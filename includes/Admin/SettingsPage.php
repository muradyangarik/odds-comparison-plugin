<?php
/**
 * Settings Page Class
 *
 * Handles the main settings page rendering and settings registration.
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

namespace OddsComparison\Admin;

/**
 * Class SettingsPage
 */
class SettingsPage {
    
    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        // Register settings sections.
        add_settings_section(
            'odds_comparison_general_section',
            __('General Settings', 'odds-comparison'),
            [$this, 'render_general_section'],
            'odds-comparison'
        );
        
        add_settings_section(
            'odds_comparison_cache_section',
            __('Cache Settings', 'odds-comparison'),
            [$this, 'render_cache_section'],
            'odds-comparison'
        );
        
        // Register settings fields.
        register_setting('odds_comparison_settings', 'odds_comparison_default_format');
        register_setting('odds_comparison_settings', 'odds_comparison_default_market');
        register_setting('odds_comparison_settings', 'odds_comparison_cache_duration');
        register_setting('odds_comparison_settings', 'odds_comparison_update_frequency');
        
        // General settings fields.
        add_settings_field(
            'odds_comparison_default_format',
            __('Default Odds Format', 'odds-comparison'),
            [$this, 'render_default_format_field'],
            'odds-comparison',
            'odds_comparison_general_section'
        );
        
        add_settings_field(
            'odds_comparison_default_market',
            __('Default Market Type', 'odds-comparison'),
            [$this, 'render_default_market_field'],
            'odds-comparison',
            'odds_comparison_general_section'
        );
        
        // Cache settings fields.
        add_settings_field(
            'odds_comparison_cache_duration',
            __('Cache Duration (seconds)', 'odds-comparison'),
            [$this, 'render_cache_duration_field'],
            'odds-comparison',
            'odds_comparison_cache_section'
        );
        
        add_settings_field(
            'odds_comparison_update_frequency',
            __('Update Frequency', 'odds-comparison'),
            [$this, 'render_update_frequency_field'],
            'odds-comparison',
            'odds_comparison_cache_section'
        );
    }
    
    /**
     * Render settings page.
     *
     * @return void
     */
    public function render_settings_page() {
        // Ensure settings are initialized
        $this->ensure_settings_initialized();
        require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Admin/views/settings-page.php';
    }
    
    /**
     * Ensure settings are properly initialized.
     *
     * @return void
     */
    private function ensure_settings_initialized() {
        $default_settings = [
            'odds_comparison_default_format' => 'decimal',
            'odds_comparison_cache_duration' => 300,
            'odds_comparison_update_frequency' => 'five_minutes'
        ];
        
        foreach ($default_settings as $option_name => $default_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $default_value);
            }
        }
    }
    
    /**
     * Render general section description.
     *
     * @return void
     */
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure general plugin settings. The plugin uses The Odds API for real-time odds data.', 'odds-comparison') . '</p>';
    }
    
    /**
     * Render cache section description.
     *
     * @return void
     */
    public function render_cache_section() {
        echo '<p>' . esc_html__('Configure caching and update frequency settings.', 'odds-comparison') . '</p>';
    }
    
    /**
     * Render default format field.
     *
     * @return void
     */
    public function render_default_format_field() {
        $value = get_option('odds_comparison_default_format', 'decimal');
        ?>
        <select name="odds_comparison_default_format" id="odds_comparison_default_format">
            <option value="decimal" <?php selected($value, 'decimal'); ?>>
                <?php esc_html_e('Decimal (e.g., 2.50)', 'odds-comparison'); ?>
            </option>
            <option value="fractional" <?php selected($value, 'fractional'); ?>>
                <?php esc_html_e('Fractional (e.g., 3/2)', 'odds-comparison'); ?>
            </option>
            <option value="american" <?php selected($value, 'american'); ?>>
                <?php esc_html_e('American (e.g., +150)', 'odds-comparison'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the default format for displaying odds.', 'odds-comparison'); ?>
        </p>
        <?php
    }
    
    /**
     * Render default market field.
     *
     * @return void
     */
    public function render_default_market_field() {
        $value = get_option('odds_comparison_default_market', '');
        $markets = get_option('odds_comparison_markets', []);
        ?>
        <select name="odds_comparison_default_market" id="odds_comparison_default_market">
            <option value="" <?php selected($value, ''); ?>>
                <?php esc_html_e('Use Block Setting (Default)', 'odds-comparison'); ?>
            </option>
            <?php foreach ($markets as $market_id => $market) : ?>
                <?php if ($market['enabled']) : ?>
                    <option value="<?php echo esc_attr($market_id); ?>" <?php selected($value, $market_id); ?>>
                        <?php echo esc_html($market['label']); ?>
                    </option>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Default market type for all blocks. Leave empty to use individual block settings.', 'odds-comparison'); ?>
        </p>
        <?php
    }
    
    
    /**
     * Render cache duration field.
     *
     * @return void
     */
    public function render_cache_duration_field() {
        $value = get_option('odds_comparison_cache_duration', 300);
        ?>
        <input type="number" 
               name="odds_comparison_cache_duration" 
               id="odds_comparison_cache_duration" 
               value="<?php echo esc_attr($value); ?>" 
               min="60" 
               max="3600" 
               step="60" />
        <p class="description">
            <?php esc_html_e('How long to cache odds data (in seconds). Default: 300 (5 minutes).', 'odds-comparison'); ?>
        </p>
        <?php
    }
    
    /**
     * Render update frequency field.
     *
     * @return void
     */
    public function render_update_frequency_field() {
        $value = get_option('odds_comparison_update_frequency', 'five_minutes');
        ?>
        <select name="odds_comparison_update_frequency" id="odds_comparison_update_frequency">
            <option value="five_minutes" <?php selected($value, 'five_minutes'); ?>>
                <?php esc_html_e('Every 5 Minutes', 'odds-comparison'); ?>
            </option>
            <option value="fifteen_minutes" <?php selected($value, 'fifteen_minutes'); ?>>
                <?php esc_html_e('Every 15 Minutes', 'odds-comparison'); ?>
            </option>
            <option value="hourly" <?php selected($value, 'hourly'); ?>>
                <?php esc_html_e('Hourly', 'odds-comparison'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('How often to automatically update odds data.', 'odds-comparison'); ?>
        </p>
        <?php
    }
}

