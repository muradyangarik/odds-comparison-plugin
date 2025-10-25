<?php
/**
 * Main Plugin Class
 *
 * This is the core plugin class that initializes all components and manages the plugin lifecycle.
 * Implements Singleton pattern to ensure only one instance exists.
 *
 * @package OddsComparison
 * @since 1.0.0
 */

namespace OddsComparison;

/**
 * Class Plugin
 *
 * The main plugin class that coordinates all functionality.
 */
class Plugin {
    
    /**
     * The single instance of the class.
     *
     * @var Plugin
     */
    private static $instance = null;
    
    /**
     * Admin controller instance.
     *
     * @var Admin\AdminController
     */
    private $admin_controller;
    
    /**
     * Block controller instance.
     *
     * @var Blocks\BlockController
     */
    private $block_controller;
    
    /**
     * API controller instance.
     *
     * @var API\APIController
     */
    private $api_controller;
    
    /**
     * Cache manager instance.
     *
     * @var Core\CacheManager
     */
    private $cache_manager;
    
    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct() {
        // Constructor is private to enforce Singleton pattern.
    }
    
    /**
     * Get the singleton instance of the plugin.
     *
     * @return Plugin The plugin instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize and run the plugin.
     *
     * @return void
     */
    public function run() {
        $this->load_dependencies();
        $this->initialize_components();
        $this->register_hooks();
    }
    
    /**
     * Load required dependencies.
     *
     * @return void
     */
    private function load_dependencies() {
        // Core dependencies are loaded via autoloader.
        // Additional files can be loaded here if needed.
    }
    
    /**
     * Initialize plugin components.
     *
     * @return void
     */
    private function initialize_components() {
        // Initialize cache manager.
        $this->cache_manager = new Core\CacheManager();
        
        // Initialize admin controller.
        $this->admin_controller = new Admin\AdminController();
        
        // Initialize bookmaker manager only if not already initialized
        if (!get_option('odds_comparison_bookmaker_manager_initialized')) {
            new Admin\BookmakerManager();
            update_option('odds_comparison_bookmaker_manager_initialized', true);
        }
        
        // Initialize block controller.
        $this->block_controller = new Blocks\BlockController();
        
        // Initialize API controller.
        $this->api_controller = new API\APIController();
    }
    
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function register_hooks() {
        // Enqueue scripts and styles.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Register REST API endpoints.
        add_action('rest_api_init', [$this->api_controller, 'register_routes']);
        
        // Schedule cron jobs for updating odds.
        add_action('odds_comparison_update_odds', [$this, 'scheduled_odds_update']);
        
        // Add custom scheduled interval.
        add_filter('cron_schedules', [$this, 'add_custom_cron_interval']);
    }
    
    /**
     * Enqueue frontend assets.
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Enqueue frontend CSS.
        wp_enqueue_style(
            'odds-comparison-frontend',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/css/frontend.css',
            [],
            ODDS_COMPARISON_VERSION
        );
        
        // Enqueue frontend JavaScript.
        wp_enqueue_script(
            'odds-comparison-frontend',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/js/frontend.js',
            ['jquery'],
            ODDS_COMPARISON_VERSION,
            true
        );
        
        // Localize script with AJAX URL and nonce.
        wp_localize_script('odds-comparison-frontend', 'oddsComparison', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('odds-comparison/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'defaultFormat' => get_option('odds_comparison_default_format', 'decimal'),
            'enabledMarkets' => $this->get_enabled_markets(),
        ]);
    }
    
    /**
     * Get enabled markets for frontend.
     *
     * @return array Array of enabled market configurations.
     */
    private function get_enabled_markets() {
        $markets = get_option('odds_comparison_markets', []);
        $enabled_markets = [];
        
        foreach ($markets as $market_id => $market) {
            if ($market['enabled']) {
                $enabled_markets[$market_id] = [
                    'id' => $market_id,
                    'label' => $market['label'],
                    'enabled' => true
                ];
            }
        }
        
        return $enabled_markets;
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook The current admin page hook.
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages.
        if (strpos($hook, 'odds-comparison') === false) {
            return;
        }
        
        // Enqueue admin CSS.
        wp_enqueue_style(
            'odds-comparison-admin',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ODDS_COMPARISON_VERSION
        );
        
        // Enqueue admin JavaScript.
        wp_enqueue_script(
            'odds-comparison-admin',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'wp-color-picker'],
            ODDS_COMPARISON_VERSION,
            true
        );
        
        // Add color picker support.
        wp_enqueue_style('wp-color-picker');
    }
    
    /**
     * Add custom cron interval for odds updates.
     *
     * @param array $schedules Existing cron schedules.
     * @return array Modified schedules.
     */
    public function add_custom_cron_interval($schedules) {
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display' => __('Every 5 Minutes', 'odds-comparison'),
        ];
        
        $schedules['fifteen_minutes'] = [
            'interval' => 900,
            'display' => __('Every 15 Minutes', 'odds-comparison'),
        ];
        
        return $schedules;
    }
    
    /**
     * Scheduled odds update callback.
     *
     * @return void
     */
    public function scheduled_odds_update() {
        $scraper = new Core\OddsScraper();
        $scraper->fetch_and_cache_odds();
    }
    
    /**
     * Get cache manager instance.
     *
     * @return Core\CacheManager
     */
    public function get_cache_manager() {
        return $this->cache_manager;
    }
    
    /**
     * Prevent cloning of the instance.
     *
     * @return void
     */
    private function __clone() {
        // Prevent cloning.
    }
    
    /**
     * Prevent unserializing of the instance.
     *
     * @return void
     */
    public function __wakeup() {
        // Prevent unserializing.
    }
}


