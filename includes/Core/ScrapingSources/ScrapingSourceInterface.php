<?php
/**
 * Scraping Source Interface
 *
 * Defines the contract for all scraping source implementations.
 * Ensures consistency and modularity across different sources.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Interface ScrapingSourceInterface
 *
 * Contract for all scraping source implementations.
 */
interface ScrapingSourceInterface {
    
    /**
     * Scrape odds for a specific event and market.
     *
     * @param string $event_name Event name.
     * @param string $market_type Market type.
     * @return array Scraped odds data.
     */
    public function scrape_odds($event_name, $market_type);
    
    /**
     * Get the source name.
     *
     * @return string Source name.
     */
    public function get_source_name();
    
    /**
     * Check if the source is available.
     *
     * @return bool True if available, false otherwise.
     */
    public function is_available();
    
    /**
     * Get supported markets for this source.
     *
     * @return array Supported market types.
     */
    public function get_supported_markets();
    
    /**
     * Get rate limit delay for this source.
     *
     * @return int Delay in seconds.
     */
    public function get_rate_limit_delay();
}




