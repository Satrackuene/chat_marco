<?php
/*
Plugin Name: Chat Marco Satrack IA
Description: Chat para los servicios de Satrack, registros en MySQL y conexión a n8n. Arquitectura SOLID.
Version: 0.2.0
Author: Juan Carlos Zorro
License: GPLv2 or later
Text Domain: chat-marco-ai
*/

if (!defined('ABSPATH')) {
  exit;
}

define('SCM_VERSION', '0.2.1');
define('SCM_PATH', plugin_dir_path(__FILE__));
define('SCM_URL', plugin_dir_url(__FILE__));

// Simple autoloader PSR-4-like for the SCM\ namespace
spl_autoload_register(function ($class) {
  if (strpos($class, 'SCM\\') !== 0)
    return;
  $path = SCM_PATH . 'includes/' . str_replace(['SCM\\', '\\'], ['', '/'], $class) . '.php';
  if (file_exists($path))
    require $path;
});

// Activation: DB tables
register_activation_hook(__FILE__, function () {
  \SCM\Infrastructure\Activator::activate();
});

add_action('plugins_loaded', function () {
  $plugin = new \SCM\Plugin();
  $plugin->init();
});
