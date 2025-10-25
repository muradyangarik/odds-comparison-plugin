<?php
/**
 * Oddschecker Scraper Class
 *
 * Handles scraping odds specifically from Oddschecker.com
 * with specialized parsing for their HTML structure.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Class OddscheckerScraper
 *
 * Specialized scraper for Oddschecker.com
 */
class OddscheckerScraper implements ScrapingSourceInterface {
    
    /**
     * Base URL for Oddschecker.
     *
     * @var string
     */
    private $base_url = 'https://www.oddschecker.com';
    
    /**
     * User agent for requests.
     *
     * @var string
     */
    private $user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    /**
     * Rate limiting delay in seconds.
     *
     * @var int
     */
    private $rate_limit_delay = 2;
    
    /**
     * Scrape odds from Oddschecker.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Scraped odds data.
     */
    public function scrape_odds($event_name, $market_type) {
        $url = $this->build_url($event_name, $market_type);
        
        if (!$url) {
            return [];
        }
        
        $html = $this->make_request($url);
        
        if (!$html) {
            return [];
        }
        
        return $this->parse_html($html, $market_type);
    }
    
    /**
     * Build Oddschecker URL.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return string|false URL or false on failure.
     */
    private function build_url($event_name, $market_type) {
        $slug = $this->convert_to_slug($event_name);
        $market_path = $this->get_market_path($market_type);
        
        return $this->base_url . '/football/' . $slug . '/' . $market_path;
    }
    
    /**
     * Convert event name to URL slug.
     *
     * @param string $event_name Event name.
     * @return string URL slug.
     */
    private function convert_to_slug($event_name) {
        $slug = strtolower($event_name);
        $slug = str_replace([' vs ', ' v ', ' against '], '-v-', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Get market path for URL.
     *
     * @param string $market_type Market type.
     * @return string Market path.
     */
    private function get_market_path($market_type) {
        $paths = [
            'match_winner' => 'match-odds',
            'over_under' => 'over-under',
            'both_teams_score' => 'both-teams-to-score',
            'handicap' => 'asian-handicap',
            'correct_score' => 'correct-score',
        ];
        
        return $paths[$market_type] ?? 'match-odds';
    }
    
    /**
     * Parse HTML content.
     *
     * @param string $html HTML content.
     * @param string $market_type Market type.
     * @return array Parsed odds data.
     */
    private function parse_html($html, $market_type) {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new \DOMXPath($dom);
        
        // Find odds table
        $table = $xpath->query('//table[@class="eventTable"]')->item(0);
        
        if (!$table) {
            return [];
        }
        
        $odds_data = [];
        $rows = $xpath->query('.//tr[contains(@class, "diff-row")]', $table);
        
        foreach ($rows as $row) {
            $bookmaker = $this->extract_bookmaker($xpath, $row);
            $odds = $this->extract_odds($xpath, $row, $market_type);
            
            if ($bookmaker && !empty($odds)) {
                $odds_data[$bookmaker] = $odds;
            }
        }
        
        return $odds_data;
    }
    
    /**
     * Extract bookmaker name from row.
     *
     * @param \DOMXPath $xpath XPath object.
     * @param \DOMElement $row Table row.
     * @return string|false Bookmaker name or false.
     */
    private function extract_bookmaker($xpath, $row) {
        $cell = $xpath->query('.//td[contains(@class, "bk-logo")]', $row)->item(0);
        
        if (!$cell) {
            return false;
        }
        
        $img = $xpath->query('.//img', $cell)->item(0);
        
        if ($img) {
            return $img->getAttribute('alt') ?: $img->getAttribute('title');
        }
        
        return trim($cell->textContent);
    }
    
    /**
     * Extract odds from row.
     *
     * @param \DOMXPath $xpath XPath object.
     * @param \DOMElement $row Table row.
     * @param string $market_type Market type.
     * @return array Odds values.
     */
    private function extract_odds($xpath, $row, $market_type) {
        $odds_cells = $xpath->query('.//td[contains(@class, "o")]', $row);
        $odds = [];
        
        if ($market_type === 'match_winner' && $odds_cells->length >= 3) {
            $odds['home'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
            $odds['draw'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
            $odds['away'] = $this->parse_odds_value($odds_cells->item(2)->textContent);
        } elseif ($market_type === 'over_under' && $odds_cells->length >= 2) {
            $odds['over_2_5'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
            $odds['under_2_5'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
        } elseif ($market_type === 'both_teams_score' && $odds_cells->length >= 2) {
            $odds['yes'] = $this->parse_odds_value($odds_cells->item(0)->textContent);
            $odds['no'] = $this->parse_odds_value($odds_cells->item(1)->textContent);
        }
        
        return $odds;
    }
    
    /**
     * Parse odds value from text.
     *
     * @param string $text Odds text.
     * @return float|false Parsed decimal odds or false.
     */
    private function parse_odds_value($text) {
        $text = trim($text);
        
        if (empty($text) || $text === '-') {
            return false;
        }
        
        // Decimal odds
        if (is_numeric($text)) {
            return (float) $text;
        }
        
        // Fractional odds
        if (preg_match('/^(\d+)\/(\d+)$/', $text, $matches)) {
            $numerator = (float) $matches[1];
            $denominator = (float) $matches[2];
            
            if ($denominator > 0) {
                return ($numerator / $denominator) + 1;
            }
        }
        
        // American odds
        if (preg_match('/^([+-]?\d+)$/', $text, $matches)) {
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
            error_log('Oddschecker Scraper Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            error_log("Oddschecker Scraper Error: HTTP {$status_code} for URL {$url}");
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Get the source name.
     *
     * @return string Source name.
     */
    public function get_source_name() {
        return 'oddschecker';
    }
    
    /**
     * Check if the source is available.
     *
     * @return bool True if available, false otherwise.
     */
    public function is_available() {
        // Test if we can reach Oddschecker
        $test_url = $this->base_url . '/football';
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
