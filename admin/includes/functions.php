<?php

function class_auto_loader($class)
{
    $class = strtolower($class);

    $the_path = "includes/{$class}.php";

    if (is_file($the_path) && !class_exists($class)) {
        require_once($the_path);
    } else {
        die("This file named {$class}.php doesn't exist.");
    }
}

function redirect($location)
{
    header("Location: {$location}");
}

spl_autoload_register('class_auto_loader');