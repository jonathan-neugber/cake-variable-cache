<?php
declare(strict_types=1);

namespace VariableCache\Lib\Engine;

use Cake\Core\App;
use Cake\Core\ObjectRegistry;
use RuntimeException;

class CacheProviderEngineRegistry extends ObjectRegistry
{
    /**
     * {@inheritdoc}
     */
    protected function _resolveClassName($class): string
    {
        if (is_object($class)) {
            return $class;
        }

        return App::className($class, 'VariableCache\Engine', 'VariableCache');
    }

    /**
     * {@inheritdoc}
     */
    protected function _throwMissingClassError($class, $plugin): void
    {
        throw new RuntimeException(sprintf('Could not load class %s', $class));
    }

    /**
     * {@inheritdoc}
     */
    protected function _create($class, $alias, $settings): CacheProviderInterface
    {
        if (is_callable($class)) {
            $class = $class($alias);
        }

        if (is_object($class)) {
            $instance = $class;
        }

        if (!isset($instance)) {
            $instance = new $class($settings);
        }

        if ($instance instanceof CacheProviderInterface) {
            return $instance;
        }

        throw new RuntimeException(
            'CacheProviders must implement CachedVariableDataProviderInterface.'
        );
    }
}
