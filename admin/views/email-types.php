<?php
/**
 * Email types view
 *
 * @package WP_MailBridge
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get registered email types
$email_types = MailBridge_Registry::get_email_types_from_db();
?>

<div class="wrap">
    <h1><?php echo esc_html__('Registered Email Types', 'wp-mail-bridge'); ?></h1>

    <p class="description">
        <?php echo esc_html__('These are email types registered by plugins and themes. Developers can register new types using the mailbridge_register_email_type() function.', 'wp-mail-bridge'); ?>
    </p>

    <?php if (empty($email_types)): ?>
        <div class="mailbridge-empty-state">
            <p><?php echo esc_html__('No email types registered yet.', 'wp-mail-bridge'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name column-primary">
                        <?php echo esc_html__('Type ID', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Name', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Description', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Plugin/Module', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Expected Languages', 'wp-mail-bridge'); ?>
                    </th>
                    <th scope="col" class="manage-column">
                        <?php echo esc_html__('Variables', 'wp-mail-bridge'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($email_types as $type_id => $type_data): ?>
                    <tr>
                        <td class="column-name column-primary" data-colname="<?php echo esc_attr__('Type ID', 'wp-mail-bridge'); ?>">
                            <strong><code><?php echo esc_html($type_id); ?></code></strong>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Name', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($type_data['name']); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Description', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($type_data['description']); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Plugin/Module', 'wp-mail-bridge'); ?>">
                            <?php echo esc_html($type_data['plugin']); ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Expected Languages', 'wp-mail-bridge'); ?>">
                            <?php
                            if (!empty($type_data['languages']) && is_array($type_data['languages'])):
                                echo '<code>' . esc_html(implode(', ', $type_data['languages'])) . '</code>';
                            else:
                                echo '<em>' . esc_html__('All languages', 'wp-mail-bridge') . '</em>';
                            endif;
                            ?>
                        </td>
                        <td data-colname="<?php echo esc_attr__('Variables', 'wp-mail-bridge'); ?>">
                            <?php if (!empty($type_data['variables'])): ?>
                                <button type="button" class="button button-small mailbridge-show-variables" data-type-id="<?php echo esc_attr($type_id); ?>">
                                    <?php echo esc_html__('Show Variables', 'wp-mail-bridge'); ?>
                                </button>
                                <div id="variables-<?php echo esc_attr($type_id); ?>" style="display:none; margin-top:10px;">
                                    <ul style="margin: 0; list-style: disc inside;">
                                        <?php foreach ($type_data['variables'] as $var_key => $var_label): ?>
                                            <li>
                                                <code>{{<?php echo esc_html($var_key); ?>}}</code> -
                                                <?php echo esc_html($var_label); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <em><?php echo esc_html__('No variables', 'wp-mail-bridge'); ?></em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mailbridge-info-box" style="margin-top: 30px; padding: 20px; background: #fff; border: 1px solid #c3c4c7;">
            <h2><?php echo esc_html__('For Developers', 'wp-mail-bridge'); ?></h2>

            <h3><?php echo esc_html__('Basic Usage', 'wp-mail-bridge'); ?></h3>
            <p><?php echo esc_html__('To register a new email type in your plugin or theme, use the following code:', 'wp-mail-bridge'); ?></p>
            <pre style="background: #f0f0f1; padding: 15px; overflow-x: auto;"><code>add_action('mailbridge_register_email_types', function() {
    mailbridge_register_email_type('my_email_type', array(
        'name' => 'My Email Type',
        'description' => 'Description of this email',
        'variables' => array(
            'variable_name' => 'Variable Description',
            'another_var' => 'Another Variable',
        ),
        'plugin' => 'My Plugin Name',
        'default_subject' => 'Email Subject with {{variable_name}}',
        'default_content' => '&lt;p&gt;Email content with {{another_var}}&lt;/p&gt;',
        'languages' => array('en', 'fr'), // Optional: specify expected languages
    ));
});</code></pre>

            <h3 style="margin-top: 20px;"><?php echo esc_html__('Multi-Language Support', 'wp-mail-bridge'); ?></h3>
            <p><?php echo esc_html__('You can provide different default content and preview values for each language:', 'wp-mail-bridge'); ?></p>
            <pre style="background: #f0f0f1; padding: 15px; overflow-x: auto;"><code>add_action('mailbridge_register_email_types', function() {
    mailbridge_register_email_type('user_welcome', array(
        'name' => 'User Welcome Email',
        'description' => 'Welcome email sent to new users',
        'variables' => array(
            'user_name' => 'User full name',
            'site_name' => 'Website name',
            'login_url' => 'Login page URL',
        ),
        'plugin' => 'My Plugin',

        // Multi-language subject
        'default_subject' => array(
            'en' => 'Welcome to {{site_name}}!',
            'fr' => 'Bienvenue sur {{site_name}} !',
        ),

        // Multi-language content
        'default_content' => array(
            'en' => '&lt;p&gt;Hello {{user_name}},&lt;/p&gt;&lt;p&gt;Welcome to our site!&lt;/p&gt;',
            'fr' => '&lt;p&gt;Bonjour {{user_name}},&lt;/p&gt;&lt;p&gt;Bienvenue sur notre site !&lt;/p&gt;',
        ),

        // Multi-language preview values
        'preview_values' => array(
            'user_name' => array(
                'en' => 'John Doe',
                'fr' => 'Jean Dupont',
            ),
            'site_name' => 'My Website', // Same for all languages
            'login_url' => array(
                'en' => '&lt;a href="https://example.com/login"&gt;Login here&lt;/a&gt;',
                'fr' => '&lt;a href="https://example.com/login"&gt;Se connecter&lt;/a&gt;',
            ),
        ),

        'languages' => array('en', 'fr'),
    ));
});</code></pre>

            <p style="margin-top: 15px;">
                <strong><?php echo esc_html__('Note:', 'wp-mail-bridge'); ?></strong>
                <?php echo esc_html__('When a language is selected, the editor automatically loads the corresponding default_subject, default_content, and preview_values. You can mix simple strings (same for all languages) and language-specific arrays.', 'wp-mail-bridge'); ?>
            </p>
        </div>
    <?php endif; ?>
</div>
