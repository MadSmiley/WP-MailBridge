<?php
/**
 * Email logs view
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get logs from database
global $wpdb;
$table = $wpdb->prefix . 'mailbridge_logs';

// Pagination
$per_page = 50;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Get total count
$total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $table");
$total_pages = ceil($total_logs / $per_page);

// Get logs
$logs = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table ORDER BY sent_at DESC LIMIT %d OFFSET %d",
    $per_page,
    $offset
));
?>

<div class="wrap">
    <h1><?php echo esc_html__('Email Logs', 'wp-mail-bridge'); ?></h1>

    <p class="description">
        <?php echo esc_html__('History of all emails sent through MailBridge.', 'wp-mail-bridge'); ?>
    </p>

    <?php if (empty($logs)): ?>
        <div class="mailbridge-empty-state">
            <p><?php echo esc_html__('No emails sent yet.', 'wp-mail-bridge'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Date/Time', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column column-primary">
                        <?php echo esc_html__('Template', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Recipient', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Subject', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Status', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Error', 'wp-mail-bridge'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td data-colname="<?php echo esc_attr__('Date/Time', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->sent_at))); ?>
                        </td>
                        <td class="column-primary" data-colname="<?php echo esc_attr__('Template', 'wp-mail-bridge'); ?>">
                            <code><?php echo esc_html($log->template_slug); ?></code>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Recipient', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($log->recipient); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Subject', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($log->subject); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Status', 'wp-mail-bridge'); ?>">
                            <?php if ($log->status === 'sent'): ?>
                                <span class="mailbridge-status-success" style="color: #00a32a; font-weight: 600;">
                                    ✓ <?php echo esc_html__('Sent', 'wp-mail-bridge'); ?>
                                </span>
                            <?php else: ?>
                                <span class="mailbridge-status-failed" style="color: #d63638; font-weight: 600;">
                                    ✗ <?php echo esc_html__('Failed', 'wp-mail-bridge'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Error', 'wp-mail-bridge'); ?>">
                            <?php if (!empty($log->error_message)): ?>
                                <span style="color: #d63638;"><?php echo esc_html($log->error_message); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(esc_html__('%s items', 'wp-mail-bridge'), number_format_i18n($total_logs)); ?>
                    </span>
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page,
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
