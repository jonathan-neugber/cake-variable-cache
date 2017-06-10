<?php
declare(strict_types=1);

namespace VariableCache\Lib;

use Cake\Utility\Inflector;

trait DynamicCalculationTrait
{
    /**
     * If a class implements this trait you can call `Foo::calculate('bar');`
     * which then constructs the `Foo` class and calls `calculateBar`.
     * This is useful as the `CachedVariable::execute()` always passes the name as the first argument
     * to the callback.
     * This allows that you can define `[\Foo::class, 'calculate']` as the callback for the variable.
     *
     * @param string $cachedVariableName Name of the variable.
     * @param array  $args               Arguments for the callback function.
     * @param array  $constructorArgs    Constructor arguments for the class.
     * @return mixed
     * @throws \Exception
     */
    public static function calculate(string $cachedVariableName, array $args = [], array $constructorArgs = [])
    {
        $object = new self(...$constructorArgs);

        $cachedVariableName = 'calculate' . Inflector::camelize($cachedVariableName);

        $memoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '-1');
        if (!method_exists($object, $cachedVariableName)) {
            throw new \Exception(
                sprintf(
                    'Calculation function "%s" does not exist in class "%s".',
                    $cachedVariableName,
                    self::class
                )
            );
        }

        $ret = $object->$cachedVariableName(...$args);
        ini_set('memory_limit', $memoryLimit);

        return $ret;
    }
}
