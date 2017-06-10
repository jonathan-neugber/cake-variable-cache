<?php
declare(strict_types=1);

namespace VariableCache\Lib\Engine;

use Cake\Core\InstanceConfigTrait;
use Cake\Filesystem\File;
use Cake\Filesystem\Folder;
use Cake\I18n\Time;
use Generator;
use VariableCache\Model\Entity\CachedVariable;

/**
 * Provider for variables cached in files.
 *
 * TODO:
 * - Improve performance
 * - Improve stability
 */
class FileCacheProvider implements CacheProviderInterface
{
    use InstanceConfigTrait;

    /**
     * @var \Cake\Filesystem\Folder $_folder
     */
    protected $_folder;

    protected $_defaultConfig = [
        'folder' => TMP
    ];

    function __construct(array $config)
    {
        $this->setConfig($config);

        $this->_folder = new Folder($this->getConfig('folder'));
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable(string $name): ?CachedVariable
    {
        return $this->_readFile($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getVariables(array $names): array
    {
        $ret = [];
        foreach ($names as $name) {
            $ret[] = $this->getVariable($name);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function add(CachedVariable $variable): CachedVariable
    {
        $variable->id = $variable->name;
        $variable->created = Time::now();
        $variable->modified = Time::now();

        $value = json_encode($variable->toArray());

        $path = $this->_folder->path . $variable->id . '.json';

        if (!empty($variable->parent_id)) {
            $path = $this->_folder->path . DS . $variable->parent_id . DS . $variable->id . '.json';
        }

        $file = new File($path, true);

        $file->write($value);

        return $variable;
    }

    /**
     * {@inheritdoc}
     */
    public function updateVariable(CachedVariable $variable): CachedVariable
    {
        $existing = $this->_readFile($variable->id);

        $existing->set($variable->toArray());

        return $this->add($variable);
    }

    /**
     * {@inheritdoc}
     */
    public function getMainVariables(): Generator
    {
        $files = $this->_folder->find('.*.json', true);

        foreach ($files as $file) {
            $file = new File($this->_folder->path . $file);

            $data = json_decode($file->read(), true);

            $var = new CachedVariable($data);
            $var->modified = new Time($var->modified);
            $var->created = new Time($var->created);

            yield $var;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentVariables(CachedVariable $variable): Generator
    {
        $files = $this->_folder->findRecursive('.*.json');

        foreach ($files as $file) {
            if (strpos($file, DS . $variable->id . DS) === false) {
                continue;
            }

            $file = new File($file);

            $data = json_decode($file->read(), true);

            $var = new CachedVariable($data);
            $var->modified = new Time($var->modified);
            $var->created = new Time($var->created);

            yield $var;
        }
    }

    /**
     * Returns a cached variable if it exists.
     *
     * @param string $name Cached variable name
     * @return null|\VariableCache\Model\Entity\CachedVariable
     */
    protected function _readFile(string $name): ?CachedVariable
    {
        $files = $this->_folder->findRecursive(sprintf('%s.json', $name));

        if (count($files) === 0) {
            return null;
        } else {
            $file = new File($files[0]);
        }

        $data = json_decode($file->read(), true);

        $ret = new CachedVariable($data);
        $ret->modified = new Time($ret->modified);
        $ret->created = new Time($ret->created);

        return $ret;
    }
}
