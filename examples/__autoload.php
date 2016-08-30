<?php
/**
 * PSR-4: Autoloader
 */
spl_autoload_register(function($class) {
    
    $prefix = 'SerginhoLD\\CSV\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    
    if (strncmp($prefix, $class, $len) !== 0)
        return;
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file))
        require $file;
    
});