<?php
/**
 * Template edit view
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get template if editing
$template_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$template = null;

if ($template_id > 0) {
    global $wpdb;
    $table = $wpdb->prefix . 'mailbridge_templates';
    $template = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $template_id));
}

// Get registered email types for reference
$email_types = MailBridge_Registry::get_email_types_from_db();

// Default values
$template_name = $template ? $template->template_name : '';
$template_slug = $template ? $template->template_slug : '';
$subject = $template ? $template->subject : '';
$content = $template ? $template->content : '';
$language = $template ? $template->language : 'en';
$plugin_name = $template ? $template->plugin_name : '';
$status = $template ? $template->status : 'active';

// Get available languages
$languages = array(
    'en' => 'English',
    'fr' => 'Français',
    'es' => 'Español',
    'de' => 'Deutsch',
    'it' => 'Italiano',
);
?>

<div class="wrap">
    <h1>
        <?php echo $template_id ? esc_html__('Edit Template', 'wp-mail-bridge') : esc_html__('Add New Template', 'wp-mail-bridge'); ?>
    </h1>

    <form method="post" action="<?php echo admin_url('admin.php'); ?>">
        <?php wp_nonce_field('mailbridge_save_template', 'mailbridge_nonce'); ?>
        <input type="hidden" name="mailbridge_action" value="save_template">
        <input type="hidden" name="template_id" value="<?php echo esc_attr($template_id); ?>">

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="template_name"><?php echo esc_html__('Template Name', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" name="template_name" id="template_name" class="regular-text"
                               value="<?php echo esc_attr($template_name); ?>" required>
                        <p class="description"><?php echo esc_html__('A descriptive name for this email template.', 'wp-mail-bridge'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="template_slug"><?php echo esc_html__('Template Slug', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" name="template_slug" id="template_slug" class="regular-text"
                               value="<?php echo esc_attr($template_slug); ?>" required>
                        <p class="description">
                            <?php echo esc_html__('Unique identifier used in code (e.g., "welcome_email"). Use only lowercase letters, numbers, and underscores.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="email_type_reference"><?php echo esc_html__('Email Type Reference', 'wp-mail-bridge'); ?></label>
                    </th>
                    <td>
                        <select id="email_type_reference" class="regular-text">
                            <option value=""><?php echo esc_html__('-- Select a registered email type --', 'wp-mail-bridge'); ?></option>
                            <?php foreach ($email_types as $type_id => $type_data): ?>
                                <option value="<?php echo esc_attr($type_id); ?>"
                                        data-subject="<?php echo esc_attr($type_data['default_subject'] ?? ''); ?>"
                                        data-content="<?php echo esc_attr($type_data['default_content'] ?? ''); ?>"
                                        data-variables="<?php echo esc_attr(json_encode($type_data['variables'] ?? [])); ?>"
                                        data-plugin="<?php echo esc_attr($type_data['plugin'] ?? ''); ?>">
                                    <?php echo esc_html($type_data['name']); ?> (<?php echo esc_html($type_id); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Optional: Select a registered email type to auto-fill and see available variables.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="language"><?php echo esc_html__('Language', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <select name="language" id="language" class="regular-text" required>
                            <?php foreach ($languages as $code => $name): ?>
                                <option value="<?php echo esc_attr($code); ?>" <?php selected($language, $code); ?>>
                                    <?php echo esc_html($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Language for this template. You can create multiple versions of the same template in different languages.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="plugin_name"><?php echo esc_html__('Plugin/Module', 'wp-mail-bridge'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="plugin_name" id="plugin_name" class="regular-text"
                               value="<?php echo esc_attr($plugin_name); ?>">
                        <p class="description">
                            <?php echo esc_html__('Name of the plugin or module this template belongs to (for organization).', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="status"><?php echo esc_html__('Status', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <select name="status" id="status" class="regular-text" required>
                            <option value="active" <?php selected($status, 'active'); ?>>
                                <?php echo esc_html__('Active', 'wp-mail-bridge'); ?>
                            </option>
                            <option value="inactive" <?php selected($status, 'inactive'); ?>>
                                <?php echo esc_html__('Inactive', 'wp-mail-bridge'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php echo esc_html__('Only active templates can be used to send emails.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="subject"><?php echo esc_html__('Subject', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <input type="text" name="subject" id="subject" class="large-text"
                               value="<?php echo esc_attr($subject); ?>" required>
                        <p class="description">
                            <?php echo esc_html__('Email subject line. You can use variables like {{variable_name}}.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="content"><?php echo esc_html__('Content', 'wp-mail-bridge'); ?> *</label>
                    </th>
                    <td>
                        <?php
                        wp_editor($content, 'content', array(
                            'textarea_name' => 'content',
                            'textarea_rows' => 15,
                            'teeny' => false,
                            'media_buttons' => false,
                            'tinymce' => array(
                                'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,forecolor,backcolor,removeformat',
                            ),
                        ));
                        ?>
                        <p class="description">
                            <?php echo esc_html__('Email body content. HTML is supported. Use variables like {{variable_name}}.', 'wp-mail-bridge'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <div id="mailbridge-variables-info" style="display:none; margin-top: 20px; padding: 15px; background: #f0f0f1; border-left: 4px solid #2271b1;">
            <h3><?php echo esc_html__('Available Variables', 'wp-mail-bridge'); ?></h3>
            <div id="mailbridge-variables-list"></div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary"
                   value="<?php echo esc_attr__('Save Template', 'wp-mail-bridge'); ?>">
            <a href="<?php echo admin_url('admin.php?page=mailbridge'); ?>" class="button">
                <?php echo esc_html__('Cancel', 'wp-mail-bridge'); ?>
            </a>
        </p>
    </form>
</div>
