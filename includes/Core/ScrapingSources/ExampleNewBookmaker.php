<?php
/**
 * Example: Adding a New Bookmaker Scraper
 *
 * This demonstrates how easy it is to add new bookmakers
 * to the modular and scalable system.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Class ExampleNewBookmakerScraper
 *
 * Example implementation showing how to add a new bookmaker.
 * This could be any bookmaker like DraftKings, FanDuel, etc.
 */
class ExampleNewBookmakerScraper implements ScrapingSourceInterface {
    
    /**
     * Base URL for the new bookmaker.
     *
     * @var string
     */
    private $base_url = 'https://www.newbookmaker.com';
    
    /**
     * User agent for requests.
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
     * Scrape odds from the new bookmaker.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Scraped odds data.
     */
    public function scrape_odds($event_name, $market_type) {
        // Method 1: API Integration (Recommended)
        if ($this->has_api_access()) {
            return $this->scrape_via_api($event_name, $market_type);
        }
        
        // Method 2: Web Scraping (Fallback)
        return $this->scrape_via_web($event_name, $market_type);
    }
    
    /**
     * Scrape odds via API (preferred method).
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array API odds data.
     */
    private function scrape_via_api($event_name, $market_type) {
        $api_key = get_option('newbookmaker_api_key', '');
        
        if (empty($api_key)) {
            error_log('NewBookmaker API key not configured');
            return [];
        }
        
        $url = $this->build_api_url($event_name, $market_type, $api_key);
        $response = wp_remote_get($url, [
            'timeout' => 15,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Accept' => 'application/json',
            ],
        ]);
        
        if (is_wp_error($response)) {
            error_log('NewBookmaker API Error: ' . $response->get_error_message());
            return [];
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return $this->parse_api_response($data, $market_type);
    }
    
    /**
     * Scrape odds via web scraping (fallback method).
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Scraped odds data.
     */
    private function scrape_via_web($event_name, $market_type) {
        $url = $this->build_web_url($event_name, $market_type);
        $html = $this->make_request($url);
        
        if (!$html) {
            return [];
        }
        
        return $this->parse_web_html($html, $market_type);
    }
    
    /**
     * Build API URL.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @param string $api_key API key.
     * @return string API URL.
     */
    private function build_api_url($event_name, $market_type, $api_key) {
        $event_slug = $this->convert_to_slug($event_name);
        $market_slug = $this->convert_market_to_slug($market_type);
        
        return "{$this->base_url}/api/v1/odds/{$event_slug}/{$market_slug}?key={$api_key}";
    }
    
    /**
     * Build web URL.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return string Web URL.
     */
    private function build_web_url($event_name, $market_type) {
        $event_slug = $this->convert_to_slug($event_name);
        $market_slug = $this->convert_market_to_slug($market_type);
        
        return "{$this->base_url}/sports/football/{$event_slug}/{$market_slug}";
    }
    
