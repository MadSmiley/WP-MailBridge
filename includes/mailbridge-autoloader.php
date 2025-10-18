<?php
/**
 * Autoloader for MailBridge classes
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Autoloader class
 */
class MailBridge_Autoloader {

    /**
     * Register autoloader
     */
    public static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Autoload classes
     *
     * @param string $class_name The class name
     */
    public static function autoload($class_name) {
        // Check if this is a MailBridge class
        if (strpos($class_name, 'MailBridge_') !== 0) {
            return;
        }

        // Convert class name to file name
        $file_name = strtolower(str_replace('_', '-', $class_name)) . '.php';

        // Check in includes directory
        $file_path = MAILBRIDGE_PLUGIN_DIR . 'includes/' . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }

        // Check in admin directory
        $file_path = MAILBRIDGE_PLUGIN_DIR . 'admin/' . $file_name;
        if (file_exists($file_path)) {
            require_once $file_path;
            return;
        }
    }
}

// Register the autoloader
MailBridge_Autoloader::register();
