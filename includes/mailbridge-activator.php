<?php
/**
 * Plugin activation handler
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activator class
 */
class MailBridge_Activator {

    /**
     * Activate the plugin
     */
    public static function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for email templates
        $table_templates = $wpdb->prefix . 'mailbridge_templates';

        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            template_name varchar(100) NOT NULL,
            template_slug varchar(100) NOT NULL,
            subject varchar(255) NOT NULL,
            content longtext NOT NULL,
            language varchar(10) DEFAULT 'en',
            plugin_name varchar(100) DEFAULT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY template_slug_language (template_slug, language),
            KEY plugin_name (plugin_name),
            KEY status (status)
        ) $charset_collate;";

        // Table for email type registry
        $table_registry = $wpdb->prefix . 'mailbridge_email_types';

        $sql_registry = "CREATE TABLE IF NOT EXISTS $table_registry (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            type_id varchar(100) NOT NULL,
            name varchar(255) NOT NULL,
            description text,
            variables longtext,
            plugin_name varchar(100) DEFAULT NULL,
            default_subject varchar(255) DEFAULT NULL,
            default_content longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY type_id (type_id),
            KEY plugin_name (plugin_name)
        ) $charset_collate;";

        // Table for email logs
        $table_logs = $wpdb->prefix . 'mailbridge_logs';

        $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            template_slug varchar(100) NOT NULL,
            recipient varchar(255) NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(20) DEFAULT 'sent',
            error_message text,
            sent_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY template_slug (template_slug),
            KEY recipient (recipient),
            KEY sent_at (sent_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_templates);
        dbDelta($sql_registry);
        dbDelta($sql_logs);

        // Save plugin version
        update_option('mailbridge_version', MAILBRIDGE_VERSION);
        update_option('mailbridge_installed', time());

        // Create default templates
        self::create_default_templates();
    }

    /**
     * Create default email templates
     */
    private static function create_default_templates() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_templates';

        // Check if templates already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }

        // Default welcome email template
        $wpdb->insert(
            $table,
            array(
                'template_name' => 'Welcome Email',
                'template_slug' => 'welcome_email',
                'subject' => 'Welcome {{user_name}}!',
                'content' => '<h1>Welcome to our site, {{user_name}}!</h1><p>Thank you for joining us.</p><p>Your account email: {{user_email}}</p>',
                'language' => 'en',
                'plugin_name' => 'MailBridge Core',
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // Default notification template
        $wpdb->insert(
            $table,
            array(
                'template_name' => 'Notification',
                'template_slug' => 'notification',
                'subject' => 'Notification: {{notification_title}}',
                'content' => '<h2>{{notification_title}}</h2><p>{{notification_message}}</p><p>Date: {{notification_date}}</p>',
                'language' => 'en',
                'plugin_name' => 'MailBridge Core',
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
}
