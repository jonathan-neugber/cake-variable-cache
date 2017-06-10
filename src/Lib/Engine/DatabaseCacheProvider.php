<?php
declare(strict_types=1);

namespace VariableCache\Lib\Engine;

use Cake\ORM\TableRegistry;
use Generator;
use VariableCache\Model\Entity\CachedVariable;

class DatabaseCacheProvider implements CacheProviderInterface
{
    /**
     * @var \VariableCache\Model\Table\CachedVariablesTable
     */
    protected $_table;

    public function __construct()
    {
        $this->_table = TableRegistry::get('VariableCache.CachedVariables');
    }

    /**
     * {@inheritdoc}
     */
    public function getVariable(string $name): ?CachedVariable
    {
        return $this->_table->find()
            ->where([
                'name' => $name
            ])
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getVariables(array $names): array
    {
        return $this->_table->find()
            ->where([
                'name IN' => $names
            ])
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function add(CachedVariable $variable): CachedVariable
    {
        if (empty($variable->config['interval'])) {
            throw new \Exception('No interval given');
        }

        /* @var \VariableCache\Model\Entity\CachedVariable $cachedVariable */
        $cachedVariable = $this->_table->find()
            ->where([
                'name' => $variable->name
            ])
            ->first();

        if (is_null($cachedVariable)) {
            $cachedVariable = $variable;
        } else {
            $this->_table->patchEntity($cachedVariable, $variable->toArray());
        }

        return $this->updateVariable($cachedVariable);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVariable(CachedVariable $variable): CachedVariable
    {
        if ($this->_table->save($variable)) {
            $this->_table->recover();

            return $variable;
        }

        throw new \Exception("Could not save '{$variable->name}'");
    }

    /**
     * {@inheritdoc}
     */
    public function getMainVariables(): Generator
    {
        yield from $this->_table->find()
            ->where([
                'parent_id IS NULL'
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getDependentVariables(CachedVariable $variable): Generator
    {
        yield from $this->_table
            ->find()
            ->where([
                'parent_id' => $variable->id
            ]);
    }
}
