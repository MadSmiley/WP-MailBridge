<?php
/**
 * Uninstall script for MailBridge
 *
 * Fired when the plugin is uninstalled.
 * This will remove all plugin data from the database.
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly or not uninstalling
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Define table names
$table_templates = $wpdb->prefix . 'mailbridge_templates';
$table_email_types = $wpdb->prefix . 'mailbridge_email_types';
$table_logs = $wpdb->prefix . 'mailbridge_logs';

// Drop tables
$wpdb->query("DROP TABLE IF EXISTS $table_templates");
$wpdb->query("DROP TABLE IF EXISTS $table_email_types");
$wpdb->query("DROP TABLE IF EXISTS $table_logs");

// Delete options
delete_option('mailbridge_version');
delete_option('mailbridge_installed');
delete_option('mailbridge_log_retention_days');
delete_option('mailbridge_default_language');
delete_option('mailbridge_enable_logging');

// Clear any cached data
wp_cache_flush();
