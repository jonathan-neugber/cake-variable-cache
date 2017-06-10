<?php
declare(strict_types=1);

namespace VariableCache\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use VariableCache\Model\Entity\CachedVariable;

/**
 * CachedVariables Model
 * @method \VariableCache\Model\Entity\CachedVariable get($primaryKey, $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable newEntity($data = null, array $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable[] newEntities(array $data, array $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable patchEntity(\Cake\Datasource\EntityInterface $entity, array $data,
 *         array
 *         $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable[] patchEntities($entities, array $data, array $options = [])
 * @method \VariableCache\Model\Entity\CachedVariable findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CachedVariablesTable extends Table
{

    /**
     * {@inheritdoc}
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->table('cached_variables');
        $this->displayField('name');
        $this->primaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Tree');
    }

    /**
     * {@inheritdoc}
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmpty('id', 'create');

        $validator
            ->requirePresence('name', 'create')
            ->notEmpty('name');

        $validator
            ->requirePresence('config', 'create')
            ->notEmpty('config');

        $validator
            ->requirePresence('execution_status', 'create')
            ->inList('execution_status', array_keys(CachedVariable::typeDescriptions()))
            ->notEmpty('execution_status');

        $validator
            ->allowEmpty('content');

        return $validator;
    }

    /**
     * {@inheritdoc}
     */
    protected function _initializeSchema(TableSchema $schema): TableSchema
    {
        parent::_initializeSchema($schema);

        $schema->columnType('content', 'json');
        $schema->columnType('config', 'json');

        return $schema;
    }
}
