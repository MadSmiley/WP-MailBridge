<?php
/**
 * Core plugin class
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Core class that orchestrates the plugin
 */
class MailBridge_Core {

    /**
     * Admin instance
     *
     * @var MailBridge_Admin
     */
    protected $admin;

    /**
     * Registry instance
     *
     * @var MailBridge_Registry
     */
    protected $registry;

    /**
     * Constructor
     */
    public function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Registry for email types
        $this->registry = new MailBridge_Registry();

        // Admin interface
        if (is_admin()) {
            $this->admin = new MailBridge_Admin();
        }
    }

    /**
     * Run the plugin
     */
    public function run() {
        // Initialize registry
        $this->registry->init();

        // Initialize admin
        if (is_admin()) {
            $this->admin->init();
        }

        // Register hooks
        add_action('mailbridge_register_email_types', array($this, 'register_core_email_types'));
    }

    /**
     * Register core email types
     */
    public function register_core_email_types() {
        // Example: Welcome email
        mailbridge_register_email_type('welcome_email', array(
            'name' => __('Welcome Email', 'wp-mail-bridge'),
            'description' => __('Email sent when a new user registers', 'wp-mail-bridge'),
            'variables' => array(
                'user_name' => __('User name', 'wp-mail-bridge'),
                'user_email' => __('User email address', 'wp-mail-bridge'),
                'site_name' => __('Site name', 'wp-mail-bridge'),
                'site_url' => __('Site URL', 'wp-mail-bridge'),
            ),
            'plugin' => 'MailBridge Core',
            'default_subject' => __('Welcome {{user_name}}!', 'wp-mail-bridge'),
            'default_content' => __('<h1>Welcome to {{site_name}}, {{user_name}}!</h1><p>Thank you for joining us.</p>', 'wp-mail-bridge'),
        ));

        // Example: Notification
        mailbridge_register_email_type('notification', array(
            'name' => __('Generic Notification', 'wp-mail-bridge'),
            'description' => __('Generic notification email template', 'wp-mail-bridge'),
            'variables' => array(
                'notification_title' => __('Notification title', 'wp-mail-bridge'),
                'notification_message' => __('Notification message', 'wp-mail-bridge'),
                'notification_date' => __('Notification date', 'wp-mail-bridge'),
            ),
            'plugin' => 'MailBridge Core',
            'default_subject' => __('Notification: {{notification_title}}', 'wp-mail-bridge'),
            'default_content' => __('<h2>{{notification_title}}</h2><p>{{notification_message}}</p>', 'wp-mail-bridge'),
        ));
    }
}
