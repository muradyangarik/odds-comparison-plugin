<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation tasks such as creating database tables,
 * setting default options, and scheduling cron jobs.
 *
 * @package OddsComparison
 * @since 1.0.0
 */

namespace OddsComparison;

/**
 * Class Activator
 */
class Activator {
    
    /**
     * Activate the plugin.
     *
     * @return void
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();
        self::schedule_cron_jobs();
        self::set_capabilities();
        
        // Flush rewrite rules.
        flush_rewrite_rules();
    }
    
    /**
     * Create custom database tables.
     *
     * @return void
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'odds_comparison_data';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            bookmaker varchar(100) NOT NULL,
            event_name varchar(255) NOT NULL,
            market_type varchar(100) NOT NULL,
            odds_data longtext NOT NULL,
            odds_format varchar(20) DEFAULT 'decimal',
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY bookmaker (bookmaker),
            KEY event_name (event_name),
            KEY market_type (market_type),
            KEY last_updated (last_updated)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options.
     *
     * @return void
     */
    private static function set_default_options() {
        $default_options = [
            'odds_comparison_version' => ODDS_COMPARISON_VERSION,
            'odds_comparison_bookmakers' => [
                'bet365' => [
                    'enabled' => true,
                    'name' => 'Bet365',
                    'url' => 'https://www.bet365.com',
                    'affiliate_link' => '',
                ],
                'betfair' => [
                    'enabled' => true,
                    'name' => 'Betfair',
                    'url' => 'https://www.betfair.com',
                    'affiliate_link' => '',
                ],
                'williamhill' => [
                    'enabled' => true,
                    'name' => 'William Hill',
                    'url' => 'https://www.williamhill.com',
                    'affiliate_link' => '',
                ],
                'ladbrokes' => [
                    'enabled' => true,
                    'name' => 'Ladbrokes',
                    'url' => 'https://www.ladbrokes.com',
                    'affiliate_link' => '',
                ],
                'paddypower' => [
                    'enabled' => true,
                    'name' => 'Paddy Power',
                    'url' => 'https://www.paddypower.com',
                    'affiliate_link' => '',
                ],
                'coral' => [
                    'enabled' => true,
                    'name' => 'Coral',
                    'url' => 'https://www.coral.co.uk',
                    'affiliate_link' => '',
                ],
                'skybet' => [
                    'enabled' => true,
                    'name' => 'Sky Bet',
                    'url' => 'https://www.skybet.com',
                    'affiliate_link' => '',
                ],
                'unibet' => [
                    'enabled' => true,
                    'name' => 'Unibet',
                    'url' => 'https://www.unibet.com',
                    'affiliate_link' => '',
                ],
                'betvictor' => [
                    'enabled' => true,
                    'name' => 'BetVictor',
                    'url' => 'https://www.betvictor.com',
                    'affiliate_link' => '',
                ],
                '888sport' => [
                    'enabled' => true,
                    'name' => '888sport',
                    'url' => 'https://www.888sport.com',
                    'affiliate_link' => '',
                ],
            ],
            'odds_comparison_markets' => [
                'match_winner' => ['enabled' => true, 'label' => 'Match Winner'],
                'over_under' => ['enabled' => true, 'label' => 'Over/Under'],
                'both_teams_score' => ['enabled' => true, 'label' => 'Both Teams to Score'],
                'handicap' => ['enabled' => true, 'label' => 'Handicap'],
                'correct_score' => ['enabled' => false, 'label' => 'Correct Score'],
            ],
            'odds_comparison_default_format' => 'decimal',
            'odds_comparison_default_market' => '',
            'odds_comparison_cache_duration' => 300, // 5 minutes
            'odds_comparison_update_frequency' => 'five_minutes',
        ];
        
        foreach ($default_options as $option_name => $option_value) {
            if (!get_option($option_name)) {
                add_option($option_name, $option_value);
            }
        }
    }
    
    /**
     * Schedule cron jobs for automatic odds updates.
     *
     * @return void
     */
    private static function schedule_cron_jobs() {
        if (!wp_next_scheduled('odds_comparison_update_odds')) {
            wp_schedule_event(time(), 'five_minutes', 'odds_comparison_update_odds');
        }
    }
    
    /**
     * Set user capabilities for managing odds comparison.
     *
     * @return void
     */
    private static function set_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $role->add_cap('manage_odds_comparison');
        }
    }
}


