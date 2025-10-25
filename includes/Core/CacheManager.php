<?php
/**
 * Cache Manager Class
 *
 * Handles caching operations for odds data to improve performance
 * and reduce load on external services.
 *
 * @package OddsComparison\Core
 * @since 1.0.0
 */

namespace OddsComparison\Core;

/**
 * Class CacheManager
 *
 * Manages caching strategies for odds data using WordPress transients
 * and optionally object cache if available.
 */
class CacheManager {
    
    /**
     * Cache prefix for all odds-related transients.
     *
     * @var string
     */
    private $cache_prefix = 'odds_comparison_';
    
    /**
     * Default cache duration in seconds.
     *
     * @var int
     */
    private $default_duration;
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->default_duration = get_option('odds_comparison_cache_duration', 300);
    }
    
    /**
     * Get cached data.
     *
     * @param string $key Cache key.
     * @return mixed|false Cached data or false if not found.
     */
    public function get($key) {
        $cache_key = $this->get_cache_key($key);
        
        // Try object cache first if available.
        if (wp_using_ext_object_cache()) {
            $cached = wp_cache_get($cache_key, 'odds_comparison');
            if (false !== $cached) {
                return $cached;
            }
        }
        
        // Fallback to transients.
        return get_transient($cache_key);
    }
    
    /**
     * Set cached data.
     *
     * @param string $key Cache key.
     * @param mixed $data Data to cache.
     * @param int|null $duration Cache duration in seconds (null = default).
     * @return bool True on success, false on failure.
     */
    public function set($key, $data, $duration = null) {
        $cache_key = $this->get_cache_key($key);
        $duration = $duration ?? $this->default_duration;
        
        // Set in object cache if available.
        if (wp_using_ext_object_cache()) {
            wp_cache_set($cache_key, $data, 'odds_comparison', $duration);
        }
        
        // Also set transient as backup.
        return set_transient($cache_key, $data, $duration);
    }
    
    /**
     * Delete cached data.
     *
     * @param string $key Cache key.
     * @return bool True on success, false on failure.
     */
    public function delete($key) {
        $cache_key = $this->get_cache_key($key);
        
        // Delete from object cache.
        if (wp_using_ext_object_cache()) {
            wp_cache_delete($cache_key, 'odds_comparison');
        }
        
        // Delete transient.
        return delete_transient($cache_key);
    }
    
    /**
     * Clear all odds comparison caches.
     *
     * @return void
     */
    public function clear_all() {
        global $wpdb;
        
        // Clear object cache.
        if (wp_using_ext_object_cache()) {
            wp_cache_flush();
        }
        
        // Clear transients from database.
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 OR option_name LIKE %s",
                $wpdb->esc_like('_transient_' . $this->cache_prefix) . '%',
                $wpdb->esc_like('_transient_timeout_' . $this->cache_prefix) . '%'
            )
        );
    }
    
    /**
     * Get formatted cache key with prefix.
     *
     * @param string $key Base key.
     * @return string Formatted cache key.
     */
    private function get_cache_key($key) {
        return $this->cache_prefix . md5($key);
    }
    
    /**
     * Check if cache exists and is valid.
     *
     * @param string $key Cache key.
     * @return bool True if cache exists and is valid.
     */
    public function has($key) {
        return false !== $this->get($key);
    }
    
    /**
     * Get or set cache with callback.
     *
     * @param string $key Cache key.
     * @param callable $callback Callback to generate data if cache miss.
     * @param int|null $duration Cache duration in seconds.
     * @return mixed Cached or freshly generated data.
     */
    public function remember($key, callable $callback, $duration = null) {
        $cached = $this->get($key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        $data = $callback();
        $this->set($key, $data, $duration);
        
        return $data;
    }
}


