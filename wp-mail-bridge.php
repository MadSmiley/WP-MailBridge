<?php
/**
 * Plugin Name: WP MailBridge
 * Plugin URI: https://github.com/MadSmiley/WP-MailBridge
 * Description: MailBridge is a WordPress plugin that centralizes the management of personalized transactional emails.
 * Version: 1.0.0
 * Author: MadSmiley
 * Author URI: https://www.linkedin.com/in/germain-belacel/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-mail-bridge
 * Domain Path: /languages
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MAILBRIDGE_VERSION', '1.0.1');
define('MAILBRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAILBRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAILBRIDGE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once MAILBRIDGE_PLUGIN_DIR . 'includes/mailbridge-autoloader.php';

/**
 * Check for database updates
 */
function mailbridge_check_version() {
    $installed_version = get_option('mailbridge_version', '0');

    if (version_compare($installed_version, MAILBRIDGE_VERSION, '<')) {
        require_once MAILBRIDGE_PLUGIN_DIR . 'includes/mailbridge-activator.php';
        MailBridge_Activator::activate();
    }
}
add_action('plugins_loaded', 'mailbridge_check_version', 5);

/**
 * Main plugin class initialization
 */
function mailbridge_init() {
    // Load text domain for translations
    load_plugin_textdomain('wp-mail-bridge', false, dirname(MAILBRIDGE_PLUGIN_BASENAME) . '/languages');

    // Initialize the plugin
    $plugin = new MailBridge_Core();
    $plugin->run();
}
add_action('plugins_loaded', 'mailbridge_init');

/**
 * Plugin activation hook
 */
function mailbridge_activate() {
    require_once MAILBRIDGE_PLUGIN_DIR . 'includes/mailbridge-activator.php';
    MailBridge_Activator::activate();
}
register_activation_hook(__FILE__, 'mailbridge_activate');

/**
 * Plugin deactivation hook
 */
function mailbridge_deactivate() {
    require_once MAILBRIDGE_PLUGIN_DIR . 'includes/mailbridge-deactivator.php';
    MailBridge_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'mailbridge_deactivate');

/**
 * Developer API: Send an email using a template
 *
 * @param string $template_name The template identifier
 * @param array  $variables     Associative array of variables to replace
 * @param string $to           Recipient email address (optional, can be in variables)
 * @param string $language     Language code (optional, defaults to site language)
 * @return bool                 True on success, false on failure
 */
function mailbridge_send($template_name, $variables = array(), $to = '', $language = '') {
    $sender = new MailBridge_Sender();
    return $sender->send($template_name, $variables, $to, $language);
}

/**
 * Developer API: Register an email type
 *
 * @param string $id          Unique identifier for the email type
 * @param array  $args        Configuration array
 *                            - name: Display name
 *                            - description: Description of the email type
 *                            - variables: Array of available variables
 *                            - plugin: Plugin/module name
 *                            - default_subject: Default subject line
 *                            - default_content: Default email content
 *                            - preview_values: Array of example values for variables (e.g., array('user_name' => 'John Doe'))
 *                            - languages: Array of expected language codes (e.g., array('en', 'fr'))
 * @return bool               True on success, false on failure
 */
function mailbridge_register_email_type($id, $args = array()) {
    return MailBridge_Registry::register_email_type($id, $args);
}

/**
 * Developer API: Get all registered email types
 *
 * @return array Array of registered email types
 */
function mailbridge_get_email_types() {
    return MailBridge_Registry::get_email_types();
}
