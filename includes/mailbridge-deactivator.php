<?php
/**
 * Plugin deactivation handler
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deactivator class
 */
class MailBridge_Deactivator {

    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Clear any scheduled cron jobs if needed
        wp_clear_scheduled_hook('mailbridge_cleanup_logs');

        // Flush rewrite rules
        flush_rewrite_rules();

        // Note: We don't delete tables or data on deactivation
        // This preserves user data in case of accidental deactivation
        // Data deletion should only happen on uninstall
    }
}
