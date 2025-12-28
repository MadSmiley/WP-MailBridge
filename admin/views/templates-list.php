<?php
/**
 * Templates list view
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get templates from database
global $wpdb;
$table = $wpdb->prefix . 'mailbridge_templates';
$templates = $wpdb->get_results("SELECT * FROM $table ORDER BY template_name ASC");

// Get registered email types to display variation names
$email_types = MailBridge_Registry::get_email_types_from_db();

// Handle messages
$message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : '';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php echo esc_html__('Email Templates', 'wp-mail-bridge'); ?>
    </h1>
    <a href="<?php echo admin_url('admin.php?page=mailbridge-edit-template'); ?>" class="page-title-action">
        <?php echo esc_html__('Add New', 'wp-mail-bridge'); ?>
    </a>

    <hr class="wp-header-end">

    <?php if ($message === 'created'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Template created successfully.', 'wp-mail-bridge'); ?></p>
        </div>
    <?php elseif ($message === 'updated'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Template updated successfully.', 'wp-mail-bridge'); ?></p>
        </div>
    <?php elseif ($message === 'deleted'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Template deleted successfully.', 'wp-mail-bridge'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($templates)): ?>
        <div class="mailbridge-empty-state">
            <p><?php echo esc_html__('No email templates found. Create your first template to get started!', 'wp-mail-bridge'); ?></p>
            <a href="<?php echo admin_url('admin.php?page=mailbridge-edit-template'); ?>" class="button button-primary">
                <?php echo esc_html__('Create Template', 'wp-mail-bridge'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name column-primary">
                        <?php echo esc_html__('Template Name', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Slug', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Language', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Variation', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Plugin', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Status', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Actions', 'wp-mail-bridge'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $template): ?>
                    <tr>
                        <td class="column-name column-primary" data-colname="<?php echo esc_attr__('Template Name', 'wp-mail-bridge'); ?>">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=mailbridge-edit-template&id=' . $template->id); ?>">
                                    <?php echo esc_html($template->template_name); ?>
                                </a>
                            </strong>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Slug', 'wp-mail-bridge'); ?>">
                            <code><?php echo esc_html($template->template_slug); ?></code>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Language', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($template->language); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Variation', 'wp-mail-bridge'); ?>">
                            <?php
                            if (empty($template->variation)) {
                                echo '<em>' . esc_html__('Générique', 'wp-mail-bridge') . '</em>';
                            } else {
                                // Try to get variation name from email type
                                $variation_name = $template->variation;
                                if (isset($email_types[$template->template_slug]['variations'][$template->variation])) {
                                    $variation_name = $email_types[$template->template_slug]['variations'][$template->variation];
                                }
                                echo esc_html($variation_name);
                            }
                            ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Plugin', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($template->plugin_name); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Status', 'wp-mail-bridge'); ?>">
                            <?php if ($template->status === 'active'): ?>
                                <span class="mailbridge-status-active"><?php echo esc_html__('Active', 'wp-mail-bridge'); ?></span>
                            <?php else: ?>
                                <span class="mailbridge-status-inactive"><?php echo esc_html__('Inactive', 'wp-mail-bridge'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Actions', 'wp-mail-bridge'); ?>">
                            <a href="<?php echo admin_url('admin.php?page=mailbridge-edit-template&id=' . $template->id); ?>" class="button button-small">
                                <?php echo esc_html__('Edit', 'wp-mail-bridge'); ?>
                            </a>
                            <button type="button" class="button button-small mailbridge-delete-template" data-id="<?php echo esc_attr($template->id); ?>">
                                <?php echo esc_html__('Delete', 'wp-mail-bridge'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <!-- Hidden delete form -->
    <form method="post" id="mailbridge-delete-form" style="display:none;">
        <?php wp_nonce_field('mailbridge_save_template', 'mailbridge_nonce'); ?>
        <input type="hidden" name="mailbridge_action" value="delete_template">
        <input type="hidden" name="template_id" id="delete-template-id">
    </form>
</div>
