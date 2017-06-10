<?php
use Cake\Core\Configure;
use Cake\Utility\Hash;

// Load and merge default with app config
$config = include 'variable_cache.default.php';
$config = $config['VariableCache'];
if ($appMonitorConfig = Configure::read('VariableCache')) {
    $config = Hash::merge($config, $appMonitorConfig);
}
Configure::write('VariableCache', $config);
