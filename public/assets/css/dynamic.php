<?php
/**
 * Динамічний CSS файл з кольорами з налаштувань
 */

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: public, max-age=60');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60) . ' GMT');

define('APP_ROOT', dirname(dirname(dirname(__DIR__))));
require_once APP_ROOT . '/config/config.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Models\Settings;

$settings = new Settings();

$colors = [
    '--primary' => $settings->get('color_primary', '#6366f1'),
    '--primary-dark' => $settings->get('color_primary_dark', '#4f46e5'),
    '--primary-light' => $settings->get('color_primary_light', '#818cf8'),
    '--secondary' => $settings->get('color_secondary', '#64748b'),
    '--success' => $settings->get('color_success', '#10b981'),
    '--danger' => $settings->get('color_danger', '#ef4444'),
    '--warning' => $settings->get('color_warning', '#f59e0b'),
    '--info' => $settings->get('color_info', '#06b6d4')
];

function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2))
    ];
}

echo ":root {\n";
foreach ($colors as $var => $value) {
    $value = strtoupper(trim($value));
    if (preg_match('/^#[0-9A-F]{6}$/', $value)) {
        echo "    {$var}: {$value};\n";
        
        if ($var === '--primary') {
            $rgb = hexToRgb($value);
            echo "    --primary-rgb: {$rgb['r']}, {$rgb['g']}, {$rgb['b']};\n";
            echo "    --primary-rgba-05: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.05);\n";
            echo "    --primary-rgba-10: rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, 0.1);\n";
        }
    }
}
echo "}\n";
