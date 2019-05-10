<?php

spl_autoload_register(function($class_name) {
    $class_name = str_replace('\\', '/', $class_name);
    include_once "include/class/" . strtolower($class_name) . ".php";
});
