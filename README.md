# CakePHP 3 cake-variable-cache

[![License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.txt)

## Description
This plugin is designed to asynchronously execute calculations that take a long amount of time and save the results (e.g.: Statistics).

It allows you to create a simple list of variables that are to be calculated every n-amount of time.

It also supports dependent variables (e.g.: variable `bar` requires variable `foo` to be calculated first).

## Installation

#### 1. require the plugin in your `composer.json`

		"require": {
			...
			"jonathan-neugber/cake-variable-cache": "dev-master",
			...
		}

#### 2. Include the plugin using composer
Open a terminal in your project-folder and run these commands:

	$ composer update
	$ composer install


#### 3. Load the plugin in your `config/bootstrap.php`

	Plugin::load('VariableCache', ['bootstrap' => true]);

#### 4. Add configuration to your `config/app.php`

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
            'variables' => []
        ]

#### 5. Migrations

Open a terminal in your project-folder and run this command:

    $ bin/cake migrations migrate --source=../vendor/jonathan-neugber/cake-variable-cache/config/Migrations/

## Usage / Example

#### 1. Create a callback

Lets say you want to calculate a statistic:

```php
class Statistic
{
    public static function calculateStatistic($amount)
    {
        return 20000 * $amount;
    }
}
```

#### 2. Create a config

In the `VariableCache.variables` section of the configuration add the following:

```php
'foo' => [
    'callback' => ['\Statistics', 'calculateStatistic'],
    'interval' => '5 minutes',
    'args' => [
        200 // this will be passed as the first argument
    ],
    'variables' => [
        'foo' => [
            'callback' => ['\Statistics', 'calculateStatistic'],
            'interval' => '30 seconds',
            'args' => [
                100
            ]
        ]
    ]
]
```

#### 3. Import the config

Open a terminal in your project-folder and run this command:

    $ bin/cake VariableCache.CachedVariables update

This will create the cached variables in the Database.

#### 4. Run the queues

Execute the following commands in parallel in a terminal in your project-folder and run this command:

    $ bin/cake VariableCache.CachedVariables update

and

    $ bin/cake queuesadilla

#### 5. Access the variable

```php
$foo = CachedVariableUtility::get('foo');
$foo->content; // value
```
**OR**
```php
$data = CachedVariableUtility::getMultiple(['foo', 'bar']);
```
**OR**
```php
// Returns an array with name => value
$data = CachedVariableUtility::getAsKeyValue(['foo', 'bar']);
```

## Additional Information

#### DynamicCalculationTrait
The `DynamicCalculationTrait` allows you to easily create a library of callbacks for cached variables.
Using `DynamicCalculationTrait::calculate()` as the callback will automatically call
the function `calculate<CachedVariableName>` in the class.
##### Example:

```php
class Statistic
{
    use DynamicCalculationTrait;

    public static function calculateFoo($amount)
    {
        return 20000 * $amount;
    }

    public static function calculateBar($amount)
    {
        return 20000 * $amount;
    }
}
```


```php
'foo' => [
    'callback' => ['\Statistics', 'calculate'],
    'interval' => '5 minutes',
    'args' => [
        200 // this will be passed as the first argument
    ],
    'variables' => [
        'foo' => [
            'callback' => ['\Statistics', 'calculate'],
            'interval' => '30 seconds',
            'args' => [
                100
            ]
        ]
    ]
]
```

## TODO

- Write more tests
 - Use mock class for CacheProviderInterface
 - Test Cache Providers separately
- Update documentation
