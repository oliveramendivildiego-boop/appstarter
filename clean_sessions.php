#!/usr/bin/env php
<?php
// Script para limpiar sesiones activas

require __DIR__ . '/vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
@$dotenv->load();

// Inicializar config
$config = new Config\Database();
$db = new CodeIgniter\Database\ConnectionFactory();
$connection = $db->connect($config->default);

// Limpiar tabla
try {
    $connection->table('active_sessions')->truncate();
    echo "✓ Tabla active_sessions limpiada\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
