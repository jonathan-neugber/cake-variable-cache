<?php

namespace VariableCache\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Generator;
use VariableCache\Lib\CachedVariableUtility;

/**
 * Statistics shell command.
 *
 * @property \VariableCache\Model\Table\CachedVariablesTable CachedVariables
 */
class CachedVariablesShell extends Shell
{

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        parent::initialize();

        $this->loadModel('VariableCache.CachedVariables');
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionParser()
    {
        $parser = parent::getOptionParser();

        return $parser;
    }

    /**
     * Resets all cached variables
     *
     * @return void
     */
    public function reset(): void
    {
        $results = CachedVariableUtility::resetAll();

        foreach ($results as $result) {
            $this->out(
                sprintf(
                    '%s -> reset',
                    $result->name,
                    $result->execution_status
                )
            );
        }
    }

    /**
     * main() method.
     *
     * @return void
     * @throws \Exception
     */
    public function main(): void
    {
        $waitTime = 3;

        while (true) {
            $results = CachedVariableUtility::queue();

            foreach ($results as $result) {
                $this->out(
                    sprintf(
                        '%s -> %s',
                        $result->name,
                        $result->execution_status
                    )
                );
            }

            sleep($waitTime);
        }
    }

    /**
     * Adds or updates the cached variables from the config.
     *
     * @return void
     */
    public function update(): void
    {
        $varConfig = Configure::read('VariableCache.variables');

        $results = $this->_update($varConfig);

        foreach ($results as $result) {
            $this->out(
                sprintf(
                    '%s -> added',
                    $result->name
                )
            );
        }
    }

    /**
     * Recursive update function.
     *
     * @param array       $varConfig Configuration for cached variable.
     * @param string|null $parent    Parent of cached variable.
     * @return \Generator
     */
    protected function _update(array $varConfig = [], string $parent = null): Generator
    {
        if (empty($varConfig)) {
            return $parent;
        }

        if (!is_null($parent)) {
            $parent = CachedVariableUtility::get($parent);
        }

        foreach ($varConfig as $name => $config) {
            $subConfig = [];
            if (isset($config['variables'])) {
                $subConfig = $config['variables'];

                unset($config['variables']);
            }

            yield CachedVariableUtility::add($name, $config, $parent);

            yield from $this->_update($subConfig, $name);
        }
    }
}
