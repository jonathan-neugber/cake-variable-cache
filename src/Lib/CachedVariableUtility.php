<?php

declare(strict_types=1);

namespace VariableCache\Lib;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Core\StaticConfigTrait;
use Cake\Utility\Hash;
use Exception;
use Generator;
use Throwable;
use VariableCache\Lib\Engine\CacheProviderEngineRegistry;
use VariableCache\Lib\Engine\CacheProviderInterface;
use VariableCache\Model\Entity\CachedVariable;

/**
 * Utility for cached variables.
 */
class CachedVariableUtility
{
    use StaticConfigTrait;

    /**
     * @var CacheProviderEngineRegistry $_registry
     */
    protected static $_registry;

    /**
     * @var CacheProviderInterface $_dataProvider
     */
    protected static $_dataProvider;

    /**
     * Adds a new cached variable.
     *
     * @param string                                          $name   Name of the variable
     * @param array                                           $config Configuration for the variable
     * @param \VariableCache\Model\Entity\CachedVariable|null $parent Optional parent of the variable
     * @return \VariableCache\Model\Entity\CachedVariable
     */
    public static function add(string $name, array $config, CachedVariable $parent = null): CachedVariable
    {
        $provider = self::_getDataProvider();
        $defaultConfig = [
            'interval' => '10 Minutes'
        ];

        $variable = self::get($name);
        if (is_null($variable)) {
            $variable = new CachedVariable();
        }

        $variable->name = $name;

        $variable->execution_status = CachedVariable::EXECUTION_INITIAL;
        $variable->config = Hash::merge($defaultConfig, $config);
        $variable->content = null;

        if (!is_null($parent)) {
            $variable->parent_id = $parent->id;
        }


        return $provider->add($variable);
    }

    /**
     * Executes the queue callback defined in the app.php for every cached variable that needs to be queued.
     *
     * @param Generator $variables list of variables to check. If empty it starts with the main variables.
     * @return Generator<CachedVariable>
     * @throws \Exception If the callback in the app.php is not defined.
     * @internal
     */
    public static function queue(Generator $variables = null): Generator
    {
        if (is_null($variables)) {
            $variables = self::getMainVariables();
        }

        foreach ($variables as $variable) {
            if (array_key_exists($variable->execution_status, CachedVariable::getSkippableExecutionStatus())) {
                continue;
            }

            /*
             * If variable requires execution -> don't add dependent vars
             * Else -> check children
             */
            if ($variable->requiresExecution()) {
                if (self::_addToQueue($variable)) {
                    $variable->execution_status = CachedVariable::EXECUTION_PENDING;
                    yield self::_update($variable);
                }
            } else {
                $dependent = self::getDependentVariables($variable);

                yield from self::queue($dependent);
            }
        }
    }

    /**
     * Returns a generator for all main variables.
     *
     * @return \Generator
     */
    public static function getMainVariables(): Generator
    {
        yield from self::_getDataProvider()->getMainVariables();
    }

    /**
     * Returns a generator for all dependent variables.
     *
     * @param \VariableCache\Model\Entity\CachedVariable $variable
     * @return \Generator
     */
    public static function getDependentVariables(CachedVariable $variable): Generator
    {
        yield from self::_getDataProvider()->getDependentVariables(...func_get_args());
    }

    /**
     * Resets all main variables.
     *
     * @return Generator
     */
    public static function resetAll(): Generator
    {
        $variables = self::getMainVariables();

        foreach ($variables as $variable) {
            yield self::reset($variable);
        }
    }

    /**
     * Resets a single cached variable.
     *
     * @param \VariableCache\Model\Entity\CachedVariable $variable
     * @return \VariableCache\Model\Entity\CachedVariable
     */
    public static function reset(CachedVariable $variable): CachedVariable
    {
        $variable->execution_status = CachedVariable::EXECUTION_INITIAL;
        return self::_update($variable);
    }

    /**
     * Executes a cached variable.
     *
     * @param CachedVariable $variable variable to execute
     * @return Generator<CachedVariable>
     * @internal
     */
    public static function execute(CachedVariable $variable): Generator
    {
        try {
            $variable->execution_status = CachedVariable::EXECUTION_ONGOING;
            yield self::_update($variable);

            $variable->execute();

            $variable->execution_status = CachedVariable::EXECUTION_COMPLETE;
            yield self::_update($variable);

            $dependents = self::getDependentVariables($variable);

            foreach ($dependents as $dependent) {
                yield from self::execute($dependent);
            }
        } catch (Throwable $throwable) {
            $variable->content = null;
            $variable->execution_status = CachedVariable::EXECUTION_FAILED;

            return yield self::_update($variable);
        }
    }

    /**
     * Returns a variable.
     *
     * @param string $name Name of the variable
     * @return null|\VariableCache\Model\Entity\CachedVariable
     */
    public static function get(string $name): ?CachedVariable
    {
        return self::_getDataProvider()->getVariable(...func_get_args());
    }

    /**
     * Returns an array of cached variables.
     *
     * @param string[] $names Names of the variables
     * @return CachedVariable[]
     */
    public static function getMultiple(array $names): array
    {
        return self::_getDataProvider()->getVariables($names);
    }

    /**
     * Converts an array of cached variables to a name => content array.
     *
     * @param CachedVariable[] $variables Variables
     * @return array
     */
    public static function getAsKeyValue(array $variables): array
    {
        return (new Collection($variables))
            ->combine('name', 'content')
            ->toArray();
    }

    /**
     * Adds a variable to the queue.
     *
     * @param \VariableCache\Model\Entity\CachedVariable $variable Variable to add to queue.
     * @return bool
     * @throws \Exception If the callback is not defined.
     */
    protected static function _addToQueue(CachedVariable $variable): bool
    {
        $callback = Configure::read('VariableCache.Queue.callback');

        if (!is_callable($callback)) {
            throw new Exception('Queue callback not valid');
        }

        return $callback($variable);
    }

    /**
     * Returns the CachedVariable data provider.
     *
     * @return \VariableCache\Lib\Engine\CacheProviderInterface
     */
    protected static function _getDataProvider(): CacheProviderInterface
    {
        if (!isset(self::$_dataProvider)) {
            $registry = self::_registry();

            $dataProvider = Configure::read('VariableCache.DataProvider');

            self::$_dataProvider = $registry->load($dataProvider['className'], $dataProvider);
        }

        return self::$_dataProvider;
    }

    /**
     * Updates a cached variable.
     *
     * @param \VariableCache\Model\Entity\CachedVariable $variable
     * @return \VariableCache\Model\Entity\CachedVariable
     */
    protected static function _update(CachedVariable $variable): CachedVariable
    {
        return self::_getDataProvider()->updateVariable($variable);
    }

    /**
     * Initializes registry and configurations.
     *
     * @return CacheProviderEngineRegistry
     */
    protected static function _registry(): CacheProviderEngineRegistry
    {
        if (empty(static::$_registry)) {
            static::$_registry = new CacheProviderEngineRegistry();
        }

        return static::$_registry;
    }
}
