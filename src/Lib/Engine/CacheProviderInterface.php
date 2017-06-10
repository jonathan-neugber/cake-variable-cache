<?php
declare(strict_types=1);

namespace VariableCache\Lib\Engine;

use Generator;
use VariableCache\Model\Entity\CachedVariable;

interface CacheProviderInterface
{
    /**
     * Returns a cached variable by name
     *
     * @param string $name
     * @return CachedVariable
     */
    public function getVariable(string $name): ?CachedVariable;

    /**
     * Returns a list of variables
     *
     * @param string[] $names
     * @return CachedVariable[]
     */
    public function getVariables(array $names): array;

    /**
     * Adds a new a cached variable
     *
     * @param CachedVariable $variable
     * @return CachedVariable
     */
    public function add(CachedVariable $variable): CachedVariable;

    /**
     * Updates the execution status
     *
     * @param CachedVariable $variable
     * @return CachedVariable
     */
    public function updateVariable(CachedVariable $variable): CachedVariable;

    /**
     * Returns an array of main cached variables
     *
     * @return Generator<CachedVariable>
     */
    public function getMainVariables(): Generator;

    /**
     * @param CachedVariable $variable
     * @return Generator<CachedVariable>
     */
    public function getDependentVariables(CachedVariable $variable): Generator;
}
