<?php
/**
 * Dynamic Scraper Generator Class
 *
 * Generates custom scraper classes dynamically for bookmakers
 * added through the admin panel.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Class DynamicScraperGenerator
 *
 * Creates custom scraper classes on-the-fly for new bookmakers.
 */
class DynamicScraperGenerator {
    
    /**
     * Generate a custom scraper class for a bookmaker.
     *
     * @param string $bookmaker_id Bookmaker identifier.
     * @param array $bookmaker_config Bookmaker configuration.
     * @return string Generated class name.
     */
    public static function generate_scraper($bookmaker_id, $bookmaker_config) {
        $class_name = self::get_class_name($bookmaker_id);
        
        // Generate the class code
        $class_code = self::generate_class_code($class_name, $bookmaker_config);
        
        // Store the generated class
        self::store_generated_class($class_name, $class_code);
        
        // Register with the factory
        ScrapingSourceFactory::register_source($bookmaker_id, $class_name);
        
        return $class_name;
    }
    
    /**
     * Get class name for bookmaker.
     *
     * @param string $bookmaker_id Bookmaker identifier.
     * @return string Class name.
     */
    private static function get_class_name($bookmaker_id) {
        $class_name = str_replace(['-', '_'], '', ucwords($bookmaker_id, '-_'));
        return $class_name . 'DynamicScraper';
    }
    
    /**
     * Generate class code.
     *
     * @param string $class_name Class name.
     * @param array $config Bookmaker configuration.
     * @return string Generated class code.
     */
    private static function generate_class_code($class_name, $config) {
        $bookmaker_name = $config['name'];
        $base_url = $config['url'];
        $scraping_source = $config['scraping_source'] ?? 'none';
        $api_key = $config['api_key'] ?? '';
        $rate_limit = $config['rate_limit'] ?? 2;
        $supported_markets = $config['supported_markets'] ?? ['match_winner'];
        
        $markets_array = var_export($supported_markets, true);
        
        $class_code = "<?php
/**
 * Auto-generated scraper for {$bookmaker_name}
 * Generated on " . current_time('Y-m-d H:i:s') . "
 */

namespace OddsComparison\Core\ScrapingSources;

class {$class_name} implements ScrapingSourceInterface {
    
    private \$base_url = '{$base_url}';
    private \$user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    private \$rate_limit_delay = {$rate_limit};
    private \$api_key = '{$api_key}';
    private \$supported_markets = {$markets_array};
    
    public function scrape_odds(\$event_name, \$market_type) {
        if (!in_array(\$market_type, \$this->supported_markets)) {
            return [];
        }
        
        switch ('{$scraping_source}') {
            case 'api':
                return \$this->scrape_via_api(\$event_name, \$market_type);
            case 'web_scraping':
                return \$this->scrape_via_web(\$event_name, \$market_type);
            case 'oddschecker':
                return \$this->scrape_via_oddschecker(\$event_name, \$market_type);
            default:
                return \$this->get_sample_odds(\$event_name, \$market_type);
        }
    }
    
    private function scrape_via_api(\$event_name, \$market_type) {
        if (empty(\$this->api_key)) {
            return \$this->get_sample_odds(\$event_name, \$market_type);
        }
        
        \$url = \$this->build_api_url(\$event_name, \$market_type);
        \$response = wp_remote_get(\$url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . \$this->api_key,
                'Accept' => 'application/json',
            ],
        ]);
        
        if (is_wp_error(\$response)) {
            return \$this->get_sample_odds(\$event_name, \$market_type);
        }
        
        \$data = json_decode(wp_remote_retrieve_body(\$response), true);
        return \$this->parse_api_response(\$data, \$market_type);
    }
    
    private function scrape_via_web(\$event_name, \$market_type) {
        \$url = \$this->build_web_url(\$event_name, \$market_type);
        \$html = \$this->make_request(\$url);
        
        if (!\$html) {
            return \$this->get_sample_odds(\$event_name, \$market_type);
        }
        
        return \$this->parse_web_html(\$html, \$market_type);
    }
    
    private function scrape_via_oddschecker(\$event_name, \$market_type) {
        // Use the existing Oddschecker scraper
        \$oddschecker = new OddscheckerScraper();
        \$odds_data = \$oddschecker->scrape_odds(\$event_name, \$market_type);
        
        // Filter for this bookmaker
        if (isset(\$odds_data['{$bookmaker_name}'])) {
            return ['{$bookmaker_name}' => \$odds_data['{$bookmaker_name}']];
        }
        
        return \$this->get_sample_odds(\$event_name, \$market_type);
    }
    
    private function build_api_url(\$event_name, \$market_type) {
        \$event_slug = \$this->convert_to_slug(\$event_name);
        \$market_slug = \$this->convert_market_to_slug(\$market_type);
        return \$this->base_url . '/api/odds/' . \$event_slug . '/' . \$market_slug . '?key=' . \$this->api_key;
    }
    
    private function build_web_url(\$event_name, \$market_type) {
        \$event_slug = \$this->convert_to_slug(\$event_name);
        \$market_slug = \$this->convert_market_to_slug(\$market_type);
        return \$this->base_url . '/sports/football/' . \$event_slug . '/' . \$market_slug;
    }
    
