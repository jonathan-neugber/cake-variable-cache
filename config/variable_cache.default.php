<?php
use Josegonzalez\CakeQueuesadilla\Queue\Queue;
use VariableCache\Lib\Engine\DatabaseCacheProvider;
use VariableCache\Lib\QueuesadillaCallbacks;
use VariableCache\Model\Entity\CachedVariable;

return [
    'VariableCache' => [
        'DataProvider' => [
            'className' => DatabaseCacheProvider::class
        ],
        'Queue' => [
            'callback' => function (CachedVariable $variable) {
                return Queue::push([
                    QueuesadillaCallbacks::class,
                    'executeJob'
                ], [
                    'name' => $variable->name
                ]);
            }
        ],
        'variables' => [

        ]
    ]
];
