<?php

spl_autoload_register(function ($class_name) {
    $class_parts = explode('\\', $class_name);
    if (count($class_parts) > 1 && $class_parts[0] === 'Tardis') {
        unset($class_parts[0]);
    }
    $class_name = implode('/', $class_parts);
//    echo 'loaded: ' . __DIR__.'/'.$class_name . '.php'.PHP_EOL;
//    die;
    if (file_exists(__DIR__.'/'.$class_name . '.php')) {
        include __DIR__.'/'.$class_name . '.php';
    }
});
