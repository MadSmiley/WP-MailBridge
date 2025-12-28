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
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function send($template_slug, $variables = array(), $to = '', $language = '') {
        // Get template from database
        $template = $this->get_template($template_slug, $language);

        // Check for database errors
        if (is_wp_error($template)) {
            $this->log_error($template_slug, $to, $template->get_error_message());
            return $template;
        }

        // Get email type to check for defaults
        $email_type = MailBridge_Registry::get_email_type($template_slug);

        // If template not found in database, try to use default content from email type
        if (!$template) {
            if ($email_type && !empty($email_type['default_content'])) {
                // Create a template object from email type defaults
                $template = $this->create_template_from_defaults($email_type, $language);
            } else {
                $error = new WP_Error(
                    'template_not_found',
                    __('Template not found and no default content available', 'wp-mail-bridge'),
                    ['template_slug' => $template_slug, 'language' => $language]
                );
                $this->log_error($template_slug, $to, $error->get_error_message());
                return $error;
            }
        }

        // Validate required variables
        if ($email_type) {
            $validation = $this->validate_variables($variables, $email_type['variables']);
            if (!$validation['valid']) {
                $error_message = sprintf(
                    __('Missing required variables: %s', 'wp-mail-bridge'),
                    implode(', ', $validation['missing'])
                );
                $error = new WP_Error(
                    'missing_variables',
                    $error_message,
                    ['missing_variables' => $validation['missing'], 'template_slug' => $template_slug]
                );
                $this->log_error($template_slug, $to, $error->get_error_message());
                return $error;
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
            $error = new WP_Error(
                'no_recipient',
                __('No recipient email provided', 'wp-mail-bridge'),
                ['template_slug' => $template_slug]
            );
            $this->log_error($template_slug, '', $error->get_error_message());
            return $error;
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
            return true;
        } else {
            $error = new WP_Error(
                'email_send_failed',
                __('Email sending failed', 'wp-mail-bridge'),
                array('template_slug' => $template_slug, 'recipient' => $to, 'subject' => $subject)
            );
            $this->log_error($template_slug, $to, $error->get_error_message());
            return $error;
        }
    }

    /**
     * Get template from database
     *
     * @param string $slug     Template slug
     * @param string $language Language code
     * @return object|WP_Error Template object on success, WP_Error on database failure
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

        // Check for database errors
        if ($wpdb->last_error) {
            return new WP_Error(
                'database_error',
                sprintf(__('Database error: %s', 'wp-mail-bridge'), $wpdb->last_error),
                ['template_slug' => $slug, 'language' => $language]
            );
        }

        // Fallback to English if not found
        if (!$template && $language !== 'en') {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE template_slug = %s AND language = 'en' AND status = 'active' LIMIT 1",
                $slug
            ));

            if ($wpdb->last_error) {
                return new WP_Error(
                    'database_error',
                    sprintf(__('Database error: %s', 'wp-mail-bridge'), $wpdb->last_error),
                    ['template_slug' => $slug, 'language' => 'en']
                );
            }
        }

        // Fallback to any language
        if (!$template) {
            $template = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table WHERE template_slug = %s AND status = 'active' LIMIT 1",
                $slug
            ));

            if ($wpdb->last_error) {
                return new WP_Error(
                    'database_error',
                    sprintf(__('Database error: %s', 'wp-mail-bridge'), $wpdb->last_error),
                    ['template_slug' => $slug]
                );
            }
        }

        return $template;
    }

    /**
     * Create a template object from email type defaults
     *
     * @param array  $email_type Email type configuration
     * @param string $language   Language code
     * @return object Template object
     */
    private function create_template_from_defaults($email_type, $language = '') {
        // If no language specified, use site language
        if (empty($language)) {
            $language = substr(get_locale(), 0, 2);
        }

        // Get subject from defaults (can be string or array by language)
        $subject = '';
        if (!empty($email_type['default_subject'])) {
            if (is_array($email_type['default_subject'])) {
                // Try specific language first
                if (isset($email_type['default_subject'][$language])) {
                    $subject = $email_type['default_subject'][$language];
                }
                // Fallback to English
                elseif (isset($email_type['default_subject']['en'])) {
                    $subject = $email_type['default_subject']['en'];
                }
                // Fallback to first available language
                elseif (!empty($email_type['default_subject'])) {
                    $subject = reset($email_type['default_subject']);
                }
            } else {
                $subject = $email_type['default_subject'];
            }
        }

        // Get content from defaults (can be string or array by language)
        $content = '';
        if (!empty($email_type['default_content'])) {
            if (is_array($email_type['default_content'])) {
                // Try specific language first
                if (isset($email_type['default_content'][$language])) {
                    $content = $email_type['default_content'][$language];
                }
                // Fallback to English
                elseif (isset($email_type['default_content']['en'])) {
                    $content = $email_type['default_content']['en'];
                }
                // Fallback to first available language
                elseif (!empty($email_type['default_content'])) {
                    $content = reset($email_type['default_content']);
                }
            } else {
                $content = $email_type['default_content'];
            }
        }

        // Create a stdClass object similar to database result
        $template = new stdClass();
        $template->subject = $subject;
        $template->content = $content;
        $template->language = $language;
        $template->status = 'active';

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
     * @return bool|WP_Error True on success, WP_Error on database failure
     */
    private function log_success($template_slug, $to, $subject) {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_logs';

        $result = $wpdb->insert(
            $table,
            [
                'template_slug' => $template_slug,
                'recipient' => $to,
                'subject' => $subject,
                'status' => 'sent',
            ],
            ['%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error(
                'log_insert_failed',
                sprintf(__('Failed to log success: %s', 'wp-mail-bridge'), $wpdb->last_error),
                ['template_slug' => $template_slug, 'recipient' => $to]
            );
        }

        return true;
    }

    /**
     * Log email error
     *
     * @param string $template_slug Template slug
     * @param string $to           Recipient
     * @param string $error        Error message
     * @return bool|WP_Error True on success, WP_Error on database failure
     */
    private function log_error($template_slug, $to, $error) {
        global $wpdb;
        $table = $wpdb->prefix . 'mailbridge_logs';

        $result = $wpdb->insert(
            $table,
            [
                'template_slug' => $template_slug,
                'recipient' => $to,
                'subject' => '',
                'status' => 'failed',
                'error_message' => $error,
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error(
                'log_insert_failed',
                sprintf(__('Failed to log error: %s', 'wp-mail-bridge'), $wpdb->last_error),
                ['template_slug' => $template_slug, 'recipient' => $to, 'original_error' => $error]
            );
        }

        return true;
    }
}
