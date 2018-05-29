<?php
require 'include.php';

if (!isset($argv[1])) {
    echo 'Usage: php newupgrade.php [UPGRADE_NAME]', PHP_EOL;
    exit(1);
}

$new_upgrade_name = $argv[1];

list('dirname' => $path, 'basename' => $basename) = pathinfo($new_upgrade_name);

$full_path = 'upgrades/' . $path;

if (!file_exists($full_path)) {
    mkdir($full_path, 0755, true);
}

touch($full_path . '/' . time() . '_' . $basename . '.sql');
