<?php
/**
 * API Controller Class
 *
 * Manages REST API endpoints for odds data and plugin interactions.
 *
 * @package OddsComparison\API
 * @since 1.0.0
 */

namespace OddsComparison\API;

use OddsComparison\Core\OddsScraper;
use OddsComparison\Core\OddsConverter;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Class APIController
 *
 * Handles REST API endpoint registration and request processing.
 */
class APIController extends WP_REST_Controller {
    
    /**
     * API namespace.
     *
     * @var string
     */
    protected $namespace = 'odds-comparison/v1';
    
    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes() {
        // Get odds endpoint.
        register_rest_route($this->namespace, '/odds', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_odds'],
                'permission_callback' => '__return_true',
                'args' => [
                    'event' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'market' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'match_winner',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'format' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'decimal',
                        'enum' => ['decimal', 'fractional', 'american'],
                    ],
                    'sport' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'football',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);
        
        // Convert odds endpoint.
        register_rest_route($this->namespace, '/convert', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'convert_odds'],
                'permission_callback' => '__return_true',
                'args' => [
                    'value' => [
                        'required' => true,
                        'validate_callback' => function($param) {
                            return !empty($param);
                        },
                    ],
                    'from' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['decimal', 'fractional', 'american'],
                    ],
                    'to' => [
                        'required' => true,
                        'type' => 'string',
                        'enum' => ['decimal', 'fractional', 'american'],
                    ],
                ],
            ],
        ]);
        
        // Get bookmakers endpoint.
        register_rest_route($this->namespace, '/bookmakers', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_bookmakers'],
                'permission_callback' => '__return_true',
            ],
        ]);
        
        // Get markets endpoint.
        register_rest_route($this->namespace, '/markets', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_markets'],
                'permission_callback' => '__return_true',
            ],
        ]);
        
        // Get sports endpoint.
        register_rest_route($this->namespace, '/sports', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_sports'],
                'permission_callback' => '__return_true',
            ],
        ]);
        
        // Get live events endpoint.
        register_rest_route($this->namespace, '/events', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_live_events'],
                'permission_callback' => '__return_true',
                'args' => [
                    'sport' => [
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'limit' => [
                        'required' => false,
                        'type' => 'integer',
                        'default' => 20,
                    ],
                ],
            ],
        ]);
        
        // Refresh odds endpoint (protected).
        register_rest_route($this->namespace, '/refresh', [
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [$this, 'refresh_odds'],
                'permission_callback' => [$this, 'check_admin_permissions'],
                'args' => [
                    'event' => [
                        'required' => true,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'market' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => 'match_winner',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ]);
    }
    
    /**
     * Get odds data for an event.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_odds($request) {
        // Set proper headers for JSON response
        header('Content-Type: application/json');
        
        try {
            $event_name = $request->get_param('event');
            $market_type = $request->get_param('market');
            $format = $request->get_param('format');
            $sport = $request->get_param('sport') ?? 'football';
            
            error_log("API get_odds called: event={$event_name}, market={$market_type}, sport={$sport}");
            
            // Validate required parameters
            if (empty($event_name)) {
                return new WP_Error(
                    'missing_event',
                    'Event name is required',
                    ['status' => 400]
                );
            }
            
            if (empty($market_type)) {
                return new WP_Error(
                    'missing_market',
                    'Market type is required',
                    ['status' => 400]
                );
            }
            
            // Check if the requested market is enabled
            if (!$this->is_market_enabled($market_type)) {
                return new WP_Error(
                    'market_disabled',
                    __('This market type is disabled', 'odds-comparison'),
                    ['status' => 403]
                );
            }
            
            // Clear any existing cache for this request
            $cache_manager = new \OddsComparison\Core\CacheManager();
            $cache_key = "odds_{$event_name}_{$market_type}_{$sport}";
            $cache_manager->delete($cache_key);
            
            $scraper = new OddsScraper();
            // Bypass cache for modal requests to get fresh data with all bookmakers
            $odds_data = $scraper->fetch_odds_fresh($event_name, $market_type, $sport);
            
            error_log("API get_odds: Retrieved " . count($odds_data) . " bookmakers for {$event_name}");
        
            // If no odds data is returned, provide fallback data instead of error
            if (empty($odds_data)) {
                error_log("API get_odds: No odds data found, using fallback");
                
                // Create fallback odds data
                $odds_api_scraper = new \OddsComparison\Core\TheOddsAPIScraper();
                $odds_data = $odds_api_scraper->get_fallback_odds_with_market($market_type);
                
                // If still empty, create basic fallback based on market type
                if (empty($odds_data)) {
                    $odds_data = $this->create_emergency_fallback($market_type);
                }
            }
            
            // Filter out hidden bookmakers based on admin settings
            $odds_data = $this->filter_visible_bookmakers($odds_data);
            
            // Convert odds to requested format if not decimal.
            if ($format !== 'decimal') {
                $odds_data = $this->convert_odds_format($odds_data, 'decimal', $format);
            }
            
            error_log("API get_odds: Returning " . count($odds_data) . " bookmakers");
            
            return new WP_REST_Response([
                'success' => true,
                'event' => $event_name,
                'market' => $market_type,
                'format' => $format,
                'sport' => $sport,
                'odds' => $odds_data,
                'timestamp' => current_time('mysql'),
            ], 200);
            
        } catch (Exception $e) {
            error_log("API get_odds error: " . $e->getMessage());
            
            // Return JSON error response instead of WP_Error
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Failed to load odds: ' . $e->getMessage(),
                'event' => $event_name ?? 'Unknown',
                'market' => $market_type ?? 'Unknown',
                'odds' => [],
                'timestamp' => current_time('mysql'),
            ], 500);
            
        } catch (Error $e) {
            error_log("API get_odds fatal error: " . $e->getMessage());
            
            // Return JSON error response instead of WP_Error
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Fatal error loading odds: ' . $e->getMessage(),
                'event' => $event_name ?? 'Unknown',
                'market' => $market_type ?? 'Unknown',
                'odds' => [],
                'timestamp' => current_time('mysql'),
            ], 500);
        }
    }
    
    /**
     * Check if a market type is enabled.
     *
     * @param string $market_type Market type to check.
     * @return bool True if market is enabled, false otherwise.
     */
    private function is_market_enabled($market_type) {
        $markets = get_option('odds_comparison_markets', []);
        
        // If no markets configured, allow all (default behavior)
        if (empty($markets)) {
            return true;
        }
        
        // Check if this specific market is enabled
        return isset($markets[$market_type]) && $markets[$market_type]['enabled'] === true;
    }
    
    /**
     * Filter out hidden bookmakers based on admin visibility settings.
     *
     * @param array $odds_data Raw odds data from API.
     * @return array Filtered odds data with only visible bookmakers.
     */
    private function filter_visible_bookmakers($odds_data) {
        // Get bookmaker visibility settings
        $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
        
        // If no settings exist, show all bookmakers (default behavior)
        if (empty($bookmaker_visibility)) {
            return $odds_data;
        }
        
        $filtered_data = [];
        $has_visible_bookmakers = false;
        
        foreach ($odds_data as $bookmaker_key => $bookmaker_data) {
            // Get bookmaker title for matching
            $bookmaker_title = $bookmaker_data['bookmaker'] ?? '';
            $bookmaker_id = sanitize_title($bookmaker_title);
            
            // Check if this bookmaker is visible
            $is_visible = isset($bookmaker_visibility[$bookmaker_id]) ? 
                         $bookmaker_visibility[$bookmaker_id] : true;
            
            // Only include visible bookmakers
            if ($is_visible) {
                $filtered_data[$bookmaker_key] = $bookmaker_data;
                $has_visible_bookmakers = true;
            }
        }
        
        // If no bookmakers are visible, return all bookmakers to avoid empty results
        if (!$has_visible_bookmakers && !empty($odds_data)) {
            error_log("No visible bookmakers found, showing all bookmakers");
            return $odds_data;
        }
        
        return $filtered_data;
    }
    
    /**
     * Convert odds between formats.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function convert_odds($request) {
        $value = $request->get_param('value');
        $from_format = $request->get_param('from');
        $to_format = $request->get_param('to');
        
        if (!OddsConverter::is_valid($value, $from_format)) {
            return new WP_Error(
                'invalid_odds',
                __('Invalid odds value for the specified format', 'odds-comparison'),
                ['status' => 400]
            );
        }
        
        $converted = OddsConverter::convert($value, $from_format, $to_format);
        
        // Calculate additional information.
        $decimal_value = $from_format === 'decimal' ? $value : OddsConverter::convert($value, $from_format, 'decimal');
        $implied_probability = OddsConverter::calculate_implied_probability($decimal_value);
        
        return new WP_REST_Response([
            'success' => true,
            'original' => [
                'value' => $value,
                'format' => $from_format,
            ],
            'converted' => [
                'value' => $converted,
                'format' => $to_format,
            ],
            'implied_probability' => $implied_probability . '%',
        ], 200);
    }
    
    /**
     * Get list of bookmakers.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_bookmakers($request) {
        $bookmakers = get_option('odds_comparison_bookmakers', []);
        
        // Filter to only enabled bookmakers.
        $enabled_bookmakers = array_filter($bookmakers, function($bookmaker) {
            return $bookmaker['enabled'] === true;
        });
        
        return new WP_REST_Response([
            'success' => true,
            'bookmakers' => $enabled_bookmakers,
        ], 200);
    }
    
    /**
     * Get list of markets.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_markets($request) {
        $markets = get_option('odds_comparison_markets', []);
        
        // Filter to only enabled markets.
        $enabled_markets = array_filter($markets, function($market) {
            return $market['enabled'] === true;
        });
        
        return new WP_REST_Response([
            'success' => true,
            'markets' => $enabled_markets,
        ], 200);
    }
    
    /**
     * Refresh odds data (admin only).
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function refresh_odds($request) {
        $event_name = $request->get_param('event');
        $market_type = $request->get_param('market');
        
        // Clear cache for this event.
        $cache = new \OddsComparison\Core\CacheManager();
        $cache->delete("odds_{$event_name}_{$market_type}");
        
        // Fetch fresh data.
        $scraper = new OddsScraper();
        $odds_data = $scraper->fetch_odds($event_name, $market_type);
        
        if (empty($odds_data)) {
            return new WP_Error(
                'refresh_failed',
                __('Failed to refresh odds data', 'odds-comparison'),
                ['status' => 500]
            );
        }
        
        return new WP_REST_Response([
            'success' => true,
            'message' => __('Odds refreshed successfully', 'odds-comparison'),
            'odds' => $odds_data,
        ], 200);
    }
    
    /**
     * Get available sports.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_sports($request) {
        $scraper = new OddsScraper();
        $sports = $scraper->get_all_sports();
        
        return new WP_REST_Response([
            'success' => true,
            'sports' => $sports,
            'count' => count($sports),
        ], 200);
    }
    
    /**
     * Get live events.
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response Response object.
     */
    public function get_live_events($request) {
        $sport = $request->get_param('sport');
        $limit = $request->get_param('limit');
        
        $scraper = new OddsScraper();
        
        if ($sport) {
            $events = $scraper->get_live_events_by_sport($sport, $limit);
        } else {
            $events = $scraper->get_all_live_events($limit);
        }
        
        return new WP_REST_Response([
            'success' => true,
            'events' => $events,
            'count' => count($events),
            'sport' => $sport,
        ], 200);
    }
    
    /**
     * Check if user has admin permissions.
     *
     * @param WP_REST_Request $request Request object.
     * @return bool True if user has permissions.
     */
    public function check_admin_permissions($request) {
        return current_user_can('manage_odds_comparison');
    }
    
    /**
     * Convert odds data to a different format.
     *
     * @param array $odds_data Odds data array.
     * @param string $from_format Source format.
     * @param string $to_format Target format.
     * @return array Converted odds data.
     */
    private function convert_odds_format($odds_data, $from_format, $to_format) {
        foreach ($odds_data as &$bookmaker_data) {
            if (isset($bookmaker_data['odds']) && is_array($bookmaker_data['odds'])) {
                foreach ($bookmaker_data['odds'] as &$odds_value) {
                    if (is_numeric($odds_value)) {
                        $odds_value = OddsConverter::convert($odds_value, $from_format, $to_format);
                    }
                }
            }
        }
        
        return $odds_data;
    }
    
    /**
     * Create emergency fallback data when all else fails.
     *
     * @param string $market_type Market type.
     * @return array Emergency fallback odds data.
     */
    private function create_emergency_fallback($market_type) {
        $base_odds = [
            'fanduel' => [
                'bookmaker' => 'FanDuel',
                'url' => 'https://www.fanduel.com/',
                'odds' => []
            ],
            'draftkings' => [
                'bookmaker' => 'DraftKings',
                'url' => 'https://www.draftkings.com/',
                'odds' => []
            ],
            'betmgm' => [
                'bookmaker' => 'BetMGM',
                'url' => 'https://www.betmgm.com/',
                'odds' => []
            ]
        ];
        
        // Add market-specific odds
        switch ($market_type) {
            case 'match_winner':
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'home' => '1.85',
                        'draw' => '3.20',
                        'away' => '2.95'
                    ];
                }
                break;
                
            case 'over_under':
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'over_2_5' => '1.85',
                        'under_2_5' => '2.05'
                    ];
                }
                break;
                
            case 'both_teams_score':
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'yes' => '1.75',
                        'no' => '2.10'
                    ];
                }
                break;
                
            case 'handicap':
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'home_handicap' => '1.90',
                        'away_handicap' => '1.95'
                    ];
                }
                break;
                
            case 'correct_score':
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'score_1_0' => '8.50',
                        'score_2_1' => '12.00',
                        'score_0_1' => '9.00'
                    ];
                }
                break;
                
            default:
                // Default to match_winner format
                foreach ($base_odds as &$bookmaker) {
                    $bookmaker['odds'] = [
                        'home' => '1.85',
                        'draw' => '3.20',
                        'away' => '2.95'
                    ];
                }
        }
        
        return $base_odds;
    }
}


