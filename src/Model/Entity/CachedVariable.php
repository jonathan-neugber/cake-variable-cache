<?php
declare(strict_types=1);

namespace VariableCache\Model\Entity;

use Cake\ORM\Entity;
use CkTools\Utility\TypeAwareTrait;

/**
 * CachedVariable Entity
 *
 * @property string          $id
 * @property string          $parent_id
 * @property string          $name
 * @property array           $content
 * @property array           $config
 * @property string          $execution_status
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 */
class CachedVariable extends Entity
{
    use TypeAwareTrait;

    public const EXECUTION_COMPLETE = 'complete';
    public const EXECUTION_ONGOING = 'ongoing';
    public const EXECUTION_PENDING = 'pending';
    public const EXECUTION_FAILED = 'failed';
    public const EXECUTION_INITIAL = 'initial';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false
    ];

    /**
     * {@inheritdoc}
     */
    public static function typeDescriptions(): array
    {
        return [
            self::EXECUTION_COMPLETE => __('cached_variable.execution_complete'),
            self::EXECUTION_FAILED => __('cached_variable.execution_failed'),
            self::EXECUTION_ONGOING => __('cached_variable.execution_ongoing'),
            self::EXECUTION_PENDING => __('cached_variable.execution_pending'),
            self::EXECUTION_INITIAL => __('cached_variable.execution_initial'),
        ];
    }

    /**
     * Returns a type map of all execution status that can be skipped when adding to the queue.
     *
     * @return array
     */
    public static function getSkippableExecutionStatus(): array
    {
        return self::getTypeMap(
            self::EXECUTION_FAILED,
            self::EXECUTION_PENDING,
            self::EXECUTION_ONGOING
        );
    }


    /**
     * Returns whether this variable requires an execution.
     *
     * @return bool
     */
    public function requiresExecution(): bool
    {
        if ($this->execution_status === self::EXECUTION_COMPLETE) {
            return !$this->modified->wasWithinLast($this->config['interval']);
        }

        if ($this->execution_status === self::EXECUTION_INITIAL) {
            return true;
        }

        return false;
    }

    /**
     * Executes the callback.
     *
     * @return \VariableCache\Model\Entity\CachedVariable
     * @throws \Exception
     */
    public function execute(): CachedVariable
    {
        $callback = $this->config['callback'];

        if (!is_callable($callback)) {
            throw new \Exception('Not callable');
        }

        $args = [];

        if (isset($this->config['args'])) {
            $args = $this->config['args'];
        }

        $value = $callback($this->name, ...$args);

        $this->content = $value;

        return $this;
    }
}
