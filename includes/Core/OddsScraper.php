<?php
/**
 * Odds Scraper Class
 *
 * Handles scraping odds data from external sources with built-in
 * rate limiting, error handling, and caching.
 *
 * @package OddsComparison\Core
 * @since 1.0.0
 */

namespace OddsComparison\Core;

/**
 * Class OddsScraper
 *
 * Scrapes odds from comparison sites using various strategies.
 */
class OddsScraper {
    
    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private $cache;
    
    /**
     * HTTP client user agent.
     *
     * @var string
     */
    private $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    
    /**
     * Rate limiting delay in seconds.
     *
     * @var int
     */
    private $rate_limit_delay = 2;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->cache = new CacheManager();
    }
    
    /**
     * Fetch odds for a specific event.
     *
     * @param string $event_name Event identifier.
     * @param string $market_type Market type (e.g., match_winner).
     * @param string $sport Sport key (e.g., 'soccer_epl', 'basketball_nba').
     * @return array Array of odds data by bookmaker.
     */
    public function fetch_odds($event_name, $market_type = 'match_winner', $sport = 'football') {
        // Check cache first.
        $cache_key = "odds_{$event_name}_{$market_type}_{$sport}";
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        // Use The Odds API for guaranteed real data
        $odds_api_scraper = new TheOddsAPIScraper();
        
        // Scrape real odds from The Odds API
        $odds_data = $odds_api_scraper->scrape_real_odds($event_name, $sport, $market_type);
        
        // Cache the result for 5 minutes
        if (!empty($odds_data)) {
            $this->cache->set($cache_key, $odds_data, 300);
        }
        
        return $odds_data;
    }
    
    /**
     * Fetch odds for a specific event without cache (fresh data).
     *
     * @param string $event_name Event identifier.
     * @param string $market_type Market type (e.g., match_winner).
     * @param string $sport Sport key (e.g., 'soccer_epl', 'basketball_nba').
     * @return array Array of odds data by bookmaker.
     */
    public function fetch_odds_fresh($event_name, $market_type = 'match_winner', $sport = 'football') {
        // Use The Odds API for guaranteed real data without cache
        $odds_api_scraper = new TheOddsAPIScraper();
        
        // Scrape real odds from The Odds API without cache
        $odds_data = $odds_api_scraper->scrape_real_odds($event_name, $sport, $market_type);
        
        return $odds_data;
    }
    
    /**
     * Get all available sports.
     *
     * @return array Array of sports.
     */
    public function get_all_sports() {
        $odds_api_scraper = new TheOddsAPIScraper();
        return $odds_api_scraper->get_all_sports();
    }
    
    /**
     * Get all live events.
     *
     * @param int $limit Maximum number of events.
     * @return array Array of events.
     */
    public function get_all_live_events($limit = 50) {
        // Check cache first
        $cache_key = "all_live_events_{$limit}";
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        $odds_api_scraper = new TheOddsAPIScraper();
        $events = $odds_api_scraper->get_all_live_events($limit);
        
        // Cache for 2 minutes
        if (!empty($events)) {
            $this->cache->set($cache_key, $events, 120);
        }
        
        return $events;
    }
    
    /**
     * Filter odds data by market type.
     *
     * @param array $odds_data Raw odds data.
     * @param string $market_type Market type to filter.
     * @return array Filtered odds data.
     */
    private function filter_odds_by_market($odds_data, $market_type) {
        $filtered_data = [];
        
        // Map our market types to expected field names in the odds data
        $field_mapping = [
            'match_winner' => ['home', 'draw', 'away'],
            'over_under' => ['over_2_5', 'under_2_5'],
            'both_teams_score' => ['yes', 'no'],
            'handicap' => ['home_handicap', 'away_handicap'],
            'correct_score' => ['score_1_0', 'score_2_1', 'score_0_1']
        ];
        
        $expected_fields = $field_mapping[$market_type] ?? [];
        
        foreach ($odds_data as $bookmaker_id => $bookmaker_data) {
            if (isset($bookmaker_data['odds'])) {
                $has_required_fields = false;
                
                // Check if this bookmaker has any of the expected fields
                foreach ($expected_fields as $field) {
                    if (isset($bookmaker_data['odds'][$field]) && $bookmaker_data['odds'][$field] !== '-') {
                        $has_required_fields = true;
                        break;
                    }
                }
                
                if ($has_required_fields) {
                    // Filter to only include the relevant fields for this market
                    $filtered_bookmaker = $bookmaker_data;
                    $filtered_bookmaker['odds'] = [];
                    
                    foreach ($expected_fields as $field) {
                        if (isset($bookmaker_data['odds'][$field])) {
                            $filtered_bookmaker['odds'][$field] = $bookmaker_data['odds'][$field];
                        }
                    }
                    
                    $filtered_data[$bookmaker_id] = $filtered_bookmaker;
                }
            }
        }
        
        return $filtered_data;
    }
    
    /**
     * Get live events for a specific sport.
     *
     * @param string $sport_key Sport key.
     * @param int $limit Maximum number of events.
     * @return array Array of events.
     */
    public function get_live_events_by_sport($sport_key, $limit = 20) {
        // Check cache first
        $cache_key = "live_events_{$sport_key}_{$limit}";
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        $odds_api_scraper = new TheOddsAPIScraper();
        $events = $odds_api_scraper->get_live_events_by_sport($sport_key, $limit);
        
        // Cache for 2 minutes
        if (!empty($events)) {
            $this->cache->set($cache_key, $events, 120);
        }
        
        return $events;
    }
    
    /**
     * Convert market type to Oddschecker format.
     *
     * @param string $market_type Our market type.
     * @return string Oddschecker market type.
     */
    private function convert_market_type($market_type) {
        $market_mapping = [
            'match_winner' => 'match-odds',
            'over_under' => 'over-under',
            'both_teams_score' => 'both-teams-to-score',
            'handicap' => 'asian-handicap',
            'correct_score' => 'correct-score',
        ];
        
        return $market_mapping[$market_type] ?? 'match-odds';
    }
    
    /**
     * Scrape odds from specified source.
     *
     * @param string $source_name Source name.
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Odds data.
     */
    private function scrape_from_source($source_name, $event_name, $market_type) {
        // Use factory to create scraper instance.
        $scraper = \OddsComparison\Core\ScrapingSources\ScrapingSourceFactory::create($source_name);
        
        if (!$scraper) {
            error_log("Failed to create scraper for source: {$source_name}");
            return $this->get_fallback_odds(get_option('odds_comparison_bookmakers', []), $market_type);
        }
        
        // Check if source is available.
        if (!$scraper->is_available()) {
            error_log("Scraping source not available: {$source_name}");
            return $this->get_fallback_odds(get_option('odds_comparison_bookmakers', []), $market_type);
        }
        
        // Check if market is supported.
        $supported_markets = $scraper->get_supported_markets();
        if (!in_array($market_type, $supported_markets)) {
            error_log("Market type not supported by source {$source_name}: {$market_type}");
            return $this->get_fallback_odds(get_option('odds_comparison_bookmakers', []), $market_type);
        }
        
        // Apply rate limiting.
        $delay = $scraper->get_rate_limit_delay();
        if ($delay > 0) {
            usleep($delay * 1000000);
        }
        
        // Scrape the odds.
        $scraped_odds = $scraper->scrape_odds($event_name, $market_type);
        
        if (empty($scraped_odds)) {
            error_log("No odds scraped from source: {$source_name}");
            return $this->get_fallback_odds(get_option('odds_comparison_bookmakers', []), $market_type);
        }
        
        // Map scraped odds to our bookmaker format.
        return $this->map_scraped_odds($scraped_odds, $market_type);
    }
    
    /**
     * Map scraped odds to our bookmaker format.
     *
     * @param array $scraped_odds Scraped odds data.
     * @param string $market_type Market type.
     * @return array Mapped odds data.
     */
    private function map_scraped_odds($scraped_odds, $market_type) {
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        $mapped_odds = [];
        
        foreach ($bookmakers as $bookmaker_id => $bookmaker) {
            if (!$bookmaker['enabled']) {
                continue;
            }
            
            $bookmaker_name = $bookmaker['name'];
            
            // Try to find matching odds in scraped data.
            $odds = $this->find_matching_odds($scraped_odds, $bookmaker_name);
            
            if ($odds) {
                $mapped_odds[$bookmaker_id] = [
                    'bookmaker' => $bookmaker_name,
                    'url' => $bookmaker['affiliate_link'] ?: $bookmaker['url'],
                    'odds' => $odds,
                    'last_updated' => current_time('mysql'),
                ];
            } else {
                // Use fallback if no match found.
                $mapped_odds[$bookmaker_id] = $this->generate_sample_odds($bookmaker_id, $market_type);
            }
        }
        
        return $mapped_odds;
    }
    
    /**
     * Find matching odds for bookmaker.
     *
     * @param array $scraped_odds Scraped odds data.
     * @param string $bookmaker_name Bookmaker name.
     * @return array|false Matching odds or false.
     */
    private function find_matching_odds($scraped_odds, $bookmaker_name) {
        // Try exact match first.
        if (isset($scraped_odds[$bookmaker_name])) {
            return $scraped_odds[$bookmaker_name];
        }
        
        // Try partial matches.
        foreach ($scraped_odds as $scraped_name => $odds) {
            if (stripos($scraped_name, $bookmaker_name) !== false || 
                stripos($bookmaker_name, $scraped_name) !== false) {
                return $odds;
            }
        }
        
        return false;
    }
    
    /**
     * Scrape odds from Oddschecker.
     *
     * This method scrapes real odds from Oddschecker.com
     * with proper error handling and rate limiting.
     *
     * @param string $event_name Event identifier.
     * @param string $market_type Market type.
     * @return array Array of odds data.
     */
    private function scrape_oddschecker($event_name, $market_type) {
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        $odds_data = [];
        
        // Convert event name to Oddschecker URL format
        $oddschecker_url = $this->build_oddschecker_url($event_name, $market_type);
        
        if (!$oddschecker_url) {
            error_log('Odds Scraper: Could not build Oddschecker URL for event: ' . $event_name);
            return $this->get_fallback_odds($bookmakers, $market_type);
        }
        
        // Fetch the page
        $html = $this->make_request($oddschecker_url);
        
        if (!$html) {
            error_log('Odds Scraper: Failed to fetch Oddschecker page: ' . $oddschecker_url);
            return $this->get_fallback_odds($bookmakers, $market_type);
        }
        
        // Parse the HTML to extract odds
        $parsed_odds = $this->parse_oddschecker_html($html, $market_type);
        
        if (empty($parsed_odds)) {
            error_log('Odds Scraper: No odds found in Oddschecker response');
            return $this->get_fallback_odds($bookmakers, $market_type);
        }
        
        // Map parsed odds to our bookmakers
        foreach ($bookmakers as $bookmaker_id => $bookmaker) {
            if (!$bookmaker['enabled']) {
                continue;
            }
            
            $bookmaker_name = $bookmaker['name'];
            $odds_data[$bookmaker_id] = $this->map_bookmaker_odds($parsed_odds, $bookmaker_name, $bookmaker);
            
            // Rate limiting between bookmaker processing
            usleep($this->rate_limit_delay * 100000);
        }
        
        return $odds_data;
    }
    
    /**
     * Generate sample odds data for demonstration.
     *
     * @param string $bookmaker_id Bookmaker identifier.
     * @param string $market_type Market type.
     * @return array Sample odds data.
     */
    private function generate_sample_odds($bookmaker_id, $market_type) {
        $base_odds = [
            'match_winner' => [
                'home' => rand(150, 300) / 100,
                'draw' => rand(300, 400) / 100,
                'away' => rand(200, 350) / 100,
            ],
            'over_under' => [
                'over_2_5' => rand(170, 220) / 100,
                'under_2_5' => rand(170, 220) / 100,
            ],
            'both_teams_score' => [
                'yes' => rand(160, 200) / 100,
                'no' => rand(180, 220) / 100,
            ],
        ];
        
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        $bookmaker_name = $bookmakers[$bookmaker_id]['name'] ?? 'Unknown';
        $bookmaker_url = $bookmakers[$bookmaker_id]['affiliate_link'] ?: $bookmakers[$bookmaker_id]['url'];
        
        return [
            'bookmaker' => $bookmaker_name,
            'url' => $bookmaker_url,
            'odds' => $base_odds[$market_type] ?? [],
            'last_updated' => current_time('mysql'),
        ];
    }
    
    /**
     * Fetch and cache odds for all configured events.
     *
     * @return void
     */
    public function fetch_and_cache_odds() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'odds_comparison_data';
        
        // Get unique events from the database.
        $events = $wpdb->get_results(
            "SELECT DISTINCT event_name, market_type FROM $table_name LIMIT 100",
            ARRAY_A
        );
        
        foreach ($events as $event) {
            $odds_data = $this->fetch_odds($event['event_name'], $event['market_type']);
            
            // Update database with fresh data.
            foreach ($odds_data as $bookmaker_id => $data) {
                $this->save_odds_to_database(
                    $bookmaker_id,
                    $event['event_name'],
                    $event['market_type'],
                    $data
                );
            }
        }
    }
    
    /**
     * Save odds data to database.
     *
     * @param string $bookmaker Bookmaker identifier.
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @param array $odds_data Odds data to save.
     * @return int|false Insert/update ID or false on failure.
     */
    public function save_odds_to_database($bookmaker, $event_name, $market_type, $odds_data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'odds_comparison_data';
        
        // Check if record exists.
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name 
                 WHERE bookmaker = %s AND event_name = %s AND market_type = %s",
                $bookmaker,
                $event_name,
                $market_type
            )
        );
        
        $data = [
            'bookmaker' => $bookmaker,
            'event_name' => $event_name,
            'market_type' => $market_type,
            'odds_data' => wp_json_encode($odds_data),
            'last_updated' => current_time('mysql'),
        ];
        
        if ($existing) {
            // Update existing record.
            return $wpdb->update(
                $table_name,
                $data,
                ['id' => $existing->id]
            );
        } else {
            // Insert new record.
            $wpdb->insert($table_name, $data);
            return $wpdb->insert_id;
        }
    }
    
    /**
     * Get odds from database.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Array of odds data by bookmaker.
     */
    public function get_odds_from_database($event_name, $market_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'odds_comparison_data';
        
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name 
                 WHERE event_name = %s AND market_type = %s
                 ORDER BY last_updated DESC",
                $event_name,
                $market_type
            ),
            ARRAY_A
        );
        
        $odds_data = [];
        
        foreach ($results as $row) {
            $odds_data[$row['bookmaker']] = json_decode($row['odds_data'], true);
        }
        
        return $odds_data;
    }
    
    /**
     * Build Oddschecker URL for the given event and market.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return string|false Oddschecker URL or false on failure.
     */
    private function build_oddschecker_url($event_name, $market_type) {
        // Convert event name to URL-friendly format
        $url_slug = $this->convert_event_to_slug($event_name);
        
        // Map market types to Oddschecker paths
        $market_paths = [
            'match_winner' => 'match-odds',
            'over_under' => 'over-under',
            'both_teams_score' => 'both-teams-to-score',
            'handicap' => 'asian-handicap',
            'correct_score' => 'correct-score',
        ];
        
        $market_path = $market_paths[$market_type] ?? 'match-odds';
        
        // Build the URL
        $base_url = 'https://www.oddschecker.com/football/';
        $url = $base_url . $url_slug . '/' . $market_path;
        
        return $url;
    }
    
    /**
     * Convert event name to URL slug.
     *
     * @param string $event_name Event name.
     * @return string URL slug.
     */
    private function convert_event_to_slug($event_name) {
        // Convert "Chelsea vs Arsenal" to "chelsea-v-arsenal"
        $slug = strtolower($event_name);
        $slug = str_replace([' vs ', ' v ', ' against '], '-v-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Parse Oddschecker HTML to extract odds data.
     *
     * @param string $html HTML content.
     * @param string $market_type Market type.
     * @return array Parsed odds data.
     */
    private function parse_oddschecker_html($html, $market_type) {
        $odds_data = [];
        
        // Use DOMDocument to parse HTML
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Find the odds table
        $odds_table = $xpath->query('//table[@class="eventTable"]')->item(0);
        
        if (!$odds_table) {
            return $odds_data;
        }
        
        // Extract bookmaker names and odds
        $rows = $xpath->query('.//tr[contains(@class, "diff-row")]', $odds_table);
        
        foreach ($rows as $row) {
            $bookmaker_name = $this->extract_bookmaker_name($xpath, $row);
            $odds_values = $this->extract_odds_values($xpath, $row, $market_type);
            
            if ($bookmaker_name && !empty($odds_values)) {
                $odds_data[$bookmaker_name] = $odds_values;
            }
        }
        
        return $odds_data;
    }
    
    /**
     * Extract bookmaker name from table row.
     *
     * @param DOMXPath $xpath XPath object.
     * @param DOMElement $row Table row element.
     * @return string|false Bookmaker name or false.
     */
    private function extract_bookmaker_name($xpath, $row) {
        $bookmaker_cell = $xpath->query('.//td[contains(@class, "bk-logo")]', $row)->item(0);
        
        if (!$bookmaker_cell) {
            return false;
        }
        
        $img = $xpath->query('.//img', $bookmaker_cell)->item(0);
        
        if ($img) {
            $alt = $img->getAttribute('alt');
            return $alt ?: $img->getAttribute('title');
        }
        
        return trim($bookmaker_cell->textContent);
    }
    
    /**
     * Extract odds values from table row.
     *
     * @param DOMXPath $xpath XPath object.
     * @param DOMElement $row Table row element.
     * @param string $market_type Market type.
     * @return array Odds values.
     */
    private function extract_odds_values($xpath, $row, $market_type) {
        $odds_values = [];
        
        // Find odds cells (skip first cell which is bookmaker name)
        $odds_cells = $xpath->query('.//td[contains(@class, "o")]', $row);
        
        if ($market_type === 'match_winner') {
            // For match winner: Home, Draw, Away
            if ($odds_cells->length >= 3) {
                $odds_values['home'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
                $odds_values['draw'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
                $odds_values['away'] = $this->parse_odds_value($odds_cells->item(2)->textContent);
            }
        } elseif ($market_type === 'over_under') {
            // For over/under: Over, Under
            if ($odds_cells->length >= 2) {
                $odds_values['over_2_5'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
                $odds_values['under_2_5'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
            }
        } elseif ($market_type === 'both_teams_score') {
            // For both teams score: Yes, No
            if ($odds_cells->length >= 2) {
                $odds_values['yes'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
                $odds_values['no'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
            }
        }
        
        return $odds_values;
    }
    
    /**
     * Parse odds value from text.
     *
     * @param string $odds_text Odds text (e.g., "2.50", "5/2", "+150").
     * @return float|false Parsed decimal odds or false.
     */
    private function parse_odds_value($odds_text) {
        $odds_text = trim($odds_text);
        
        if (empty($odds_text) || $odds_text === '-') {
            return false;
        }
        
        // Handle decimal odds
        if (is_numeric($odds_text)) {
            return (float) $odds_text;
        }
        
        // Handle fractional odds (e.g., "5/2")
        if (preg_match('/^(\d+)\/(\d+)$/', $odds_text, $matches)) {
            $numerator = (float) $matches[1];
            $denominator = (float) $matches[2];
            
            if ($denominator > 0) {
                return ($numerator / $denominator) + 1;
            }
        }
        
        // Handle American odds (e.g., "+150", "-200")
        if (preg_match('/^([+-]?\d+)$/', $odds_text, $matches)) {
            $american = (int) $matches[1];
            
            if ($american > 0) {
                return ($american / 100) + 1;
            } else {
                return (100 / abs($american)) + 1;
            }
        }
        
        return false;
    }
    
    /**
     * Map bookmaker odds from parsed data.
     *
     * @param array $parsed_odds Parsed odds data.
     * @param string $bookmaker_name Bookmaker name.
     * @param array $bookmaker_config Bookmaker configuration.
     * @return array Mapped odds data.
     */
    private function map_bookmaker_odds($parsed_odds, $bookmaker_name, $bookmaker_config) {
        // Try to find exact match first
        if (isset($parsed_odds[$bookmaker_name])) {
            return [
                'bookmaker' => $bookmaker_name,
                'url' => $bookmaker_config['affiliate_link'] ?: $bookmaker_config['url'],
                'odds' => $parsed_odds[$bookmaker_name],
                'last_updated' => current_time('mysql'),
            ];
        }
        
        // Try partial matches
        foreach ($parsed_odds as $parsed_name => $odds) {
            if (stripos($parsed_name, $bookmaker_name) !== false || 
                stripos($bookmaker_name, $parsed_name) !== false) {
                return [
                    'bookmaker' => $bookmaker_name,
                    'url' => $bookmaker_config['affiliate_link'] ?: $bookmaker_config['url'],
                    'odds' => $odds,
                    'last_updated' => current_time('mysql'),
                ];
            }
        }
        
        // Return fallback if no match found
        return $this->generate_sample_odds($bookmaker_name, 'match_winner');
    }
    
    /**
     * Get fallback odds when scraping fails.
     *
     * @param array $bookmakers Bookmakers configuration.
     * @param string $market_type Market type.
     * @return array Fallback odds data.
     */
    private function get_fallback_odds($bookmakers, $market_type) {
        $odds_data = [];
        
        foreach ($bookmakers as $bookmaker_id => $bookmaker) {
            if (!$bookmaker['enabled']) {
                continue;
            }
            
            $odds_data[$bookmaker_id] = $this->generate_sample_odds($bookmaker_id, $market_type);
        }
        
        return $odds_data;
    }
    
    /**
     * Make HTTP request with proper headers and error handling.
     *
     * @param string $url URL to fetch.
     * @return string|false Response body or false on failure.
     */
    private function make_request($url) {
        $args = [
            'timeout' => 15,
            'user-agent' => $this->user_agent,
            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1',
            ],
            'sslverify' => true,
            'redirection' => 5,
        ];
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            error_log('Odds Scraper Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            error_log("Odds Scraper Error: HTTP {$status_code} for URL {$url}");
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
}

