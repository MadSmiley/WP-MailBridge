<?php
/**
 * Email type registry
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registry class for email types
 */
class MailBridge_Registry {

    /**
     * Registered email types
     *
     * @var array
     */
    private static $email_types = array();

    /**
     * Initialize the registry
     */
    public function init() {
        // Allow plugins to register their email types
        do_action('mailbridge_register_email_types');

        // Save registered types to database
        $this->sync_to_database();
    }

    /**
     * Register an email type
     *
     * @param string $id   Email type ID
     * @param array  $args Configuration arguments
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function register_email_type($id, $args = array()) {
        // Validate ID
        if (empty($id)) {
            return new WP_Error(
                'empty_email_type_id',
                __('Email type ID cannot be empty', 'wp-mail-bridge'),
                ['args' => $args]
            );
        }

        // Default arguments
        $defaults = [
            'name' => '',
            'description' => '',
            'variables' => [],
            'plugin' => '',
            'default_subject' => '',
            'default_content' => '',
            'preview_values' => [],
            'languages' => [],
            'variations' => [],
        ];

        $args = wp_parse_args($args, $defaults);

        // Store in memory
        self::$email_types[$id] = $args;

        return true;
    }

    /**
     * Get all registered email types
     *
     * @return array
     */
    public static function get_email_types() {
        return self::$email_types;
    }

    /**
     * Get a specific email type
     *
     * @param string $id Email type ID
     * @return array|null
     */
    public static function get_email_type($id) {
        return isset(self::$email_types[$id]) ? self::$email_types[$id] : null;
    }

    /**
     * Sync registered types to database
     *
     * @return bool|WP_Error True on success, WP_Error on database failure
     */
    private function sync_to_database() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_email_types';

        foreach (self::$email_types as $type_id => $type_data) {
            // Check if type exists
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE type_id = %s",
                $type_id
            ));

            // Check for database errors
            if ($wpdb->last_error) {
                return new WP_Error(
                    'database_error',
                    sprintf(__('Database error while checking email type: %s', 'wp-mail-bridge'), $wpdb->last_error),
                    ['type_id' => $type_id]
                );
            }

            // Convert languages array to comma-separated string
            $languages_str = '';
            if (!empty($type_data['languages']) && is_array($type_data['languages'])) {
                $languages_str = implode(',', $type_data['languages']);
            }

            $data = [
                'type_id' => $type_id,
                'name' => $type_data['name'],
                'description' => $type_data['description'],
                'variables' => maybe_serialize($type_data['variables']),
                'plugin_name' => $type_data['plugin'],
                'default_subject' => maybe_serialize($type_data['default_subject']),
                'default_content' => maybe_serialize($type_data['default_content']),
                'preview_values' => maybe_serialize($type_data['preview_values']),
                'languages' => $languages_str,
                'variations' => maybe_serialize($type_data['variations']),
            ];

            if ($exists) {
                // Update existing
                $result = $wpdb->update(
                    $table,
                    $data,
                    ['type_id' => $type_id],
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                    ['%s']
                );

                if ($result === false) {
                    return new WP_Error(
                        'database_update_failed',
                        sprintf(__('Failed to update email type in database: %s', 'wp-mail-bridge'), $wpdb->last_error),
                        ['type_id' => $type_id]
                    );
                }
            } else {
                // Insert new
                $result = $wpdb->insert(
                    $table,
                    $data,
                    ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
                );

                if ($result === false) {
                    return new WP_Error(
                        'database_insert_failed',
                        sprintf(__('Failed to insert email type in database: %s', 'wp-mail-bridge'), $wpdb->last_error),
                        ['type_id' => $type_id]
                    );
                }
            }
        }

        return true;
    }

    /**
     * Get email types from database
     *
     * @return array|WP_Error Array of email types on success, WP_Error on database failure
     */
    public static function get_email_types_from_db() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_email_types';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

        // Check for database errors
        if ($wpdb->last_error) {
            return new WP_Error(
                'database_error',
                sprintf(__('Database error while retrieving email types: %s', 'wp-mail-bridge'), $wpdb->last_error)
            );
        }

        $types = [];
        foreach ($results as $row) {
            // Convert comma-separated languages string to array
            $languages = [];
            if (!empty($row->languages)) {
                $languages = explode(',', $row->languages);
            }

            $types[$row->type_id] = [
                'name' => $row->name,
                'description' => $row->description,
                'variables' => maybe_unserialize($row->variables),
                'plugin' => $row->plugin_name,
                'default_subject' => maybe_unserialize($row->default_subject),
                'default_content' => maybe_unserialize($row->default_content),
                'preview_values' => isset($row->preview_values) ? maybe_unserialize($row->preview_values) : [],
                'languages' => $languages,
                'variations' => isset($row->variations) ? maybe_unserialize($row->variations) : [],
            ];
        }

        return $types;
    }
}