    /**
     * Convert event name to URL slug.
     *
     * @param string $event_name Event name.
     * @return string URL slug.
     */
    private function convert_to_slug($event_name) {
        $slug = strtolower($event_name);
        $slug = str_replace([' vs ', ' v ', ' against '], '-vs-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Convert market type to URL slug.
     *
     * @param string $market_type Market type.
     * @return string Market slug.
     */
    private function convert_market_to_slug($market_type) {
        $market_slugs = [
            'match_winner' => 'match-winner',
            'over_under' => 'over-under',
            'both_teams_score' => 'both-teams-score',
            'handicap' => 'handicap',
            'correct_score' => 'correct-score',
        ];
        
        return $market_slugs[$market_type] ?? 'match-winner';
    }
    
    /**
     * Parse API response.
     *
     * @param array $data API response data.
     * @param string $market_type Market type.
     * @return array Parsed odds data.
     */
    private function parse_api_response($data, $market_type) {
        if (!isset($data['odds']) || !is_array($data['odds'])) {
            return [];
        }
        
        $odds_data = [];
        
        foreach ($data['odds'] as $bookmaker => $odds) {
            $odds_data[$bookmaker] = $this->format_odds($odds, $market_type);
        }
        
        return $odds_data;
    }
    
    /**
     * Parse web HTML.
     *
     * @param string $html HTML content.
     * @param string $market_type Market type.
     * @return array Parsed odds data.
     */
    private function parse_web_html($html, $market_type) {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Find odds table (adjust selectors based on actual site structure)
        $odds_table = $xpath->query('//table[@class="odds-table"]')->item(0);
        
        if (!$odds_table) {
            return [];
        }
        
        $odds_data = [];
        $rows = $xpath->query('.//tr[contains(@class, "odds-row")]', $odds_table);
        
        foreach ($rows as $row) {
            $bookmaker = $this->extract_bookmaker_name($xpath, $row);
            $odds = $this->extract_odds_values($xpath, $row, $market_type);
            
            if ($bookmaker && !empty($odds)) {
                $odds_data[$bookmaker] = $odds;
            }
        }
        
        return $odds_data;
    }
    
    /**
     * Format odds data.
     *
     * @param array $odds Raw odds data.
     * @param string $market_type Market type.
     * @return array Formatted odds.
     */
    private function format_odds($odds, $market_type) {
        if ($market_type === 'match_winner') {
            return [
                'home' => (float) ($odds['home'] ?? 0),
                'draw' => (float) ($odds['draw'] ?? 0),
                'away' => (float) ($odds['away'] ?? 0),
            ];
        } elseif ($market_type === 'over_under') {
            return [
                'over_2_5' => (float) ($odds['over'] ?? 0),
                'under_2_5' => (float) ($odds['under'] ?? 0),
            ];
        } elseif ($market_type === 'both_teams_score') {
            return [
                'yes' => (float) ($odds['yes'] ?? 0),
                'no' => (float) ($odds['no'] ?? 0),
            ];
        }
        
        return $odds;
    }
    
    /**
     * Extract bookmaker name from row.
     *
     * @param \DOMXPath $xpath XPath object.
     * @param \DOMElement $row Table row.
     * @return string|false Bookmaker name or false.
     */
    private function extract_bookmaker_name($xpath, $row) {
        $cell = $xpath->query('.//td[contains(@class, "bookmaker")]', $row)->item(0);
        
        if (!$cell) {
            return false;
        }
        
        return trim($cell->textContent);
    }
    
    /**
     * Extract odds values from row.
     *
     * @param \DOMXPath $xpath XPath object.
     * @param \DOMElement $row Table row.
     * @param string $market_type Market type.
     * @return array Odds values.
     */
    private function extract_odds_values($xpath, $row, $market_type) {
        $odds_cells = $xpath->query('.//td[contains(@class, "odds")]', $row);
        $odds = [];
        
        if ($market_type === 'match_winner' && $odds_cells->length >= 3) {
            $odds['home'] = (float) $odds_cells->item(0)->textContent;
            $odds['draw'] = (float) $odds_cells->item(1)->textContent;
            $odds['away'] = (float) $odds_cells->item(2)->textContent;
        } elseif ($market_type === 'over_under' && $odds_cells->length >= 2) {
            $odds['over_2_5'] = (float) $odds_cells->item(0)->textContent;
            $odds['under_2_5'] = (float) $odds_cells->item(1)->textContent;
        } elseif ($market_type === 'both_teams_score' && $odds_cells->length >= 2) {
            $odds['yes'] = (float) $odds_cells->item(0)->textContent;
            $odds['no'] = (float) $odds_cells->item(1)->textContent;
        }
        
        return $odds;
    }
    
    /**
     * Make HTTP request.
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
            error_log('NewBookmaker Scraper Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            error_log("NewBookmaker Scraper Error: HTTP {$status_code} for URL {$url}");
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Check if API access is available.
     *
     * @return bool True if API key is configured.
     */
    private function has_api_access() {
        return !empty(get_option('newbookmaker_api_key', ''));
    }
    
    /**
     * Get the source name.
     *
     * @return string Source name.
     */
    public function get_source_name() {
        return 'newbookmaker';
    }
    
    /**
     * Check if the source is available.
     *
     * @return bool True if available, false otherwise.
     */
    public function is_available() {
        // Test if we can reach the bookmaker
        $test_url = $this->base_url;
        $response = wp_remote_get($test_url, ['timeout' => 10]);
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Get supported markets for this source.
     *
     * @return array Supported market types.
     */
    public function get_supported_markets() {
        return [
            'match_winner',
            'over_under',
            'both_teams_score',
            'handicap',
            'correct_score',
        ];
    }
    
    /**
     * Get rate limit delay for this source.
     *
     * @return int Delay in seconds.
     */
    public function get_rate_limit_delay() {
        return $this->rate_limit_delay;
    }
}




