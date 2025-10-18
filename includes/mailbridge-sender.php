<?php
/**
 * Email sender with variable replacement
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sender class
 */
class MailBridge_Sender {

    /**
     * Send an email using a template
     *
     * @param string $template_slug Template identifier
     * @param array  $variables     Variables to replace
     * @param string $to           Recipient email
     * @param string $language     Language code
     * @return bool
     */
    public function send($template_slug, $variables = array(), $to = '', $language = '') {
        // Get template from database
        $template = $this->get_template($template_slug, $language);

        if (!$template) {
            $this->log_error($template_slug, $to, 'Template not found');
            return false;
        }

        // Validate required variables
        $email_type = MailBridge_Registry::get_email_type($template_slug);
        if ($email_type) {
            $validation = $this->validate_variables($variables, $email_type['variables']);
            if (!$validation['valid']) {
                $this->log_error($template_slug, $to, 'Missing variables: ' . implode(', ', $validation['missing']));
                return false;
            }
        }

        // Get recipient from variables if not provided
        if (empty($to) && isset($variables['to'])) {
            $to = $variables['to'];
        }

        if (empty($to) && isset($variables['user_email'])) {
            $to = $variables['user_email'];
        }

        if (empty($to)) {
            $this->log_error($template_slug, '', 'No recipient email provided');
            return false;
        }

        // Replace variables in subject and content
        $subject = $this->replace_variables($template->subject, $variables);
        $content = $this->replace_variables($template->content, $variables);

        // Set content type to HTML
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        // Send email
        $sent = wp_mail($to, $subject, $content);

        // Reset content type
        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));

        // Log the result
        if ($sent) {
            $this->log_success($template_slug, $to, $subject);
        } else {
            $this->log_error($template_slug, $to, 'Email sending failed');
        }

        return $sent;
    }

    /**
     * Get template from database
     *
     * @param string $slug     Template slug
     * @param string $language Language code
     * @return object|null
     */
    private function get_template($slug, $language = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_templates';

        // If no language specified, use site language
        if (empty($language)) {
            $language = substr(get_locale(), 0, 2);
        }

        // Try to get template for specific language
        $template = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE template_slug = %s AND language = %s AND status = 'active' LIMIT 1",
            $slug,
            $language
        ));

        // Fallback to English if not found
        if (!$template && $language !== 'en') {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE template_slug = %s AND language = 'en' AND status = 'active' LIMIT 1",
                $slug
            ));
        }

        // Fallback to any language
        if (!$template) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE template_slug = %s AND status = 'active' LIMIT 1",
                $slug
            ));
        }

        return $template;
    }

    /**
     * Replace variables in content
     *
     * @param string $content   Content with variables
     * @param array  $variables Variables array
     * @return string
     */
    private function replace_variables($content, $variables) {
        // Add common site variables
        $variables = array_merge($variables, array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'site_description' => get_bloginfo('description'),
            'current_date' => date_i18n(get_option('date_format')),
            'current_time' => date_i18n(get_option('time_format')),
        ));

        // Replace each variable
        foreach ($variables as $key => $value) {
            // Handle arrays and objects
            if (is_array($value) || is_object($value)) {
                $value = print_r($value, true);
            }

            // Replace {{variable}} format
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Validate required variables
     *
     * @param array $provided  Provided variables
     * @param array $required  Required variables
     * @return array
     */
    private function validate_variables($provided, $required) {
        $missing = array();

        foreach ($required as $key => $label) {
            if (!isset($provided[$key])) {
                $missing[] = $key;
            }
        }

        return array(
            'valid' => empty($missing),
            'missing' => $missing,
        );
    }

    /**
     * Set HTML content type for emails
     *
     * @return string
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Log successful email send
     *
     * @param string $template_slug Template slug
     * @param string $to           Recipient
     * @param string $subject      Email subject
     */
    private function log_success($template_slug, $to, $subject) {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_logs';

        $wpdb->insert(
            $table,
            array(
                'template_slug' => $template_slug,
                'recipient' => $to,
                'subject' => $subject,
                'status' => 'sent',
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Log email error
     *
     * @param string $template_slug Template slug
     * @param string $to           Recipient
     * @param string $error        Error message
     */
    private function log_error($template_slug, $to, $error) {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_logs';

        $wpdb->insert(
            $table,
            array(
                'template_slug' => $template_slug,
                'recipient' => $to,
                'subject' => '',
                'status' => 'failed',
                'error_message' => $error,
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        // Also log to error log for debugging
        error_log('MailBridge Error: ' . $error . ' (Template: ' . $template_slug . ', To: ' . $to . ')');
    }
}
