<?php

namespace VariableCache\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\Filesystem\Folder;
use Cake\TestSuite\TestCase;
use VariableCache\Lib\CachedVariableUtility;
use VariableCache\Model\Entity\CachedVariable;

/**
 * Tests for CachedVariableUtility
 * FYI: `iterator_to_array()` is called to execute `\Generator`.
 */
class CachedVariableUtilityTest extends TestCase
{
    public const TEST_VALUE = 'works';

    public function testAddAddsACachedVariable()
    {
        $variable = CachedVariableUtility::add('foo', []);

        $this->assertInstanceOf(CachedVariable::class, $variable);

        $variable = CachedVariableUtility::get($variable->name);

        $this->assertInstanceOf(CachedVariable::class, $variable);

    }

    public function testGetVariableReturnsNullIfVariableDoesNotExist()
    {
        $variable = CachedVariableUtility::get('foo');

        $this->assertNull($variable);
    }

    public static function foobar()
    {
        return self::TEST_VALUE;
    }

    public function testExecuteExecutesVariable()
    {
        $variable = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        $results = CachedVariableUtility::execute($variable);

        $results = iterator_to_array($results);

        $this->assertSame(CachedVariable::EXECUTION_COMPLETE, $results[1]->execution_status);
        $this->assertSame(self::TEST_VALUE, $variable->content);
    }

    public function testAddingDependent()
    {
        $main = CachedVariableUtility::add('foo', []);

        CachedVariableUtility::add('bar', [], $main);

        $exists = CachedVariableUtility::get('bar');

        $this->assertInstanceOf(CachedVariable::class, $exists);

        $results = CachedVariableUtility::getDependentVariables($main);

        $results = iterator_to_array($results);

        $this->assertNotEmpty($results);
    }

    public function testExecuteExecutesDependentVariables()
    {
        $main = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::add('bar', [
            'callback' => [self::class, 'foobar']
        ], $main);

        $main = CachedVariableUtility::get('foo');

        $results = CachedVariableUtility::execute($main);

        $results = iterator_to_array($results);

        $this->assertSame(self::TEST_VALUE, $main->content);

        $dependent = CachedVariableUtility::get('bar');

        $this->assertSame(self::TEST_VALUE, $dependent->content);
    }

    public function testQueueAddsMainTaskToQueue()
    {
        $main = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::add('foo2', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::add('foo3', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::add('bar', [
            'callback' => [self::class, 'foobar']
        ], $main);

        $ret = CachedVariableUtility::queue();

        $this->assertSame(3, count(iterator_to_array($ret)));
    }

    public function testQueueDoesNotQueueIfTaskIsCompleted()
    {
        $main = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::add('bar', [
            'callback' => [self::class, 'foobar']
        ], $main);

        $results = CachedVariableUtility::execute($main);

        $results = iterator_to_array($results);

        $results = CachedVariableUtility::queue();

        $this->assertEmpty(iterator_to_array($results));
    }

    public function testResetResetsExecutionStatus()
    {
        $main = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        CachedVariableUtility::execute($main);

        CachedVariableUtility::resetAll();

        $main = CachedVariableUtility::get('foo');

        $this->assertSame(CachedVariable::EXECUTION_INITIAL, $main->execution_status);
    }

    public function testQueueDoesNotAddTheSameJobMultipleTimes()
    {
        $count = 0;
        Configure::write('VariableCache.Queue.callback', function (CachedVariable $variable) use (&$count) {
            $count++;

            return true;
        });

        $main = CachedVariableUtility::add('foo', [
            'callback' => [self::class, 'foobar']
        ]);

        $dep1 = CachedVariableUtility::add('bar', [
            'callback' => [self::class, 'foobar']
        ], $main);

        $dep2 = CachedVariableUtility::add('car', [
            'callback' => [self::class, 'foobar']
        ], $dep1);

        CachedVariableUtility::add('dar', [
            'callback' => [self::class, 'foobar']
        ], $dep2);

        CachedVariableUtility::add('ear', [
            'callback' => [self::class, 'foobar']
        ], $dep1);


        $results = CachedVariableUtility::execute($main);

        $results = iterator_to_array($results);

        CachedVariableUtility::reset(CachedVariableUtility::get('bar'));

        $results = CachedVariableUtility::queue();

        $results = iterator_to_array($results);

        $this->assertSame(1, $count);
    }

    public function tearDown()
    {
        $folder = new Folder(TMP);
        $files = $folder->findRecursive();

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
}
