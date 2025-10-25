<?php
/**
 * Bookmaker Scrapers
 *
 * Placeholder implementations for individual bookmaker scrapers.
 * These show how the modular system can be extended.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Betfair Scraper
 */
class BetfairScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Betfair' => ['home' => 1.90, 'draw' => 3.30, 'away' => 2.80]];
    }
    public function get_source_name() { return 'betfair'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * William Hill Scraper
 */
class WilliamHillScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['William Hill' => ['home' => 1.88, 'draw' => 3.35, 'away' => 2.85]];
    }
    public function get_source_name() { return 'williamhill'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under', 'both_teams_score']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * Ladbrokes Scraper
 */
class LadbrokesScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Ladbrokes' => ['home' => 1.87, 'draw' => 3.40, 'away' => 2.90]];
    }
    public function get_source_name() { return 'ladbrokes'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * Paddy Power Scraper
 */
class PaddyPowerScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Paddy Power' => ['home' => 1.89, 'draw' => 3.32, 'away' => 2.88]];
    }
    public function get_source_name() { return 'paddypower'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under', 'both_teams_score']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * Coral Scraper
 */
class CoralScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Coral' => ['home' => 1.86, 'draw' => 3.38, 'away' => 2.92]];
    }
    public function get_source_name() { return 'coral'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * Sky Bet Scraper
 */
class SkyBetScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Sky Bet' => ['home' => 1.91, 'draw' => 3.28, 'away' => 2.82]];
    }
    public function get_source_name() { return 'skybet'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under', 'both_teams_score']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * Unibet Scraper
 */
class UnibetScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['Unibet' => ['home' => 1.88, 'draw' => 3.33, 'away' => 2.87]];
    }
    public function get_source_name() { return 'unibet'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * BetVictor Scraper
 */
class BetVictorScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['BetVictor' => ['home' => 1.90, 'draw' => 3.30, 'away' => 2.80]];
    }
    public function get_source_name() { return 'betvictor'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under', 'both_teams_score']; }
    public function get_rate_limit_delay() { return 2; }
}

/**
 * 888sport Scraper
 */
class EightEightEightSportScraper implements ScrapingSourceInterface {
    public function scrape_odds($event_name, $market_type) {
        return ['888sport' => ['home' => 1.89, 'draw' => 3.31, 'away' => 2.89]];
    }
    public function get_source_name() { return '888sport'; }
    public function is_available() { return true; }
    public function get_supported_markets() { return ['match_winner', 'over_under']; }
    public function get_rate_limit_delay() { return 2; }
}




