<?php
/**
 * Settings view
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle settings save
if (isset($_POST['mailbridge_save_settings'])) {
    check_admin_referer('mailbridge_settings');

    update_option('mailbridge_log_retention_days', intval($_POST['log_retention_days']));
    update_option('mailbridge_default_language', sanitize_text_field($_POST['default_language']));
    update_option('mailbridge_enable_logging', isset($_POST['enable_logging']) ? '1' : '0');

    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'wp-mail-bridge') . '</p></div>';
}

// Get current settings
$log_retention_days = get_option('mailbridge_log_retention_days', 30);
$default_language = get_option('mailbridge_default_language', 'en');
$enable_logging = get_option('mailbridge_enable_logging', '1');

// Get plugin info
global $wpdb;
$total_templates = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mailbridge_templates");
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mailbridge_logs");
$total_types = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mailbridge_email_types");
?>

<div class="wrap">
    <h1><?php echo esc_html__('MailBridge Settings', 'wp-mail-bridge'); ?></h1>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Settings Form -->
        <div>
            <form method="post" action="">
                <?php wp_nonce_field('mailbridge_settings'); ?>
                <input type="hidden" name="mailbridge_save_settings" value="1">

                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="enable_logging"><?php echo esc_html__('Enable Logging', 'wp-mail-bridge'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="enable_logging" id="enable_logging" value="1"
                                           <?php checked($enable_logging, '1'); ?>>
                                    <?php echo esc_html__('Log all sent emails', 'wp-mail-bridge'); ?>
                                </label>
                                <p class="description">
                                    <?php echo esc_html__('Keep a record of all emails sent through MailBridge.', 'wp-mail-bridge'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="log_retention_days"><?php echo esc_html__('Log Retention Period', 'wp-mail-bridge'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="log_retention_days" id="log_retention_days"
                                       class="small-text" min="1" max="365"
                                       value="<?php echo esc_attr($log_retention_days); ?>">
                                <?php echo esc_html__('days', 'wp-mail-bridge'); ?>
                                <p class="description">
                                    <?php echo esc_html__('Automatically delete logs older than this many days. (1-365 days)', 'wp-mail-bridge'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="default_language"><?php echo esc_html__('Default Language', 'wp-mail-bridge'); ?></label>
                            </th>
                            <td>
                                <select name="default_language" id="default_language" class="regular-text">
                                    <option value="en" <?php selected($default_language, 'en'); ?>>English</option>
                                    <option value="fr" <?php selected($default_language, 'fr'); ?>>Français</option>
                                    <option value="es" <?php selected($default_language, 'es'); ?>>Español</option>
                                    <option value="de" <?php selected($default_language, 'de'); ?>>Deutsch</option>
                                    <option value="it" <?php selected($default_language, 'it'); ?>>Italiano</option>
                                </select>
                                <p class="description">
                                    <?php echo esc_html__('Default language for email templates when not specified.', 'wp-mail-bridge'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary"
                           value="<?php echo esc_attr__('Save Settings', 'wp-mail-bridge'); ?>">
                </p>
            </form>

            <hr>

            <h2><?php echo esc_html__('Developer Documentation', 'wp-mail-bridge'); ?></h2>

            <h3><?php echo esc_html__('Sending an Email', 'wp-mail-bridge'); ?></h3>
            <p><?php echo esc_html__('Use the following function to send an email from your plugin or theme:', 'wp-mail-bridge'); ?></p>
            <pre style="background: #f0f0f1; padding: 15px; overflow-x: auto;"><code>mailbridge_send('template_slug', array(
    'to' => 'recipient@example.com',
    'variable_name' => 'value',
    'another_variable' => 'another value',
), '', 'en');</code></pre>

            <h3><?php echo esc_html__('Registering an Email Type', 'wp-mail-bridge'); ?></h3>
            <p><?php echo esc_html__('Register email types so admins can customize them:', 'wp-mail-bridge'); ?></p>
            <pre style="background: #f0f0f1; padding: 15px; overflow-x: auto;"><code>add_action('mailbridge_register_email_types', function() {
    mailbridge_register_email_type('my_email', array(
        'name' => 'My Custom Email',
        'description' => 'Description of the email',
        'variables' => array(
            'user_name' => 'User full name',
            'custom_var' => 'Custom variable description',
        ),
        'plugin' => 'My Plugin Name',
        'default_subject' => 'Hello {{user_name}}!',
        'default_content' => '&lt;p&gt;Content with {{custom_var}}&lt;/p&gt;',
    ));
});</code></pre>
        </div>

        <!-- Info Sidebar -->
        <div>
            <div class="mailbridge-info-widget" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px;">
                <h2><?php echo esc_html__('Statistics', 'wp-mail-bridge'); ?></h2>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f1;">
                        <strong><?php echo esc_html__('Templates:', 'wp-mail-bridge'); ?></strong>
                        <span style="float: right;"><?php echo esc_html(number_format_i18n($total_templates)); ?></span>
                    </li>
                    <li style="padding: 10px 0; border-bottom: 1px solid #f0f0f1;">
                        <strong><?php echo esc_html__('Email Types:', 'wp-mail-bridge'); ?></strong>
                        <span style="float: right;"><?php echo esc_html(number_format_i18n($total_types)); ?></span>
                    </li>
                    <li style="padding: 10px 0;">
                        <strong><?php echo esc_html__('Emails Sent:', 'wp-mail-bridge'); ?></strong>
                        <span style="float: right;"><?php echo esc_html(number_format_i18n($total_logs)); ?></span>
                    </li>
                </ul>
            </div>

            <div class="mailbridge-info-widget" style="background: #fff; border: 1px solid #c3c4c7; padding: 20px; margin-top: 20px;">
                <h2><?php echo esc_html__('About MailBridge', 'wp-mail-bridge'); ?></h2>
                <p>
                    <strong><?php echo esc_html__('Version:', 'wp-mail-bridge'); ?></strong>
                    <?php echo esc_html(MAILBRIDGE_VERSION); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Author:', 'wp-mail-bridge'); ?></strong>
                    <a href="https://www.linkedin.com/in/germain-belacel/" target="_blank">MadSmiley</a>
                </p>
                <p>
                    <a href="https://github.com/MadSmiley/WP-MailBridge" target="_blank" class="button">
                        <?php echo esc_html__('Documentation', 'wp-mail-bridge'); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>
