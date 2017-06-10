<?php
declare(strict_types=1);

namespace VariableCache\Lib;

use josegonzalez\Queuesadilla\Job\Base;
use Throwable;
use VariableCache\Model\Entity\CachedVariable;

class QueuesadillaCallbacks
{
    /**
     * Queuesadilla callback on job execution
     *
     * @param Base $job Job
     * @return bool
     */
    public static function executeJob(Base $job): bool
    {
        try {
            $variable = CachedVariableUtility::get($job->data('name'));
            $results = CachedVariableUtility::execute($variable);

            $ret = true;
            foreach ($results as $result) {
                if ($result->execution_status === CachedVariable::EXECUTION_FAILED) {
                    $ret = false;
                }
            }

            return $ret;
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
