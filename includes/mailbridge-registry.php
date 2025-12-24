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
     * @return bool
     */
    public static function register_email_type($id, $args = array()) {
        // Validate ID
        if (empty($id)) {
            return false;
        }

        // Default arguments
        $defaults = array(
            'name' => '',
            'description' => '',
            'variables' => array(),
            'plugin' => '',
            'default_subject' => '',
            'default_content' => '',
            'preview_values' => array(),
            'languages' => array(),
        );

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

            // Convert languages array to comma-separated string
            $languages_str = '';
            if (!empty($type_data['languages']) && is_array($type_data['languages'])) {
                $languages_str = implode(',', $type_data['languages']);
            }

            $data = array(
                'type_id' => $type_id,
                'name' => $type_data['name'],
                'description' => $type_data['description'],
                'variables' => maybe_serialize($type_data['variables']),
                'plugin_name' => $type_data['plugin'],
                'default_subject' => maybe_serialize($type_data['default_subject']),
                'default_content' => maybe_serialize($type_data['default_content']),
                'preview_values' => maybe_serialize($type_data['preview_values']),
                'languages' => $languages_str,
            );

            if ($exists) {
                // Update existing
                $wpdb->update(
                    $table,
                    $data,
                    array('type_id' => $type_id),
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                    array('%s')
                );
            } else {
                // Insert new
                $wpdb->insert(
                    $table,
                    $data,
                    array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
                );
            }
        }
    }

    /**
     * Get email types from database
     *
     * @return array
     */
    public static function get_email_types_from_db() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_email_types';

        $results = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

        $types = array();
        foreach ($results as $row) {
            // Convert comma-separated languages string to array
            $languages = array();
            if (!empty($row->languages)) {
                $languages = explode(',', $row->languages);
            }

            $types[$row->type_id] = array(
                'name' => $row->name,
                'description' => $row->description,
                'variables' => maybe_unserialize($row->variables),
                'plugin' => $row->plugin_name,
                'default_subject' => maybe_unserialize($row->default_subject),
                'default_content' => maybe_unserialize($row->default_content),
                'preview_values' => isset($row->preview_values) ? maybe_unserialize($row->preview_values) : array(),
                'languages' => $languages,
            );
        }

        return $types;
    }
}
