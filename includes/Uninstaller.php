<?php
/**
 * Plugin Uninstaller
 *
 * Handles complete plugin removal, including database tables,
 * options, and user metadata.
 *
 * @package OddsComparison
 * @since 1.0.0
 */

namespace OddsComparison;

/**
 * Class Uninstaller
 */
class Uninstaller {
    
    /**
     * Uninstall the plugin.
     *
     * @return void
     */
    public static function uninstall() {
        // Check if the user has the required capability.
        if (!current_user_can('activate_plugins')) {
            return;
        }
        
        self::drop_tables();
        self::delete_options();
        self::remove_capabilities();
    }
    
    /**
     * Drop custom database tables.
     *
     * @return void
     */
    private static function drop_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'odds_comparison_data';
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    
    /**
     * Delete all plugin options.
     *
     * @return void
     */
    private static function delete_options() {
        delete_option('odds_comparison_version');
        delete_option('odds_comparison_bookmakers');
        delete_option('odds_comparison_markets');
        delete_option('odds_comparison_default_format');
        delete_option('odds_comparison_cache_duration');
        delete_option('odds_comparison_update_frequency');
    }
    
    /**
     * Remove custom capabilities.
     *
     * @return void
     */
    private static function remove_capabilities() {
        $role = get_role('administrator');
        
        if ($role) {
            $role->remove_cap('manage_odds_comparison');
        }
    }
}


