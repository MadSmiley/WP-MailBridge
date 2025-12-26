<?php
/**
 * Admin interface
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class MailBridge_Admin {

    /**
     * Initialize admin interface
     */
    public function init() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));

        // Register admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // Handle form submissions
        add_action('admin_init', array($this, 'handle_form_submissions'));

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . MAILBRIDGE_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('MailBridge', 'wp-mail-bridge'),
            __('MailBridge', 'wp-mail-bridge'),
            'manage_options',
            'mailbridge',
            array($this, 'render_templates_page'),
            'dashicons-email-alt'
        );

        // Templates submenu
        add_submenu_page(
            'mailbridge',
            __('Email Templates', 'wp-mail-bridge'),
            __('Templates', 'wp-mail-bridge'),
            'manage_options',
            'mailbridge',
            array($this, 'render_templates_page')
        );

        // Add/Edit template
        add_submenu_page(
            'mailbridge-email-types', // Hidden from menu
            __('Edit Template', 'wp-mail-bridge'),
            '',
            'manage_options',
            'mailbridge-edit-template',
            array($this, 'render_edit_template_page')
        );

        // Email types
        add_submenu_page(
            'mailbridge',
            __('Email Types', 'wp-mail-bridge'),
            __('Email Types', 'wp-mail-bridge'),
            'manage_options',
            'mailbridge-email-types',
            array($this, 'render_email_types_page')
        );

        // Logs
        add_submenu_page(
            'mailbridge',
            __('Email Logs', 'wp-mail-bridge'),
            __('Logs', 'wp-mail-bridge'),
            'manage_options',
            'mailbridge-logs',
            array($this, 'render_logs_page')
        );

        // Settings
        add_submenu_page(
            'mailbridge',
            __('Settings', 'wp-mail-bridge'),
            __('Settings', 'wp-mail-bridge'),
            'manage_options',
            'mailbridge-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'mailbridge') === false) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'mailbridge-admin',
            MAILBRIDGE_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            MAILBRIDGE_VERSION
        );

        // CodeMirror pour l'éditeur de code
        wp_enqueue_code_editor(array('type' => 'text/html'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        // JS Beautifier pour formatter le HTML
        wp_enqueue_script(
            'js-beautify',
            'https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.14.11/beautify-html.min.js',
            [],
            '1.14.11',
            true
        );

        // JavaScript
        wp_enqueue_script(
            'mailbridge-admin',
            MAILBRIDGE_PLUGIN_URL . 'admin/js/admin.js',
            ['jquery', 'wp-codemirror', 'js-beautify'],
            MAILBRIDGE_VERSION,
            true
        );

        // Localize script
        wp_localize_script('mailbridge-admin', 'mailbridgeAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mailbridge_admin'),
            'confirmDelete' => __('Are you sure you want to delete this template?', 'wp-mail-bridge'),
        ));
    }

    /**
     * Add settings link on plugins page
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=mailbridge') . '">' . __('Settings', 'wp-mail-bridge') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Handle form submissions
     */
    public function handle_form_submissions() {
        // Check if we have a form submission
        if (!isset($_POST['mailbridge_action'])) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['mailbridge_nonce']) || !wp_verify_nonce($_POST['mailbridge_nonce'], 'mailbridge_save_template')) {
            $error = new WP_Error(
                'nonce_verification_failed',
                __('Security check failed', 'wp-mail-bridge')
            );
            wp_die($error->get_error_message());
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            $error = new WP_Error(
                'permission_denied',
                __('You do not have permission to perform this action', 'wp-mail-bridge')
            );
            wp_die($error->get_error_message());
        }

        $action = sanitize_text_field($_POST['mailbridge_action']);

        if ($action === 'save_template') {
            $result = $this->save_template();
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
            }
        } elseif ($action === 'delete_template') {
            $result = $this->delete_template();
            if (is_wp_error($result)) {
                wp_die($result->get_error_message());
            }
        }
    }

    /**
     * Save template
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function save_template() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_templates';

        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_slug = sanitize_title($_POST['template_slug']);
        $subject = sanitize_text_field($_POST['subject']);
        $content = stripslashes(wp_kses_post($_POST['content']));
        $language = sanitize_text_field($_POST['language']);
        $plugin_name = sanitize_text_field($_POST['plugin_name']);
        $status = sanitize_text_field($_POST['status']);

        // Validation côté serveur
        if (empty($template_name) || empty($template_slug) || empty($content)) {
            return new WP_Error(
                'missing_required_fields',
                __('Template name, slug and content are required.', 'wp-mail-bridge'),
                ['template_name' => $template_name, 'template_slug' => $template_slug]
            );
        }

        $data = [
            'template_name' => $template_name,
            'template_slug' => $template_slug,
            'subject' => $subject,
            'content' => $content,
            'language' => $language,
            'plugin_name' => $plugin_name,
            'status' => $status,
        ];

        if ($template_id > 0) {
            // Update existing
            $result = $wpdb->update(
                $table,
                $data,
                ['id' => $template_id],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s'],
                ['%d']
            );

            if ($result === false) {
                return new WP_Error(
                    'template_update_failed',
                    sprintf(__('Failed to update template: %s', 'wp-mail-bridge'), $wpdb->last_error),
                    ['template_id' => $template_id]
                );
            }
            $message = 'updated';
        } else {
            // Insert new
            $result = $wpdb->insert(
                $table,
                $data,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );

            if ($result === false) {
                return new WP_Error(
                    'template_insert_failed',
                    sprintf(__('Failed to create template: %s', 'wp-mail-bridge'), $wpdb->last_error),
                    ['template_slug' => $template_slug]
                );
            }
            $message = 'created';
        }

        wp_redirect(admin_url('admin.php?page=mailbridge&message=' . $message));
        exit;
    }

    /**
     * Delete template
     *
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function delete_template() {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_templates';

        $template_id = isset($_POST['template_id']) ? intval($_POST['template_id']) : 0;

        if ($template_id <= 0) {
            return new WP_Error(
                'invalid_template_id',
                __('Invalid template ID', 'wp-mail-bridge'),
                ['template_id' => $template_id]
            );
        }

        $result = $wpdb->delete($table, ['id' => $template_id], ['%d']);

        if ($result === false) {
            return new WP_Error(
                'template_delete_failed',
                sprintf(__('Failed to delete template: %s', 'wp-mail-bridge'), $wpdb->last_error),
                ['template_id' => $template_id]
            );
        }

        wp_redirect(admin_url('admin.php?page=mailbridge&message=deleted'));
        exit;
    }

    /**
     * Render templates list page
     */
    public function render_templates_page() {
        require_once MAILBRIDGE_PLUGIN_DIR . 'admin/views/templates-list.php';
    }

    /**
     * Render edit template page
     */
    public function render_edit_template_page() {
        require_once MAILBRIDGE_PLUGIN_DIR . 'admin/views/template-edit.php';
    }

    /**
     * Render email types page
     */
    public function render_email_types_page() {
        require_once MAILBRIDGE_PLUGIN_DIR . 'admin/views/email-types.php';
    }

    /**
     * Render logs page
     */
    public function render_logs_page() {
        require_once MAILBRIDGE_PLUGIN_DIR . 'admin/views/logs.php';
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        require_once MAILBRIDGE_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
