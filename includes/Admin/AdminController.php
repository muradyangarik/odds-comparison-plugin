<?php
/**
 * Admin Controller Class
 *
 * Manages all admin-related functionality including menu registration,
 * settings pages, and admin AJAX handlers.
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

namespace OddsComparison\Admin;

/**
 * Class AdminController
 *
 * Coordinates admin interface functionality.
 */
class AdminController {
    
    /**
     * Settings page instance.
     *
     * @var SettingsPage
     */
    private $settings_page;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->settings_page = new SettingsPage();
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this->settings_page, 'register_settings']);
        add_action('wp_ajax_odds_comparison_clear_cache', [$this, 'ajax_clear_cache']);
        add_action('wp_ajax_odds_comparison_test_scraper', [$this, 'ajax_test_scraper']);
        add_action('wp_ajax_get_admin_bookmakers', [$this, 'ajax_get_admin_bookmakers']);
        add_action('wp_ajax_nopriv_get_admin_bookmakers', [$this, 'ajax_get_admin_bookmakers']);
        add_action('init', [$this, 'load_generated_scrapers']);
    }
    
    /**
     * Add admin menu pages.
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page.
        add_menu_page(
            __('Odds Comparison', 'odds-comparison'),
            __('Odds Comparison', 'odds-comparison'),
            'manage_odds_comparison',
            'odds-comparison',
            [$this->settings_page, 'render_settings_page'],
            'dashicons-chart-line',
            30
        );
        
        // Settings submenu.
        add_submenu_page(
            'odds-comparison',
            __('Settings', 'odds-comparison'),
            __('Settings', 'odds-comparison'),
            'manage_odds_comparison',
            'odds-comparison',
            [$this->settings_page, 'render_settings_page']
        );
        
        // Bookmakers submenu.
        add_submenu_page(
            'odds-comparison',
            __('Bookmakers', 'odds-comparison'),
            __('Bookmakers', 'odds-comparison'),
            'manage_odds_comparison',
            'odds-comparison-bookmakers',
            [$this, 'render_bookmakers_page']
        );
        
        // Markets submenu.
        add_submenu_page(
            'odds-comparison',
            __('Markets', 'odds-comparison'),
            __('Markets', 'odds-comparison'),
            'manage_odds_comparison',
            'odds-comparison-markets',
            [$this, 'render_markets_page']
        );
        
        // Tools submenu.
        add_submenu_page(
            'odds-comparison',
            __('Tools', 'odds-comparison'),
            __('Tools', 'odds-comparison'),
            'manage_odds_comparison',
            'odds-comparison-tools',
            [$this, 'render_tools_page']
        );
        
    }
    
    /**
     * Render bookmakers management page.
     *
     * @return void
     */
    public function render_bookmakers_page() {
        require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Admin/views/bookmakers-page.php';
    }
    
    /**
     * Render markets management page.
     *
     * @return void
     */
    public function render_markets_page() {
        require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Admin/views/markets-page.php';
    }
    
    /**
     * Render tools page.
     *
     * @return void
     */
    public function render_tools_page() {
        require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Admin/views/tools-page.php';
    }
    
    
    
    /**
     * AJAX handler for clearing cache.
     *
     * @return void
     */
    public function ajax_clear_cache() {
        check_ajax_referer('odds_comparison_nonce', 'nonce');
        
        if (!current_user_can('manage_odds_comparison')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'odds-comparison')]);
        }
        
        $cache = new \OddsComparison\Core\CacheManager();
        $cache->clear_all();
        
        wp_send_json_success(['message' => __('Cache cleared successfully', 'odds-comparison')]);
    }
    
    /**
     * Load generated scrapers on init.
     *
     * @return void
     */
    public function load_generated_scrapers() {
        $generated_classes = \OddsComparison\Core\ScrapingSources\DynamicScraperGenerator::get_generated_classes();
        
        foreach ($generated_classes as $class_name => $class_data) {
            \OddsComparison\Core\ScrapingSources\DynamicScraperGenerator::load_generated_class($class_name);
        }
    }
    
    /**
     * Register custom scraper for new bookmaker.
     *
     * @param string $bookmaker_id Bookmaker identifier.
     * @param array $bookmaker_config Bookmaker configuration.
     * @return void
     */
    public function register_custom_scraper($bookmaker_id, $bookmaker_config) {
        if (!empty($bookmaker_config['scraping_source']) && $bookmaker_config['scraping_source'] !== 'none') {
            \OddsComparison\Core\ScrapingSources\DynamicScraperGenerator::generate_scraper($bookmaker_id, $bookmaker_config);
        }
    }
    
    /**
     * AJAX handler for testing the scraper.
     *
     * @return void
     */
    public function ajax_test_scraper() {
        // Check nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'odds_comparison_nonce')) {
            wp_send_json_error([
                'message' => 'Security check failed: Invalid nonce'
            ]);
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => 'Insufficient permissions'
            ]);
            return;
        }
        
        try {
            // Test The Odds API scraper
            $api_scraper = new \OddsComparison\Core\TheOddsAPIScraper();
            $test_result = $api_scraper->test_api_connection();
            
            if ($test_result['connected']) {
                wp_send_json_success([
                    'message' => 'Scraper test successful!',
                    'data' => $test_result
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Scraper test failed: API connection failed',
                    'data' => $test_result
                ]);
            }
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => 'Scraper test failed: ' . $e->getMessage()
            ]);
        } catch (Error $e) {
            wp_send_json_error([
                'message' => 'Scraper test failed: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * AJAX handler for getting admin bookmakers.
     */
    public function ajax_get_admin_bookmakers() {
        try {
            // Get the actual bookmakers from the admin panel
            $bookmaker_manager = new \OddsComparison\Admin\BookmakerManager();
            $api_bookmakers = $bookmaker_manager->get_api_bookmakers();
            
            // Get bookmaker visibility settings
            $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
            
            $bookmakers = [];
            
            // Use the actual admin bookmakers
            foreach ($api_bookmakers as $bookmaker_id => $bookmaker_data) {
                // Check if this bookmaker is visible (default to true if no settings)
                $is_visible = isset($bookmaker_visibility[$bookmaker_id]) ? 
                             $bookmaker_visibility[$bookmaker_id] : true;
                
                if ($is_visible) {
                    $bookmakers[] = [
                        'bookmaker' => $bookmaker_data['title'],
                        'odds' => [
                            'home' => number_format(1.60 + (rand(0, 100) / 100) * 0.80, 2),
                            'draw' => number_format(3.00 + (rand(0, 50) / 100) * 0.50, 2),
                            'away' => number_format(1.80 + (rand(0, 120) / 100) * 1.20, 2),
                            'over' => number_format(1.80 + (rand(0, 30) / 100) * 0.30, 2),
                            'under' => number_format(1.80 + (rand(0, 30) / 100) * 0.30, 2),
                            'yes' => number_format(1.70 + (rand(0, 50) / 100) * 0.50, 2),
                            'no' => number_format(2.00 + (rand(0, 60) / 100) * 0.60, 2)
                        ],
                        'url' => $bookmaker_data['url'] ?? 'https://' . strtolower(str_replace([' ', '&'], ['', ''], $bookmaker_data['title'])) . '.com'
                    ];
                }
            }
            
            // If no visible bookmakers, show all bookmakers
            if (empty($bookmakers)) {
                foreach ($api_bookmakers as $bookmaker_id => $bookmaker_data) {
                    $bookmakers[] = [
                        'bookmaker' => $bookmaker_data['title'],
                        'odds' => [
                            'home' => number_format(1.60 + (rand(0, 100) / 100) * 0.80, 2),
                            'draw' => number_format(3.00 + (rand(0, 50) / 100) * 0.50, 2),
                            'away' => number_format(1.80 + (rand(0, 120) / 100) * 1.20, 2),
                            'over' => number_format(1.80 + (rand(0, 30) / 100) * 0.30, 2),
                            'under' => number_format(1.80 + (rand(0, 30) / 100) * 0.30, 2),
                            'yes' => number_format(1.70 + (rand(0, 50) / 100) * 0.50, 2),
                            'no' => number_format(2.00 + (rand(0, 60) / 100) * 0.60, 2)
                        ],
                        'url' => $bookmaker_data['url'] ?? 'https://' . strtolower(str_replace([' ', '&'], ['', ''], $bookmaker_data['title'])) . '.com'
                    ];
                }
            }
            
            if (empty($bookmakers)) {
                wp_send_json_error(['message' => 'No bookmakers found in admin panel']);
            } else {
                wp_send_json_success(['data' => $bookmakers]);
            }
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Failed to get bookmakers: ' . $e->getMessage()]);
        }
    }
}

