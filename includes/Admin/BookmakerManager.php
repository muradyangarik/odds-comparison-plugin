<?php
/**
 * Bookmaker Manager Class
 *
 * Handles bookmaker management, settings, and configuration.
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

namespace OddsComparison\Admin;

/**
 * Class BookmakerManager
 *
 * Manages bookmaker settings and configuration.
 */
class BookmakerManager {
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_odds_comparison_save_bookmaker', [$this, 'save_bookmaker']);
        add_action('wp_ajax_odds_comparison_delete_bookmaker', [$this, 'delete_bookmaker']);
        add_action('wp_ajax_odds_comparison_test_api', [$this, 'test_api_connection']);
        add_action('wp_ajax_odds_comparison_toggle_bookmaker', [$this, 'toggle_bookmaker_visibility']);
    }
    
    /**
     * Add admin menu pages.
     *
     * @return void
     */
    public function add_admin_menu() {
        // Only add menu if it doesn't already exist
        if (!get_option('odds_comparison_menu_added')) {
            // Main menu page
            add_menu_page(
                __('Odds Comparison', 'odds-comparison'),
                __('Odds Comparison', 'odds-comparison'),
                'manage_options',
                'odds-comparison',
                [$this, 'admin_dashboard_page'],
                'dashicons-chart-line',
                30
            );
            
            // Bookmakers submenu
            add_submenu_page(
                'odds-comparison',
                __('Bookmakers', 'odds-comparison'),
                __('Bookmakers', 'odds-comparison'),
                'manage_options',
                'odds-comparison-bookmakers',
                [$this, 'bookmakers_page']
            );
            
            // Markets submenu
            add_submenu_page(
                'odds-comparison',
                __('Markets', 'odds-comparison'),
                __('Markets', 'odds-comparison'),
                'manage_options',
                'odds-comparison-markets',
                [$this, 'markets_page']
            );
            
            // Settings submenu
            add_submenu_page(
                'odds-comparison',
                __('Settings', 'odds-comparison'),
                __('Settings', 'odds-comparison'),
                'manage_options',
                'odds-comparison-settings',
                [$this, 'settings_page']
            );
            
            // Mark menu as added to prevent duplicates
            update_option('odds_comparison_menu_added', true);
        }
    }
    
    /**
     * Register plugin settings.
     *
     * @return void
     */
    public function register_settings() {
        // Register bookmakers setting
        register_setting('odds_comparison_bookmakers', 'odds_comparison_bookmakers', [
            'sanitize_callback' => [$this, 'sanitize_bookmakers'],
        ]);
        
        // Register markets setting
        register_setting('odds_comparison_markets', 'odds_comparison_markets', [
            'sanitize_callback' => [$this, 'sanitize_markets'],
        ]);
        
        // Register general settings
        register_setting('odds_comparison_settings', 'odds_comparison_api_key');
        register_setting('odds_comparison_settings', 'odds_comparison_default_format');
        register_setting('odds_comparison_settings', 'odds_comparison_cache_duration');
        register_setting('odds_comparison_settings', 'odds_comparison_auto_refresh');
    }
    
    /**
     * Admin dashboard page.
     *
     * @return void
     */
    public function admin_dashboard_page() {
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        $markets = get_option('odds_comparison_markets', []);
        $settings = get_option('odds_comparison_settings', []);
        
        // Get API status
        $api_status = $this->get_api_status();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Odds Comparison Dashboard', 'odds-comparison'); ?></h1>
            
            <div class="odds-comparison-dashboard">
                <!-- Status Cards -->
                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <h3><?php esc_html_e('API Status', 'odds-comparison'); ?></h3>
                        <div class="status-indicator <?php echo $api_status['connected'] ? 'connected' : 'disconnected'; ?>">
                            <?php echo $api_status['connected'] ? '✅ Connected' : '❌ Disconnected'; ?>
                        </div>
                        <p><?php echo esc_html($api_status['message']); ?></p>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3><?php esc_html_e('Bookmakers', 'odds-comparison'); ?></h3>
                        <div class="stat-number"><?php echo count($bookmakers); ?></div>
                        <p><?php esc_html_e('Configured bookmakers', 'odds-comparison'); ?></p>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3><?php esc_html_e('Markets', 'odds-comparison'); ?></h3>
                        <div class="stat-number"><?php echo count($markets); ?></div>
                        <p><?php esc_html_e('Available markets', 'odds-comparison'); ?></p>
                    </div>
                    
                    <div class="dashboard-card">
                        <h3><?php esc_html_e('Cache', 'odds-comparison'); ?></h3>
                        <div class="stat-number"><?php echo $settings['cache_duration'] ?? '5'; ?>m</div>
                        <p><?php esc_html_e('Cache duration', 'odds-comparison'); ?></p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h2><?php esc_html_e('Quick Actions', 'odds-comparison'); ?></h2>
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=odds-comparison-bookmakers'); ?>" class="button button-primary">
                            <?php esc_html_e('Manage Bookmakers', 'odds-comparison'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=odds-comparison-markets'); ?>" class="button">
                            <?php esc_html_e('Configure Markets', 'odds-comparison'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=odds-comparison-settings'); ?>" class="button">
                            <?php esc_html_e('Plugin Settings', 'odds-comparison'); ?>
                        </a>
                        <button type="button" class="button" id="test-api-connection">
                            <?php esc_html_e('Test API Connection', 'odds-comparison'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h2><?php esc_html_e('Recent Activity', 'odds-comparison'); ?></h2>
                    <div class="activity-log">
                        <p><?php esc_html_e('No recent activity to display.', 'odds-comparison'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .odds-comparison-dashboard {
            max-width: 1200px;
        }
        
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .dashboard-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dashboard-card h3 {
            margin-top: 0;
            color: #23282d;
        }
        
        .status-indicator {
            font-weight: bold;
            padding: 8px 12px;
            border-radius: 4px;
            display: inline-block;
            margin: 10px 0;
        }
        
        .status-indicator.connected {
            background: #d4edda;
            color: #155724;
        }
        
        .status-indicator.disconnected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #0073aa;
            margin: 10px 0;
        }
        
        .quick-actions {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .recent-activity {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .activity-log {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            min-height: 100px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#test-api-connection').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Testing...');
                
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'odds_comparison_test_api',
                        nonce: '<?php echo wp_create_nonce('odds_comparison_test_api'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✅ API Connection Successful!\n\n' + response.data.message);
                        } else {
                            alert('❌ API Connection Failed!\n\n' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('❌ Error testing API connection');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Test API Connection');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Bookmakers management page.
     *
     * @return void
     */
    public function bookmakers_page() {
        // Get bookmaker visibility settings
        $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'odds_comparison_bookmaker_visibility')) {
            $this->save_bookmaker_visibility_settings();
            $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
        }
        
        // Get API bookmakers
        $api_bookmakers = $this->get_api_bookmakers();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Bookmaker List', 'odds-comparison'); ?></h1>
            <p class="description">
                <?php esc_html_e('All bookmakers from The Odds API. Click to hide/show them on your website.', 'odds-comparison'); ?>
            </p>
            
            <?php if (empty($api_bookmakers)): ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('No bookmakers available from API. Please check your API connection in Settings.', 'odds-comparison'); ?></p>
                </div>
            <?php else: ?>
                <form method="post" id="bookmaker-list-form">
                    <?php wp_nonce_field('odds_comparison_bookmaker_visibility'); ?>
                    
                    <div class="bookmaker-list-container">
                        <div class="bookmaker-controls">
                            <button type="button" class="button" id="show-all-bookmakers">
                                <?php esc_html_e('Show All', 'odds-comparison'); ?>
                            </button>
                            <button type="button" class="button" id="hide-all-bookmakers">
                                <?php esc_html_e('Hide All', 'odds-comparison'); ?>
                            </button>
                            <span class="bookmaker-count">
                                <?php printf(esc_html__('%d bookmakers from API', 'odds-comparison'), count($api_bookmakers)); ?>
                            </span>
                        </div>
                        
                        <div class="bookmaker-list">
                            <?php foreach ($api_bookmakers as $bookmaker): 
                                $bookmaker_id = sanitize_title($bookmaker['title']);
                                $is_visible = isset($bookmaker_visibility[$bookmaker_id]) ? $bookmaker_visibility[$bookmaker_id] : true;
                            ?>
                            <div class="bookmaker-item">
                                <div class="bookmaker-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" 
                                               name="bookmaker_visibility[<?php echo esc_attr($bookmaker_id); ?>]" 
                                               value="1" 
                                               <?php checked($is_visible); ?>
                                               class="bookmaker-toggle-checkbox" />
                                        <span class="toggle-slider"></span>
                                    </label>
                                </div>
                                
                                <div class="bookmaker-details">
                                    <div class="bookmaker-name">
                                        <strong><?php echo esc_html($bookmaker['title']); ?></strong>
                                    </div>
                                    <div class="bookmaker-url">
                                        <a href="<?php echo esc_url($bookmaker['url']); ?>" target="_blank" rel="nofollow">
                                            <?php echo esc_html($bookmaker['url']); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="bookmaker-status">
                                    <span class="status-badge <?php echo $is_visible ? 'visible' : 'hidden'; ?>">
                                        <?php echo $is_visible ? '✅ Visible' : '❌ Hidden'; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="submit-section">
                            <input type="submit" name="submit" class="button button-primary button-large" value="<?php esc_attr_e('Save Changes', 'odds-comparison'); ?>" />
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <style>
        .bookmaker-list-container {
            max-width: 1000px;
        }
        
        .bookmaker-controls {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e1e1e1;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .bookmaker-controls .button {
            margin-right: 10px;
        }
        
        .bookmaker-count {
            color: #666;
            font-size: 14px;
        }
        
        .bookmaker-list {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .bookmaker-item {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #e1e1e1;
            transition: background-color 0.3s ease;
        }
        
        .bookmaker-item:last-child {
            border-bottom: none;
        }
        
        .bookmaker-item:hover {
            background: #f8f9fa;
        }
        
        .bookmaker-toggle {
            margin-right: 20px;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #0073aa;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }
        
        .bookmaker-details {
            flex: 1;
        }
        
        .bookmaker-name {
            font-size: 16px;
            color: #23282d;
            margin-bottom: 5px;
        }
        
        .bookmaker-url {
            font-size: 14px;
        }
        
        .bookmaker-url a {
            color: #0073aa;
            text-decoration: none;
        }
        
        .bookmaker-url a:hover {
            text-decoration: underline;
        }
        
        .bookmaker-status {
            margin-left: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .status-badge.visible {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-badge.hidden {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .submit-section {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            border: 1px solid #e1e1e1;
            text-align: center;
        }
        
        .submit-section .button-large {
            padding: 12px 24px;
            font-size: 16px;
        }
        
        @media (max-width: 768px) {
            .bookmaker-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .bookmaker-toggle {
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .bookmaker-status {
                margin-left: 0;
                align-self: flex-end;
            }
            
            .bookmaker-controls {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Show all bookmakers
            $('#show-all-bookmakers').on('click', function() {
                $('.bookmaker-toggle-checkbox').prop('checked', true);
                updateStatusBadges();
            });
            
            // Hide all bookmakers
            $('#hide-all-bookmakers').on('click', function() {
                $('.bookmaker-toggle-checkbox').prop('checked', false);
                updateStatusBadges();
            });
            
            // Individual toggle change
            $('.bookmaker-toggle-checkbox').on('change', function() {
                updateStatusBadges();
            });
            
            function updateStatusBadges() {
                $('.bookmaker-toggle-checkbox').each(function() {
                    var $item = $(this).closest('.bookmaker-item');
                    var $badge = $item.find('.status-badge');
                    
                    if ($(this).is(':checked')) {
                        $badge.removeClass('hidden').addClass('visible').text('✅ Visible');
                    } else {
                        $badge.removeClass('visible').addClass('hidden').text('❌ Hidden');
                    }
                });
            }
            
            // Initialize status badges
            updateStatusBadges();
        });
        </script>
        <?php
    }
    
    /**
     * Markets configuration page.
     *
     * @return void
     */
    public function markets_page() {
        $markets = get_option('odds_comparison_markets', $this->get_default_markets());
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'odds_comparison_markets')) {
            $this->save_market_settings();
            $markets = get_option('odds_comparison_markets', []);
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Configure Markets', 'odds-comparison'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('odds_comparison_markets'); ?>
                
                <div class="markets-configuration">
                    <p><?php esc_html_e('Configure which betting markets to display and their settings.', 'odds-comparison'); ?></p>
                    
                    <table class="form-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Market', 'odds-comparison'); ?></th>
                                <th><?php esc_html_e('Label', 'odds-comparison'); ?></th>
                                <th><?php esc_html_e('Enabled', 'odds-comparison'); ?></th>
                                <th><?php esc_html_e('Outcomes', 'odds-comparison'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($markets as $key => $market): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($market['name']); ?></strong>
                                    <br><code><?php echo esc_html($key); ?></code>
                                </td>
                                <td>
                                    <input type="text" name="markets[<?php echo esc_attr($key); ?>][label]" 
                                           value="<?php echo esc_attr($market['label']); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <label>
                                        <input type="checkbox" name="markets[<?php echo esc_attr($key); ?>][enabled]" 
                                               value="1" <?php checked($market['enabled']); ?> />
                                        <?php esc_html_e('Enable', 'odds-comparison'); ?>
                                    </label>
                                </td>
                                <td>
                                    <div class="market-outcomes">
                                        <?php foreach ($market['outcomes'] as $outcome_key => $outcome_label): ?>
                                        <div class="outcome-item">
                                            <input type="text" 
                                                   name="markets[<?php echo esc_attr($key); ?>][outcomes][<?php echo esc_attr($outcome_key); ?>]" 
                                                   value="<?php echo esc_attr($outcome_label); ?>" 
                                                   class="small-text" />
                                            <code><?php echo esc_html($outcome_key); ?></code>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Markets', 'odds-comparison'); ?>" />
                    </p>
                </div>
            </form>
        </div>
        
        <style>
        .markets-configuration table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .markets-configuration th,
        .markets-configuration td {
            padding: 15px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .markets-configuration th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .market-outcomes {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .outcome-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .outcome-item code {
            background: #f1f1f1;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
        }
        </style>
        <?php
    }
    
    /**
     * Settings page.
     *
     * @return void
     */
    public function settings_page() {
        $settings = get_option('odds_comparison_settings', []);
        
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'odds_comparison_settings')) {
            $this->save_general_settings();
            $settings = get_option('odds_comparison_settings', []);
        }
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Odds Comparison Settings', 'odds-comparison'); ?></h1>
            
            <form method="post">
                <?php wp_nonce_field('odds_comparison_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="api_key"><?php esc_html_e('API Key', 'odds-comparison'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="api_key" name="api_key" 
                                   value="<?php echo esc_attr($settings['api_key'] ?? ''); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Your API key from The Odds API. Get one at', 'odds-comparison'); ?> 
                                <a href="https://the-odds-api.com/" target="_blank">the-odds-api.com</a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_format"><?php esc_html_e('Default Odds Format', 'odds-comparison'); ?></label>
                        </th>
                        <td>
                            <select id="default_format" name="default_format">
                                <option value="decimal" <?php selected($settings['default_format'] ?? 'decimal', 'decimal'); ?>>
                                    <?php esc_html_e('Decimal (2.50)', 'odds-comparison'); ?>
                                </option>
                                <option value="fractional" <?php selected($settings['default_format'] ?? 'decimal', 'fractional'); ?>>
                                    <?php esc_html_e('Fractional (3/2)', 'odds-comparison'); ?>
                                </option>
                                <option value="american" <?php selected($settings['default_format'] ?? 'decimal', 'american'); ?>>
                                    <?php esc_html_e('American (+150)', 'odds-comparison'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="cache_duration"><?php esc_html_e('Cache Duration (minutes)', 'odds-comparison'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="cache_duration" name="cache_duration" 
                                   value="<?php echo esc_attr($settings['cache_duration'] ?? 5); ?>" 
                                   min="1" max="60" class="small-text" />
                            <p class="description">
                                <?php esc_html_e('How long to cache odds data (1-60 minutes)', 'odds-comparison'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auto_refresh"><?php esc_html_e('Auto Refresh', 'odds-comparison'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" id="auto_refresh" name="auto_refresh" 
                                       value="1" <?php checked($settings['auto_refresh'] ?? false); ?> />
                                <?php esc_html_e('Enable automatic odds refresh on frontend', 'odds-comparison'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'odds-comparison'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get API status.
     *
     * @return array API status information.
     */
    private function get_api_status() {
        try {
            $scraper = new \OddsComparison\Core\TheOddsAPIScraper();
            $test_result = $scraper->test_api_connection();
            
            return [
                'connected' => $test_result['connected'],
                'message' => $test_result['connected'] 
                    ? sprintf(__('Connected to The Odds API. %d sports available.', 'odds-comparison'), $test_result['sports_count'])
                    : __('Failed to connect to The Odds API. Check your API key.', 'odds-comparison')
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'message' => sprintf(__('Error: %s', 'odds-comparison'), $e->getMessage())
            ];
        }
    }
    
    /**
     * Get default markets.
     *
     * @return array Default markets configuration.
     */
    private function get_default_markets() {
        return [
            'match_winner' => [
                'name' => 'Match Winner',
                'label' => 'Match Winner',
                'enabled' => true,
                'outcomes' => [
                    'home' => 'Home',
                    'draw' => 'Draw',
                    'away' => 'Away'
                ]
            ],
            'over_under' => [
                'name' => 'Over/Under 2.5 Goals',
                'label' => 'Over/Under 2.5',
                'enabled' => true,
                'outcomes' => [
                    'over_2_5' => 'Over 2.5',
                    'under_2_5' => 'Under 2.5'
                ]
            ],
            'both_teams_score' => [
                'name' => 'Both Teams to Score',
                'label' => 'Both Teams Score',
                'enabled' => true,
                'outcomes' => [
                    'yes' => 'Yes',
                    'no' => 'No'
                ]
            ]
        ];
    }
    
    /**
     * Save bookmaker settings.
     *
     * @return void
     */
    private function save_bookmaker_settings() {
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        
        $name = sanitize_text_field($_POST['bookmaker_name']);
        $url = esc_url_raw($_POST['bookmaker_url']);
        $affiliate_link = !empty($_POST['affiliate_link']) ? esc_url_raw($_POST['affiliate_link']) : '';
        $enabled = isset($_POST['bookmaker_enabled']);
        
        $id = sanitize_title($name);
        
        $bookmakers[$id] = [
            'name' => $name,
            'url' => $url,
            'affiliate_link' => $affiliate_link,
            'enabled' => $enabled,
            'created' => current_time('mysql')
        ];
        
        update_option('odds_comparison_bookmakers', $bookmakers);
        
        wp_redirect(admin_url('admin.php?page=odds-comparison-bookmakers&message=added'));
        exit;
    }
    
    /**
     * Save market settings.
     *
     * @return void
     */
    private function save_market_settings() {
        $markets = [];
        
        if (isset($_POST['markets']) && is_array($_POST['markets'])) {
            foreach ($_POST['markets'] as $key => $market) {
                $markets[$key] = [
                    'name' => sanitize_text_field($market['name'] ?? ''),
                    'label' => sanitize_text_field($market['label'] ?? ''),
                    'enabled' => isset($market['enabled']),
                    'outcomes' => array_map('sanitize_text_field', $market['outcomes'] ?? [])
                ];
            }
        }
        
        update_option('odds_comparison_markets', $markets);
        
        wp_redirect(admin_url('admin.php?page=odds-comparison-markets&message=saved'));
        exit;
    }
    
    /**
     * Save general settings.
     *
     * @return void
     */
    private function save_general_settings() {
        $settings = [
            'api_key' => sanitize_text_field($_POST['api_key'] ?? ''),
            'default_format' => sanitize_text_field($_POST['default_format'] ?? 'decimal'),
            'cache_duration' => intval($_POST['cache_duration'] ?? 5),
            'auto_refresh' => isset($_POST['auto_refresh'])
        ];
        
        update_option('odds_comparison_settings', $settings);
        
        wp_redirect(admin_url('admin.php?page=odds-comparison-settings&message=saved'));
        exit;
    }
    
    /**
     * Sanitize bookmakers data.
     *
     * @param array $bookmakers Bookmakers data.
     * @return array Sanitized bookmakers data.
     */
    public function sanitize_bookmakers($bookmakers) {
        if (!is_array($bookmakers)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($bookmakers as $id => $bookmaker) {
            $sanitized[$id] = [
                'name' => sanitize_text_field($bookmaker['name'] ?? ''),
                'url' => esc_url_raw($bookmaker['url'] ?? ''),
                'affiliate_link' => esc_url_raw($bookmaker['affiliate_link'] ?? ''),
                'enabled' => (bool) ($bookmaker['enabled'] ?? false)
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize markets data.
     *
     * @param array $markets Markets data.
     * @return array Sanitized markets data.
     */
    public function sanitize_markets($markets) {
        if (!is_array($markets)) {
            return [];
        }
        
        $sanitized = [];
        foreach ($markets as $key => $market) {
            $sanitized[$key] = [
                'name' => sanitize_text_field($market['name'] ?? ''),
                'label' => sanitize_text_field($market['label'] ?? ''),
                'enabled' => (bool) ($market['enabled'] ?? false),
                'outcomes' => array_map('sanitize_text_field', $market['outcomes'] ?? [])
            ];
        }
        
        return $sanitized;
    }
    
    /**
     * AJAX handler for saving bookmaker.
     *
     * @return void
     */
    public function save_bookmaker() {
        check_ajax_referer('odds_comparison_save_bookmaker', 'nonce');
        
        // Implementation for AJAX bookmaker saving
        wp_send_json_success(['message' => 'Bookmaker saved successfully']);
    }
    
    /**
     * AJAX handler for deleting bookmaker.
     *
     * @return void
     */
    public function delete_bookmaker() {
        check_ajax_referer('odds_comparison_delete_bookmaker', 'nonce');
        
        $bookmaker_id = sanitize_text_field($_POST['bookmaker_id']);
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        
        if (isset($bookmakers[$bookmaker_id])) {
            unset($bookmakers[$bookmaker_id]);
            update_option('odds_comparison_bookmakers', $bookmakers);
            wp_send_json_success(['message' => 'Bookmaker deleted successfully']);
        } else {
            wp_send_json_error(['message' => 'Bookmaker not found']);
        }
    }
    
    /**
     * AJAX handler for testing API connection.
     *
     * @return void
     */
    public function test_api_connection() {
        check_ajax_referer('odds_comparison_test_api', 'nonce');
        
        $status = $this->get_api_status();
        
        if ($status['connected']) {
            wp_send_json_success(['message' => $status['message']]);
        } else {
            wp_send_json_error(['message' => $status['message']]);
        }
    }
    
    /**
     * AJAX handler for toggling bookmaker visibility.
     *
     * @return void
     */
    public function toggle_bookmaker_visibility() {
        // Debug logging
        error_log('AJAX toggle_bookmaker_visibility called');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'odds_comparison_toggle_bookmaker')) {
            error_log('Nonce verification failed');
            wp_send_json_error(['message' => 'Nonce verification failed']);
            return;
        }
        
        $bookmaker_id = sanitize_text_field($_POST['bookmaker_id']);
        $is_visible = (bool) $_POST['is_visible'];
        
        error_log("Toggling bookmaker: {$bookmaker_id} to " . ($is_visible ? 'visible' : 'hidden'));
        
        $visibility_settings = get_option('odds_comparison_bookmaker_visibility', []);
        $visibility_settings[$bookmaker_id] = $is_visible;
        
        $result = update_option('odds_comparison_bookmaker_visibility', $visibility_settings);
        
        error_log('Update result: ' . ($result ? 'success' : 'failed'));
        
        wp_send_json_success([
            'message' => $is_visible ? 'Bookmaker enabled' : 'Bookmaker disabled',
            'bookmaker_id' => $bookmaker_id,
            'is_visible' => $is_visible,
            'update_result' => $result
        ]);
    }
    
    /**
     * Get bookmakers from API.
     *
     * @return array API bookmakers.
     */
    public function get_api_bookmakers() {
        // Try to get from cache first
        $cache_key = 'odds_comparison_api_bookmakers';
        $cached_bookmakers = get_transient($cache_key);
        
        if ($cached_bookmakers !== false) {
            return $cached_bookmakers;
        }
        
        try {
            $scraper = new \OddsComparison\Core\TheOddsAPIScraper();
            
            // Get bookmakers from multiple sports to get a comprehensive list
            $sports_to_check = ['soccer_epl', 'basketball_nba', 'tennis_atp', 'baseball_mlb'];
            $all_bookmakers = [];
            
            foreach ($sports_to_check as $sport) {
                $odds_data = $scraper->get_odds_for_sport($sport);
                
                if (!empty($odds_data)) {
                    foreach ($odds_data as $event) {
                        if (isset($event['bookmakers'])) {
                            foreach ($event['bookmakers'] as $bookmaker) {
                                $bookmaker_id = sanitize_title($bookmaker['title']);
                                
                                // Only add if not already added
                                if (!isset($all_bookmakers[$bookmaker_id])) {
                                    $all_bookmakers[$bookmaker_id] = [
                                        'title' => $bookmaker['title'],
                                        'url' => $bookmaker['url'] ?? '#',
                                        'key' => $bookmaker['key'] ?? $bookmaker_id
                                    ];
                                }
                            }
                        }
                    }
                }
                
                // Limit to avoid too many API calls
                if (count($all_bookmakers) >= 20) {
                    break;
                }
            }
            
            // If no bookmakers found from API, use fallback bookmakers
            if (empty($all_bookmakers)) {
                $all_bookmakers = $this->get_fallback_bookmakers();
            }
            
            // Cache for 2 hours
            set_transient($cache_key, $all_bookmakers, 2 * HOUR_IN_SECONDS);
            
            return $all_bookmakers;
            
        } catch (Exception $e) {
            // Return fallback bookmakers on error
            return $this->get_fallback_bookmakers();
        }
    }
    
    /**
     * Get fallback bookmakers when API is not available.
     *
     * @return array Fallback bookmakers.
     */
    public function get_fallback_bookmakers() {
        return [
            'fanduel' => [
                'title' => 'FanDuel',
                'url' => 'https://www.fanduel.com/',
                'key' => 'fanduel'
            ],
            'draftkings' => [
                'title' => 'DraftKings',
                'url' => 'https://www.draftkings.com/',
                'key' => 'draftkings'
            ],
            'betmgm' => [
                'title' => 'BetMGM',
                'url' => 'https://www.betmgm.com/',
                'key' => 'betmgm'
            ],
            'caesars' => [
                'title' => 'Caesars',
                'url' => 'https://www.caesars.com/sportsbook',
                'key' => 'caesars'
            ],
            'bet365' => [
                'title' => 'Bet365',
                'url' => 'https://www.bet365.com/',
                'key' => 'bet365'
            ],
            'betrivers' => [
                'title' => 'BetRivers',
                'url' => 'https://www.betrivers.com/',
                'key' => 'betrivers'
            ],
            'pointsbet' => [
                'title' => 'PointsBet',
                'url' => 'https://www.pointsbet.com/',
                'key' => 'pointsbet'
            ],
            'paddy-power' => [
                'title' => 'Paddy Power',
                'url' => 'https://www.paddypower.com/',
                'key' => 'paddy_power'
            ],
            'mybookie-ag' => [
                'title' => 'MyBookie.ag',
                'url' => 'https://www.mybookie.ag/',
                'key' => 'mybookie'
            ],
            'betfair' => [
                'title' => 'Betfair',
                'url' => 'https://www.betfair.com/',
                'key' => 'betfair'
            ],
            'smarkets' => [
                'title' => 'Smarkets',
                'url' => 'https://smarkets.com/',
                'key' => 'smarkets'
            ],
            'boylesports' => [
                'title' => 'BoyleSports',
                'url' => 'https://www.boylesports.com/',
                'key' => 'boylesports'
            ],
            'bet-victor' => [
                'title' => 'Bet Victor',
                'url' => 'https://www.betvictor.com/',
                'key' => 'bet_victor'
            ],
            '888sport' => [
                'title' => '888sport',
                'url' => 'https://www.888sport.com/',
                'key' => '888sport'
            ],
            'william-hill' => [
                'title' => 'William Hill',
                'url' => 'https://www.williamhill.com/',
                'key' => 'william_hill'
            ],
            'betway' => [
                'title' => 'Betway',
                'url' => 'https://www.betway.com/',
                'key' => 'betway'
            ],
            'livescore-bet' => [
                'title' => 'LiveScore Bet',
                'url' => 'https://www.livescorebet.com/',
                'key' => 'livescore_bet'
            ],
            'unibet' => [
                'title' => 'Unibet',
                'url' => 'https://www.unibet.com/',
                'key' => 'unibet'
            ],
            'grosvenor' => [
                'title' => 'Grosvenor',
                'url' => 'https://www.grosvenor.com/',
                'key' => 'grosvenor'
            ],
            'coral' => [
                'title' => 'Coral',
                'url' => 'https://www.coral.co.uk/',
                'key' => 'coral'
            ],
            'ladbrokes' => [
                'title' => 'Ladbrokes',
                'url' => 'https://www.ladbrokes.com/',
                'key' => 'ladbrokes'
            ],
            'betfred' => [
                'title' => 'Betfred',
                'url' => 'https://www.betfred.com/',
                'key' => 'betfred'
            ],
            'sky-bet' => [
                'title' => 'Sky Bet',
                'url' => 'https://www.skybet.com/',
                'key' => 'sky_bet'
            ],
            'bet365-sport' => [
                'title' => 'Bet365 Sport',
                'url' => 'https://www.bet365.com/',
                'key' => 'bet365_sport'
            ],
            'virgin-bet' => [
                'title' => 'Virgin Bet',
                'url' => 'https://www.virginbet.com/',
                'key' => 'virgin_bet'
            ],
            'betfair-sportsbook' => [
                'title' => 'Betfair Sportsbook',
                'url' => 'https://www.betfair.com/sport/',
                'key' => 'betfair_sportsbook'
            ],
            'paddy-power-sports' => [
                'title' => 'Paddy Power Sports',
                'url' => 'https://www.paddypower.com/sports',
                'key' => 'paddy_power_sports'
            ]
        ];
    }
    
    /**
     * Save bookmaker visibility settings.
     *
     * @return void
     */
    private function save_bookmaker_visibility_settings() {
        $visibility_settings = [];
        
        if (isset($_POST['bookmaker_visibility']) && is_array($_POST['bookmaker_visibility'])) {
            foreach ($_POST['bookmaker_visibility'] as $bookmaker_id => $value) {
                $visibility_settings[sanitize_text_field($bookmaker_id)] = (bool) $value;
            }
        }
        
        update_option('odds_comparison_bookmaker_visibility', $visibility_settings);
        
        wp_redirect(admin_url('admin.php?page=odds-comparison-bookmakers&message=visibility_saved'));
        exit;
    }
    
    /**
     * Save bookmaker affiliate settings.
     *
     * @return void
     */
    private function save_bookmaker_affiliate_settings() {
        $affiliate_settings = [];
        
        if (isset($_POST['bookmaker_affiliates']) && is_array($_POST['bookmaker_affiliates'])) {
            foreach ($_POST['bookmaker_affiliates'] as $bookmaker_id => $affiliate_link) {
                $affiliate_settings[sanitize_text_field($bookmaker_id)] = esc_url_raw($affiliate_link);
            }
        }
        
        update_option('odds_comparison_bookmaker_affiliates', $affiliate_settings);
        
        // Save default affiliate tracking
        if (isset($_POST['default_affiliate_tracking'])) {
            update_option('odds_comparison_default_affiliate_tracking', sanitize_text_field($_POST['default_affiliate_tracking']));
        }
        
        wp_redirect(admin_url('admin.php?page=odds-comparison-bookmakers&message=affiliates_saved'));
        exit;
    }
}