    private function convert_to_slug(\$event_name) {
        \$slug = strtolower(\$event_name);
        \$slug = str_replace([' vs ', ' v ', ' against '], '-vs-', \$slug);
        \$slug = preg_replace('/[^a-z0-9\-]/', '', \$slug);
        \$slug = preg_replace('/-+/', '-', \$slug);
        return trim(\$slug, '-');
    }
    
    private function convert_market_to_slug(\$market_type) {
        \$market_slugs = [
            'match_winner' => 'match-winner',
            'over_under' => 'over-under',
            'both_teams_score' => 'both-teams-score',
            'handicap' => 'handicap',
            'correct_score' => 'correct-score',
        ];
        return \$market_slugs[\$market_type] ?? 'match-winner';
    }
    
    private function parse_api_response(\$data, \$market_type) {
        if (!isset(\$data['odds']) || !is_array(\$data['odds'])) {
            return \$this->get_sample_odds('', \$market_type);
        }
        
        return ['{$bookmaker_name}' => \$data['odds']];
    }
    
    private function parse_web_html(\$html, \$market_type) {
        // Basic HTML parsing - would need to be customized per bookmaker
        \$dom = new \\DOMDocument();
        libxml_use_internal_errors(true);
        \$dom->loadHTML(\$html);
        libxml_clear_errors();
        
        // This is a basic implementation - would need customization
        return \$this->get_sample_odds('', \$market_type);
    }
    
    private function make_request(\$url) {
        \$args = [
            'timeout' => 15,
            'user-agent' => \$this->user_agent,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Language' => 'en-US,en;q=0.9',
            ],
        ];
        
        \$response = wp_remote_get(\$url, \$args);
        
        if (is_wp_error(\$response)) {
            return false;
        }
        
        return wp_remote_retrieve_body(\$response);
    }
    
    private function get_sample_odds(\$event_name, \$market_type) {
        \$base_odds = [
            'match_winner' => [
                'home' => 1.8 + (rand(-20, 20) / 100),
                'draw' => 3.2 + (rand(-20, 20) / 100),
                'away' => 2.9 + (rand(-20, 20) / 100),
            ],
            'over_under' => [
                'over_2_5' => 1.9 + (rand(-20, 20) / 100),
                'under_2_5' => 1.9 + (rand(-20, 20) / 100),
            ],
            'both_teams_score' => [
                'yes' => 1.8 + (rand(-20, 20) / 100),
                'no' => 1.9 + (rand(-20, 20) / 100),
            ],
        ];
        
        return ['{$bookmaker_name}' => \$base_odds[\$market_type] ?? []];
    }
    
    public function get_source_name() {
        return '{$bookmaker_id}';
    }
    
    public function is_available() {
        \$test_url = \$this->base_url;
        \$response = wp_remote_get(\$test_url, ['timeout' => 10]);
        return !is_wp_error(\$response) && wp_remote_retrieve_response_code(\$response) === 200;
    }
    
    public function get_supported_markets() {
        return \$this->supported_markets;
    }
    
    public function get_rate_limit_delay() {
        return \$this->rate_limit_delay;
    }
}";

        return $class_code;
    }
    
    /**
     * Store generated class in cache.
     *
     * @param string $class_name Class name.
     * @param string $class_code Class code.
     * @return void
     */
    private static function store_generated_class($class_name, $class_code) {
        // Store in WordPress transients for now
        // In production, you might want to store in files or database
        set_transient("odds_comparison_generated_class_{$class_name}", $class_code, DAY_IN_SECONDS);
        
        // Also store in a registry of generated classes
        $generated_classes = get_option('odds_comparison_generated_classes', []);
        $generated_classes[$class_name] = [
            'code' => $class_code,
            'generated_at' => current_time('mysql'),
        ];
        update_option('odds_comparison_generated_classes', $generated_classes);
    }
    
    /**
     * Load generated class.
     *
     * @param string $class_name Class name.
     * @return bool True if loaded successfully.
     */
    public static function load_generated_class($class_name) {
        $generated_classes = get_option('odds_comparison_generated_classes', []);
        
        if (!isset($generated_classes[$class_name])) {
            return false;
        }
        
        $class_code = $generated_classes[$class_name]['code'];
        
        // Use eval to load the class (in production, consider file-based approach)
        eval('?>' . $class_code);
        
        return class_exists("\\OddsComparison\\Core\\ScrapingSources\\{$class_name}");
    }
    
    /**
     * Get all generated classes.
     *
     * @return array Generated classes registry.
     */
    public static function get_generated_classes() {
        return get_option('odds_comparison_generated_classes', []);
    }
    
    /**
     * Delete generated class.
     *
     * @param string $class_name Class name.
     * @return bool True if deleted successfully.
     */
    public static function delete_generated_class($class_name) {
        $generated_classes = get_option('odds_comparison_generated_classes', []);
        
        if (isset($generated_classes[$class_name])) {
            unset($generated_classes[$class_name]);
            update_option('odds_comparison_generated_classes', $generated_classes);
            
            // Remove from factory
            ScrapingSourceFactory::unregister_source($class_name);
            
            return true;
        }
        
        return false;
    }
}




