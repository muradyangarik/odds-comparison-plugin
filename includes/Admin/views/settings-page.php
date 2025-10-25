<?php
/**
 * Settings Page Template
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap odds-comparison-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="odds-comparison-header">
        <p class="description">
            <?php esc_html_e('Configure the Advanced Odds Comparison plugin settings. This plugin uses The Odds API for real-time odds data.', 'odds-comparison'); ?>
        </p>
    </div>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('odds_comparison_settings');
        do_settings_sections('odds-comparison');
        submit_button(__('Save Settings', 'odds-comparison'));
        ?>
    </form>
    
    <hr />
    
    <div class="odds-comparison-info">
        <h2><?php esc_html_e('Plugin Information', 'odds-comparison'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th><?php esc_html_e('Version', 'odds-comparison'); ?></th>
                    <td><?php echo esc_html(ODDS_COMPARISON_VERSION); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Cached Items', 'odds-comparison'); ?></th>
                    <td id="cached-items-count">
                        <?php
                        global $wpdb;
                        $count = $wpdb->get_var(
                            "SELECT COUNT(*) FROM {$wpdb->options} 
                             WHERE option_name LIKE '_transient_odds_comparison_%'"
                        );
                        echo esc_html($count);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Database Records', 'odds-comparison'); ?></th>
                    <td>
                        <?php
                        $table_name = $wpdb->prefix . 'odds_comparison_data';
                        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                        echo esc_html($count);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>


