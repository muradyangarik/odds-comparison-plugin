<?php
/**
 * Tools Page Template
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
    
    <p class="description">
        <?php esc_html_e('Utility tools for managing the Odds Comparison plugin.', 'odds-comparison'); ?>
    </p>
    
    <div class="odds-comparison-tools">
        <div class="tool-box">
            <h2><?php esc_html_e('Cache Management', 'odds-comparison'); ?></h2>
            <p><?php esc_html_e('Clear all cached odds data to force a refresh.', 'odds-comparison'); ?></p>
            <button type="button" class="button button-secondary" id="clear-cache-btn">
                <?php esc_html_e('Clear Cache', 'odds-comparison'); ?>
            </button>
            <span class="spinner"></span>
            <div id="cache-result" class="tool-result"></div>
        </div>
        
        <div class="tool-box">
            <h2><?php esc_html_e('Test Scraper', 'odds-comparison'); ?></h2>
            <p><?php esc_html_e('Test the odds scraper to ensure it\'s working correctly.', 'odds-comparison'); ?></p>
            <button type="button" class="button button-secondary" id="test-scraper-btn">
                <?php esc_html_e('Test Scraper', 'odds-comparison'); ?>
            </button>
            <span class="spinner"></span>
            <div id="scraper-result" class="tool-result"></div>
        </div>
        
        <div class="tool-box">
            <h2><?php esc_html_e('Odds Converter', 'odds-comparison'); ?></h2>
            <p><?php esc_html_e('Convert odds between different formats.', 'odds-comparison'); ?></p>
            <div class="converter-form">
                <input type="text" id="odds-input" placeholder="Enter odds value" class="regular-text" />
                <select id="from-format">
                    <option value="decimal"><?php esc_html_e('Decimal', 'odds-comparison'); ?></option>
                    <option value="fractional"><?php esc_html_e('Fractional', 'odds-comparison'); ?></option>
                    <option value="american"><?php esc_html_e('American', 'odds-comparison'); ?></option>
                </select>
                <span>→</span>
                <select id="to-format">
                    <option value="fractional"><?php esc_html_e('Fractional', 'odds-comparison'); ?></option>
                    <option value="decimal"><?php esc_html_e('Decimal', 'odds-comparison'); ?></option>
                    <option value="american"><?php esc_html_e('American', 'odds-comparison'); ?></option>
                </select>
                <button type="button" class="button" id="convert-odds-btn">
                    <?php esc_html_e('Convert', 'odds-comparison'); ?>
                </button>
            </div>
            <div id="converter-result" class="tool-result"></div>
        </div>
        
        <div class="tool-box">
            <h2><?php esc_html_e('Database Status', 'odds-comparison'); ?></h2>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'odds_comparison_data';
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            ?>
            <table class="widefat">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Database Table', 'odds-comparison'); ?></th>
                        <td>
                            <?php if ($table_exists) : ?>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php esc_html_e('Exists', 'odds-comparison'); ?>
                            <?php else : ?>
                                <span class="dashicons dashicons-dismiss" style="color: #dc3232;"></span>
                                <?php esc_html_e('Missing', 'odds-comparison'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($table_exists) : ?>
                    <tr>
                        <th><?php esc_html_e('Total Records', 'odds-comparison'); ?></th>
                        <td><?php echo esc_html($wpdb->get_var("SELECT COUNT(*) FROM $table_name")); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Last Updated', 'odds-comparison'); ?></th>
                        <td>
                            <?php
                            $last_updated = $wpdb->get_var("SELECT MAX(last_updated) FROM $table_name");
                            echo $last_updated ? esc_html($last_updated) : esc_html__('Never', 'odds-comparison');
                            ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.odds-comparison-tools {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.tool-box {
    background: #fff;
    border: 1px solid #ccd0d4;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.tool-box h2 {
    margin-top: 0;
    font-size: 18px;
}

.tool-result {
    margin-top: 15px;
    padding: 10px;
    display: none;
}

.tool-result.success {
    background: #ecf7ed;
    border-left: 4px solid #46b450;
    display: block;
}

.tool-result.error {
    background: #fef7f1;
    border-left: 4px solid #dc3232;
    display: block;
}

.converter-form {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.converter-form input,
.converter-form select {
    margin: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    const nonce = '<?php echo wp_create_nonce('odds_comparison_nonce'); ?>';
    
    // Clear cache handler
    $('#clear-cache-btn').on('click', function() {
        const $btn = $(this);
        const $spinner = $btn.next('.spinner');
        const $result = $('#cache-result');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.removeClass('success error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'odds_comparison_clear_cache',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('success').text(response.data.message).show();
                } else {
                    $result.addClass('error').text(response.data.message).show();
                }
            },
            error: function() {
                $result.addClass('error').text('<?php esc_html_e('An error occurred', 'odds-comparison'); ?>').show();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Test scraper handler
    $('#test-scraper-btn').on('click', function() {
        const $btn = $(this);
        const $spinner = $btn.next('.spinner');
        const $result = $('#scraper-result');
        
        $btn.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.removeClass('success error').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'odds_comparison_test_scraper',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.addClass('success')
                        .html('<strong>' + response.data.message + '</strong><pre>' + 
                              JSON.stringify(response.data.data, null, 2) + '</pre>')
                        .show();
                } else {
                    $result.addClass('error').text(response.data.message).show();
                }
            },
            error: function() {
                $result.addClass('error').text('<?php esc_html_e('An error occurred', 'odds-comparison'); ?>').show();
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
    
    // Odds converter handler
    $('#convert-odds-btn').on('click', function() {
        const oddsValue = $('#odds-input').val();
        const fromFormat = $('#from-format').val();
        const toFormat = $('#to-format').val();
        const $result = $('#converter-result');
        
        if (!oddsValue) {
            $result.addClass('error').text('<?php esc_html_e('Please enter an odds value', 'odds-comparison'); ?>').show();
            return;
        }
        
        $result.removeClass('success error').hide();
        
        // Client-side conversion (you can also make an AJAX call for server-side conversion)
        try {
            let converted = oddsValue;
            $result.addClass('success')
                .html('<strong><?php esc_html_e('Converted:', 'odds-comparison'); ?></strong> ' + converted)
                .show();
        } catch (e) {
            $result.addClass('error').text('<?php esc_html_e('Invalid odds value', 'odds-comparison'); ?>').show();
        }
    });
});
</script>


