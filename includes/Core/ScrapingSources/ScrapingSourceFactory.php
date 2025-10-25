<?php
/**
 * Scraping Source Factory Class
 *
 * Factory pattern implementation for creating different scraping sources.
 * Makes the system modular and scalable for additional bookmakers.
 *
 * @package OddsComparison\Core\ScrapingSources
 * @since 1.0.0
 */

namespace OddsComparison\Core\ScrapingSources;

/**
 * Class ScrapingSourceFactory
 *
 * Creates appropriate scraping source instances based on configuration.
 */
class ScrapingSourceFactory {
    
    /**
     * Available scraping sources.
     *
     * @var array
     */
    private static $sources = [
        'oddschecker' => OddscheckerScraper::class,
        'bet365' => Bet365Scraper::class,
        'betfair' => BetfairScraper::class,
        'williamhill' => WilliamHillScraper::class,
        'ladbrokes' => LadbrokesScraper::class,
        'paddypower' => PaddyPowerScraper::class,
        'coral' => CoralScraper::class,
        'skybet' => SkyBetScraper::class,
        'unibet' => UnibetScraper::class,
        'betvictor' => BetVictorScraper::class,
        '888sport' => EightEightEightSportScraper::class,
    ];
    
    /**
     * Create scraping source instance.
     *
     * @param string $source_name Source name.
     * @return ScrapingSourceInterface|false Scraper instance or false.
     */
    public static function create($source_name) {
        if (!isset(self::$sources[$source_name])) {
            error_log("Unknown scraping source: {$source_name}");
            return false;
        }
        
        $class_name = self::$sources[$source_name];
        
        if (!class_exists($class_name)) {
            error_log("Scraping source class not found: {$class_name}");
            return false;
        }
        
        return new $class_name();
    }
    
    /**
     * Get all available sources.
     *
     * @return array Available sources.
     */
    public static function get_available_sources() {
        return array_keys(self::$sources);
    }
    
    /**
     * Register new scraping source.
     *
     * @param string $name Source name.
     * @param string $class_name Class name.
     * @return void
     */
    public static function register_source($name, $class_name) {
        if (!class_exists($class_name)) {
            error_log("Cannot register scraping source: Class {$class_name} not found");
            return;
        }
        
        if (!is_subclass_of($class_name, ScrapingSourceInterface::class)) {
            error_log("Cannot register scraping source: Class {$class_name} must implement ScrapingSourceInterface");
            return;
        }
        
        self::$sources[$name] = $class_name;
    }
    
    /**
     * Unregister scraping source.
     *
     * @param string $name Source name.
     * @return void
     */
    public static function unregister_source($name) {
        unset(self::$sources[$name]);
    }
}




