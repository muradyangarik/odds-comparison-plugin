<?php
/**
 * Plugin Deactivator
 *
 * Handles plugin deactivation tasks such as clearing scheduled events
 * and cleaning up temporary data.
 *
 * @package OddsComparison
 * @since 1.0.0
 */

namespace OddsComparison;

/**
 * Class Deactivator
 */
class Deactivator {
    
    /**
     * Deactivate the plugin.
     *
     * @return void
     */
    public static function deactivate() {
        self::clear_scheduled_events();
        self::clear_transients();
        
        // Flush rewrite rules.
        flush_rewrite_rules();
    }
    
    /**
     * Clear all scheduled cron events.
     *
     * @return void
     */
    private static function clear_scheduled_events() {
        $timestamp = wp_next_scheduled('odds_comparison_update_odds');
        
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'odds_comparison_update_odds');
        }
    }
    
    /**
     * Clear all plugin transients.
     *
     * @return void
     */
    private static function clear_transients() {
        global $wpdb;
        
        // Delete all transients related to odds comparison.
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_odds_comparison_%' 
             OR option_name LIKE '_transient_timeout_odds_comparison_%'"
        );
    }
}


