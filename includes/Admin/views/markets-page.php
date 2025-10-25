<?php
/**
 * Markets Management Page Template
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission.
if (isset($_POST['odds_comparison_markets_nonce']) && 
    wp_verify_nonce($_POST['odds_comparison_markets_nonce'], 'odds_comparison_save_markets')) {
    
    if (current_user_can('manage_odds_comparison')) {
        $markets = get_option('odds_comparison_markets', []);
        
        foreach ($markets as $market_id => &$market) {
            $market['enabled'] = isset($_POST['markets'][$market_id]['enabled']);
            $market['label'] = sanitize_text_field($_POST['markets'][$market_id]['label'] ?? $market['label']);
        }
        
        update_option('odds_comparison_markets', $markets);
        echo '<div class="notice notice-success"><p>' . esc_html__('Markets updated successfully.', 'odds-comparison') . '</p></div>';
    }
}

$markets = get_option('odds_comparison_markets', []);
?>

<div class="wrap odds-comparison-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <p class="description">
        <?php esc_html_e('Manage betting markets to display in odds comparisons.', 'odds-comparison'); ?>
    </p>
    
    <form method="post" action="">
        <?php wp_nonce_field('odds_comparison_save_markets', 'odds_comparison_markets_nonce'); ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="check-column">
                        <input type="checkbox" id="select-all-markets" />
                    </th>
                    <th><?php esc_html_e('Market ID', 'odds-comparison'); ?></th>
                    <th><?php esc_html_e('Display Label', 'odds-comparison'); ?></th>
                    <th><?php esc_html_e('Status', 'odds-comparison'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($markets as $market_id => $market) : ?>
                <tr>
                    <td class="check-column">
                        <input type="checkbox" 
                               name="markets[<?php echo esc_attr($market_id); ?>][enabled]" 
                               value="1" 
                               <?php checked($market['enabled'], true); ?> />
                    </td>
                    <td><code><?php echo esc_html($market_id); ?></code></td>
                    <td>
                        <input type="text" 
                               name="markets[<?php echo esc_attr($market_id); ?>][label]" 
                               value="<?php echo esc_attr($market['label']); ?>" 
                               class="regular-text" />
                    </td>
                    <td>
                        <?php if ($market['enabled']) : ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                            <?php esc_html_e('Enabled', 'odds-comparison'); ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                            <?php esc_html_e('Disabled', 'odds-comparison'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php submit_button(__('Save Markets', 'odds-comparison')); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#select-all-markets').on('change', function() {
        $('input[name*="[enabled]"]').prop('checked', this.checked);
    });
});
</script>


