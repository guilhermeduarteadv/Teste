<?php

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = str_replace('\\', '/', dirname($scriptName));

        if ($basePath === '/' || $basePath === '\\' || $basePath === '.') {
            $basePath = '';
        }

        // Nunca usar caminho físico do Windows como URL.
        if (preg_match('/^[A-Z]:\//i', $basePath)) {
            $basePath = '';
        }

        $path = ltrim($path, '/');

        return $basePath . ($path ? '/' . $path : '');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return base_url($path);
    }
}
