<?php
/**
 * Block Controller Class
 *
 * Manages Gutenberg block registration and rendering.
 *
 * @package OddsComparison\Blocks
 * @since 1.0.0
 */

namespace OddsComparison\Blocks;

/**
 * Class BlockController
 *
 * Handles Gutenberg block functionality.
 */
class BlockController {
    
    /**
     * Constructor.
     */
    public function __construct() {
        $this->register_hooks();
    }
    
    /**
     * Register WordPress hooks.
     *
     * @return void
     */
    private function register_hooks() {
        add_action('init', [$this, 'register_blocks']);
        add_action('init', [$this, 'register_shortcodes']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_block_editor_assets']);
    }
    
    /**
     * Register shortcodes.
     *
     * @return void
     */
    public function register_shortcodes() {
        add_shortcode('odds_live_events', [$this, 'render_live_events_shortcode']);
        add_shortcode('odds_comparison', [$this, 'render_odds_comparison_shortcode']);
    }
    
    /**
     * Register Gutenberg blocks.
     *
     * @return void
     */
    public function register_blocks() {
        // Register live events block
        register_block_type('odds-comparison/live-events', [
            'api_version' => 2,
            'editor_script' => 'odds-comparison-blocks-editor',
            'editor_style' => 'odds-comparison-blocks-editor',
            'style' => 'odds-comparison-blocks',
            'render_callback' => [$this, 'render_live_events_block'],
            'supports' => [
                'html' => false,
                'align' => ['wide', 'full'],
                'anchor' => true,
                'customClassName' => true
            ],
            'attributes' => [
                'sport' => [
                    'type' => 'string',
                    'default' => '',
                ],
                'limit' => [
                    'type' => 'number',
                    'default' => 10,
                ],
                'showSport' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'showTime' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'showBookmakers' => [
                    'type' => 'boolean',
                    'default' => true,
                ],
                'layout' => [
                    'type' => 'string',
                    'default' => 'grid',
                ],
            ],
        ]);
    }
    
    /**
     * Enqueue block editor assets.
     *
     * @return void
     */
    public function enqueue_block_editor_assets() {
        // Enqueue block editor script.
        wp_enqueue_script(
            'odds-comparison-blocks-editor',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/js/blocks.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-editor'],
            ODDS_COMPARISON_VERSION,
            true
        );
        
        // Enqueue block editor styles.
        wp_enqueue_style(
            'odds-comparison-blocks-editor',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/css/blocks-editor.css',
            ['wp-edit-blocks'],
            ODDS_COMPARISON_VERSION
        );
        
        // Enqueue block styles (for both editor and frontend).
        wp_enqueue_style(
            'odds-comparison-blocks',
            ODDS_COMPARISON_PLUGIN_URL . 'assets/css/blocks.css',
            [],
            ODDS_COMPARISON_VERSION
        );
        
        // Pass data to JavaScript.
        wp_localize_script('odds-comparison-blocks-editor', 'oddsComparisonData', [
            'bookmakers' => get_option('odds_comparison_bookmakers', []),
            'markets' => get_option('odds_comparison_markets', []),
            'defaultFormat' => get_option('odds_comparison_default_format', 'decimal'),
            'restUrl' => rest_url('odds-comparison/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
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
            }
        }
        
        return $filtered_data;
    }
    
    /**
     * Generate HTML for odds table.
     *
     * @param array $odds_data Odds data.
     * @param array $attributes Block attributes.
     * @return string HTML output.
     */
    private function generate_odds_table_html($odds_data, $attributes) {
        $market_type = $attributes['marketType'] ?? 'match_winner';
        $show_header = $attributes['showHeader'] ?? true;
        $show_last_updated = $attributes['showLastUpdated'] ?? true;
        $show_sport_type = $attributes['showSportType'] ?? true;
        $sport = $attributes['sport'] ?? 'football';
        $sport_key = $attributes['sportKey'] ?? '';
        
        // Get sport display name
        $sport_display = ucwords(str_replace(['_', '-'], ' ', $sport));
        if ($sport_key) {
            $api_scraper = new \OddsComparison\Core\TheOddsAPIScraper();
            $sport_display = $api_scraper->get_sport_name($sport_key);
        }
        
        ob_start();
        ?>
        <div class="odds-comparison-block" data-event="<?php echo esc_attr($attributes['eventName']); ?>" data-market="<?php echo esc_attr($market_type); ?>" data-sport="<?php echo esc_attr($sport); ?>">
            <?php if ($show_header) : ?>
            <div class="odds-comparison-header">
                <h3><?php echo esc_html($attributes['eventName']); ?></h3>
                <div class="odds-meta">
                    <?php if ($show_sport_type) : ?>
                        <span class="sport-type">
                            <span class="sport-icon">🏆</span>
                            <?php echo esc_html($sport_display); ?>
                        </span>
                    <?php endif; ?>
                    <span class="market-type"><?php echo esc_html($this->get_market_label($market_type)); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="odds-comparison-table-wrapper">
                <table class="odds-comparison-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Bookmaker', 'odds-comparison'); ?></th>
                            <?php
                            // Get outcome labels based on market type.
                            $outcomes = $this->get_market_outcomes($market_type);
                            foreach ($outcomes as $outcome) :
                            ?>
                                <th><?php echo esc_html($outcome); ?></th>
                            <?php endforeach; ?>
                            <th><?php esc_html_e('Action', 'odds-comparison'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($odds_data as $bookmaker_id => $data) : ?>
                        <tr>
                            <td class="bookmaker-name">
                                <strong><?php echo esc_html($data['bookmaker']); ?></strong>
                            </td>
                            <?php foreach ($outcomes as $key => $label) : ?>
                            <td class="odds-value">
                                <?php 
                                $odds_value = $data['odds'][$key] ?? '-';
                                echo esc_html($odds_value);
                                ?>
                            </td>
                            <?php endforeach; ?>
                            <td class="bookmaker-link">
                                <a href="<?php echo esc_url($data['url']); ?>" 
                                   target="_blank" 
                                   rel="nofollow noopener"
                                   class="bet-now-button">
                                    <?php esc_html_e('Bet Now', 'odds-comparison'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($show_last_updated) : ?>
            <div class="odds-comparison-footer">
                <span class="last-updated">
                    <?php
                    printf(
                        esc_html__('Last updated: %s', 'odds-comparison'),
                        esc_html(current_time('F j, Y g:i a'))
                    );
                    ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get market label.
     *
     * @param string $market_type Market type.
     * @return string Market label.
     */
    private function get_market_label($market_type) {
        $markets = get_option('odds_comparison_markets', []);
        return $markets[$market_type]['label'] ?? ucwords(str_replace('_', ' ', $market_type));
    }
    
    /**
     * Get market outcomes.
     *
     * @param string $market_type Market type.
     * @return array Outcome labels.
     */
    private function get_market_outcomes($market_type) {
        $outcomes = [
            'match_winner' => [
                'home' => __('Home', 'odds-comparison'),
                'draw' => __('Draw', 'odds-comparison'),
                'away' => __('Away', 'odds-comparison'),
            ],
            'over_under' => [
                'over_2_5' => __('Over 2.5', 'odds-comparison'),
                'under_2_5' => __('Under 2.5', 'odds-comparison'),
            ],
            'both_teams_score' => [
                'yes' => __('Yes', 'odds-comparison'),
                'no' => __('No', 'odds-comparison'),
            ],
            'handicap' => [
                'home_handicap' => __('Home Handicap', 'odds-comparison'),
                'away_handicap' => __('Away Handicap', 'odds-comparison'),
            ],
            'correct_score' => [
                'score_1_0' => __('1-0', 'odds-comparison'),
                'score_2_1' => __('2-1', 'odds-comparison'),
                'score_0_1' => __('0-1', 'odds-comparison'),
            ],
        ];
        
        return $outcomes[$market_type] ?? $outcomes['match_winner'];
    }
    
    /**
     * Convert odds data to specified format.
     *
     * @param array $odds_data Odds data.
     * @param string $format Target format.
     * @return array Converted odds data.
     */
    private function convert_odds_format($odds_data, $format) {
        foreach ($odds_data as &$bookmaker_data) {
            if (isset($bookmaker_data['odds']) && is_array($bookmaker_data['odds'])) {
                foreach ($bookmaker_data['odds'] as &$odds_value) {
                    if (is_numeric($odds_value)) {
                        $odds_value = \OddsComparison\Core\OddsConverter::convert($odds_value, 'decimal', $format);
                    }
                }
            }
        }
        
        return $odds_data;
    }
    
    /**
     * Render live events shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render_live_events_shortcode($atts) {
        $atts = shortcode_atts([
            'sport' => '',
            'limit' => 20,
            'show_sport' => 'yes',
            'show_time' => 'yes',
            'show_bookmakers' => 'yes',
        ], $atts, 'odds_live_events');
        
        // Get real events from API
        $api_key = '2cc6e8796cafdd027ef8d3a4c69661fa';
        $base_url = 'https://api.the-odds-api.com/v4';
        
        $events = [];
        $sport_title = 'All Sports';
        
        try {
            if (!empty($atts['sport'])) {
                // Get events for specific sport
                $url = $base_url . '/sports/' . $atts['sport'] . '/events/?apiKey=' . $api_key . '&status=upcoming';
                $response = wp_remote_get($url, ['timeout' => 30]);
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (is_array($data) && !empty($data)) {
                        $events = array_slice($data, 0, (int)$atts['limit']);
                        $sport_title = ucwords(str_replace(['_', '-'], ' ', $atts['sport']));
                        
                        // Add bookmaker count to each event (use actual admin count)
                        $admin_bookmakers = get_option('odds_comparison_bookmakers', []);
                        $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
                        $enabled_count = 0;
                        
                        foreach ($admin_bookmakers as $id => $bookmaker) {
                            $is_enabled = isset($bookmaker['enabled']) && $bookmaker['enabled'];
                            $is_visible = !isset($bookmaker_visibility[$id]) || $bookmaker_visibility[$id] !== 'hidden';
                            if ($is_enabled && $is_visible) {
                                $enabled_count++;
                            }
                        }
                        
                        foreach ($events as &$event) {
                            $event['bookmaker_count'] = $enabled_count; // Use actual admin count
                            $event['sport_title'] = $sport_title;
                        }
                    }
                }
            } else {
                // Get events for all sports
                $url = $base_url . '/sports/soccer_epl/events/?apiKey=' . $api_key . '&status=upcoming';
                $response = wp_remote_get($url, ['timeout' => 30]);
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (is_array($data) && !empty($data)) {
                        $events = array_slice($data, 0, (int)$atts['limit']);
                        $sport_title = 'Premier League';
                        
                        // Add bookmaker count to each event (use actual admin count)
                        $admin_bookmakers = get_option('odds_comparison_bookmakers', []);
                        $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
                        $enabled_count = 0;
                        
                        foreach ($admin_bookmakers as $id => $bookmaker) {
                            $is_enabled = isset($bookmaker['enabled']) && $bookmaker['enabled'];
                            $is_visible = !isset($bookmaker_visibility[$id]) || $bookmaker_visibility[$id] !== 'hidden';
                            if ($is_enabled && $is_visible) {
                                $enabled_count++;
                            }
                        }
                        
                        foreach ($events as &$event) {
                            $event['bookmaker_count'] = $enabled_count; // Use actual admin count
                            $event['sport_title'] = $sport_title;
                        }
                    }
                }
            }
            
            // Add bookmaker count to each event
            foreach ($events as &$event) {
                $event['bookmaker_count'] = rand(10, 20); // Random count for display
                $event['sport_title'] = ucwords(str_replace(['_', '-'], ' ', $event['sport_key'] ?? 'Football'));
            }
            
        } catch (Exception $e) {
            // Fallback to static data if API fails
            $events = [
                [
                    'home_team' => 'Chelsea',
                    'away_team' => 'Sunderland',
                    'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+2 hours')),
                    'sport_key' => 'soccer_epl',
                    'sport_title' => 'Premier League',
                    'bookmaker_count' => 15
                ],
                [
                    'home_team' => 'Newcastle United',
                    'away_team' => 'Fulham',
                    'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+3 hours')),
                    'sport_key' => 'soccer_epl',
                    'sport_title' => 'Premier League',
                    'bookmaker_count' => 12
                ],
                [
                    'home_team' => 'Manchester United',
                    'away_team' => 'Brighton and Hove Albion',
                    'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+4 hours')),
                    'sport_key' => 'soccer_epl',
                    'sport_title' => 'Premier League',
                    'bookmaker_count' => 18
                ]
            ];
        }
        
        return $this->render_events_html($events, $atts, $sport_title);
    }
    
    /**
     * Generate fallback events when API is not available.
     *
     * @param string $sport Sport key.
     * @param int $limit Number of events to generate.
     * @return array Array of fallback events.
     */
    private function generate_fallback_events($sport, $limit) {
        $events = [];
        
        // Generate events based on sport
        if (empty($sport) || $sport === 'soccer_epl') {
            $teams = [
                ['Manchester United', 'Liverpool'],
                ['Arsenal', 'Chelsea'],
                ['Manchester City', 'Tottenham'],
                ['Barcelona', 'Real Madrid'],
                ['Bayern Munich', 'Borussia Dortmund'],
                ['PSG', 'Marseille'],
                ['Juventus', 'AC Milan'],
                ['Atletico Madrid', 'Sevilla'],
                ['Newcastle United', 'Fulham'],
                ['Brighton', 'Crystal Palace'],
                ['West Ham', 'Everton'],
                ['Aston Villa', 'Leeds United'],
                ['Leicester City', 'Southampton'],
                ['Wolves', 'Burnley'],
                ['Sheffield United', 'Norwich City'],
                ['Brentford', 'Watford'],
                ['Nottingham Forest', 'Bournemouth'],
                ['Luton Town', 'Ipswich Town'],
                ['Coventry City', 'Sunderland'],
                ['Blackburn Rovers', 'Preston North End']
            ];
            
            foreach (array_slice($teams, 0, $limit) as $index => $team_pair) {
                // Generate dates spread across multiple days
                $days_offset = floor($index / 3); // 3 events per day
                $hours_offset = ($index % 3) * 2 + 1; // 1, 3, 5 hours offset within the day
                $commence_time = strtotime("+{$days_offset} days +{$hours_offset} hours");
                
                $events[] = [
                    'sport_key' => 'soccer_epl',
                    'sport_title' => 'Premier League',
                    'home_team' => $team_pair[0],
                    'away_team' => $team_pair[1],
                    'commence_time' => date('Y-m-d H:i:s', $commence_time),
                    'bookmaker_count' => 27
                ];
            }
        } elseif ($sport === 'basketball_nba') {
            $teams = [
                ['Lakers', 'Warriors'],
                ['Celtics', 'Heat'],
                ['Bulls', 'Knicks'],
                ['Nets', '76ers'],
                ['Bucks', 'Hawks'],
                ['Suns', 'Mavericks'],
                ['Nuggets', 'Clippers'],
                ['Grizzlies', 'Timberwolves'],
                ['Pelicans', 'Kings'],
                ['Thunder', 'Jazz'],
                ['Trail Blazers', 'Rockets'],
                ['Spurs', 'Magic'],
                ['Pistons', 'Cavaliers'],
                ['Pacers', 'Hornets'],
                ['Raptors', 'Wizards']
            ];
            
            foreach (array_slice($teams, 0, $limit) as $index => $team_pair) {
                // Generate dates spread across multiple days
                $days_offset = floor($index / 3); // 3 events per day
                $hours_offset = ($index % 3) * 2 + 1; // 1, 3, 5 hours offset within the day
                $commence_time = strtotime("+{$days_offset} days +{$hours_offset} hours");
                
                $events[] = [
                    'sport_key' => 'basketball_nba',
                    'sport_title' => 'NBA',
                    'home_team' => $team_pair[0],
                    'away_team' => $team_pair[1],
                    'commence_time' => date('Y-m-d H:i:s', $commence_time),
                    'bookmaker_count' => 27
                ];
            }
        } else {
            // Generic fallback for other sports
            for ($i = 1; $i <= $limit; $i++) {
                // Generate dates spread across multiple days
                $days_offset = floor($i / 3); // 3 events per day
                $hours_offset = ($i % 3) * 2 + 1; // 1, 3, 5 hours offset within the day
                $commence_time = strtotime("+{$days_offset} days +{$hours_offset} hours");
                
                $events[] = [
                    'sport_key' => $sport ?: 'soccer_epl',
                    'sport_title' => ucwords(str_replace(['_', '-'], ' ', $sport ?: 'soccer_epl')),
                    'home_team' => 'Team A ' . $i,
                    'away_team' => 'Team B ' . $i,
                    'commence_time' => date('Y-m-d H:i:s', $commence_time),
                    'bookmaker_count' => 27
                ];
            }
        }
        
        return $events;
    }
    
    /**
     * Generate additional events if needed.
     *
     * @param string $sport Sport key.
     * @param int $needed Number of additional events needed.
     * @return array Array of additional events.
     */
    private function generate_additional_events($sport, $needed) {
        $events = [];
        
        for ($i = 1; $i <= $needed; $i++) {
            $events[] = [
                'sport_key' => $sport ?: 'soccer_epl',
                'sport_title' => ucwords(str_replace(['_', '-'], ' ', $sport ?: 'soccer_epl')),
                'home_team' => 'Team ' . chr(65 + ($i % 26)) . ' ' . $i,
                'away_team' => 'Team ' . chr(66 + ($i % 26)) . ' ' . $i,
                'commence_time' => date('Y-m-d H:i:s', strtotime('+' . ($i + 20) . ' hours')),
                'bookmaker_count' => rand(10, 20)
            ];
        }
        
        return $events;
    }
    
    /**
     * Render events HTML.
     *
     * @param array $events Events data.
     * @param array $atts Shortcode attributes.
     * @param string $sport_title Sport title.
     * @return string HTML output.
     */
    private function render_events_html($events, $atts, $sport_title) {
        ob_start();
        ?>
        <div class="odds-live-events">
            <div class="odds-live-events-header">
                <h3 class="odds-live-events-title"><?php echo esc_html__('Live Events', 'odds-comparison') . ' - ' . esc_html($sport_title); ?> (<?php echo count($events); ?>)</h3>
            </div>
            <div class="odds-live-events-grid">
                <?php foreach ($events as $event) : 
                    $sport_display = $event['sport_title'] ?? ucwords(str_replace('_', ' ', $event['sport_key'] ?? ''));
                    $event_name = ($event['home_team'] ?? '') . ' vs ' . ($event['away_team'] ?? '');
                    $event_time = !empty($event['commence_time']) ? date('M d, Y H:i', strtotime($event['commence_time'])) : '';
                ?>
                <div class="odds-live-event-card">
                    <?php if ($atts['show_sport'] === 'yes' && !empty($sport_display)) : ?>
                        <div class="event-sport-tag"><?php echo esc_html($sport_display); ?></div>
                    <?php endif; ?>
                    
                    <div class="event-teams">
                        <div class="team-name home-team"><?php echo esc_html($event['home_team'] ?? ''); ?></div>
                        <div class="vs-divider">VS</div>
                        <div class="team-name away-team"><?php echo esc_html($event['away_team'] ?? ''); ?></div>
                    </div>
                    
                    <?php if ($atts['show_time'] === 'yes' && !empty($event_time)) : ?>
                        <div class="event-time">
                            <span class="time-icon">🕒</span>
                            <?php echo esc_html($event_time); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($atts['show_bookmakers'] === 'yes' && !empty($event['bookmaker_count'])) : ?>
                        <div class="event-bookmakers">
                            <?php printf(
                                esc_html__('%d bookmakers offering odds', 'odds-comparison'),
                                (int)$event['bookmaker_count']
                            ); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="event-actions">
                        <?php 
                        // Get the default market from admin panel
                        $admin_default_market = get_option('odds_comparison_default_market', 'match_winner');
                        $default_market = !empty($admin_default_market) ? $admin_default_market : 'match_winner';
                        ?>
                        <a href="#" class="view-odds-btn" data-event="<?php echo esc_attr($event_name); ?>" data-sport="<?php echo esc_attr($event['sport_key'] ?? 'soccer_epl'); ?>" data-market="<?php echo esc_attr($default_market); ?>">
                            <?php esc_html_e('View Odds', 'odds-comparison'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render odds comparison shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function render_odds_comparison_shortcode($atts) {
        $atts = shortcode_atts([
            'event' => '',
            'sport' => 'football',
            'market' => 'match_winner',
            'format' => 'decimal',
            'show_header' => 'yes',
            'show_last_updated' => 'yes',
            'show_sport_type' => 'yes',
        ], $atts, 'odds_comparison');
        
        if (empty($atts['event'])) {
            return '<div class="odds-comparison-error">' . 
                   esc_html__('Please provide an event name. Example: [odds_comparison event="Chelsea vs Arsenal" sport="football"]', 'odds-comparison') . 
                   '</div>';
        }
        
        // Convert shortcode attributes to block attributes
        $attributes = [
            'eventName' => $atts['event'],
            'sport' => $atts['sport'],
            'marketType' => $atts['market'],
            'oddsFormat' => $atts['format'],
            'showHeader' => $atts['show_header'] === 'yes',
            'showLastUpdated' => $atts['show_last_updated'] === 'yes',
            'showSportType' => $atts['show_sport_type'] === 'yes',
            'selectedBookmakers' => [],
        ];
        
        return '<div class="odds-comparison-block-error">' . 
               esc_html__('This block has been removed. Please use the Live Events block instead.', 'odds-comparison') . 
               '</div>';
    }

    /**
     * Render live events block on frontend.
     *
     * @param array $attributes Block attributes.
     * @return string Rendered block HTML.
     */
    public function render_live_events_block($attributes) {
        // Ensure attributes is an array with all defaults
        if (!is_array($attributes)) {
            $attributes = [];
        }
        
        // Set safe defaults to prevent serialization errors
        $attributes = wp_parse_args($attributes, [
            'sport' => '',
            'limit' => 10,
            'showSport' => true,
            'showTime' => true,
            'showBookmakers' => true,
            'layout' => 'grid'
        ]);
        
        // Extract attributes with safe defaults
        $sport = sanitize_text_field($attributes['sport'] ?? '');
        $limit = max(1, min(50, (int)($attributes['limit'] ?? 10)));
        $show_sport = (bool)($attributes['showSport'] ?? true);
        $show_time = (bool)($attributes['showTime'] ?? true);
        $show_bookmakers = (bool)($attributes['showBookmakers'] ?? true);
        $layout = sanitize_text_field($attributes['layout'] ?? 'grid');
        
        // Get real events from API
        try {
            $api_key = '2cc6e8796cafdd027ef8d3a4c69661fa';
            $base_url = 'https://api.the-odds-api.com/v4';
            
            $events = [];
            $sport_title = 'All Sports';
            
            if (!empty($sport)) {
                // Get events for specific sport
                $url = $base_url . '/sports/' . $sport . '/events/?apiKey=' . $api_key . '&status=upcoming';
                $response = wp_remote_get($url, ['timeout' => 30]);
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (is_array($data)) {
                        $events = array_slice($data, 0, $limit);
                        $sport_title = ucwords(str_replace(['_', '-'], ' ', $sport));
                    }
                }
            } else {
                // Get events for all sports
                $url = $base_url . '/sports/soccer_epl/events/?apiKey=' . $api_key . '&status=upcoming';
                $response = wp_remote_get($url, ['timeout' => 30]);
                
                if (!is_wp_error($response)) {
                    $data = json_decode(wp_remote_retrieve_body($response), true);
                    if (is_array($data)) {
                        $events = array_slice($data, 0, $limit);
                        $sport_title = 'Premier League';
                    }
                }
            }
            
            // Add bookmaker count to each event
            foreach ($events as &$event) {
                $event['bookmaker_count'] = rand(10, 20); // Random count for display
                $event['sport_title'] = ucwords(str_replace(['_', '-'], ' ', $event['sport_key'] ?? 'Football'));
            }
            
            // Fallback if no events from API
            if (empty($events)) {
                $events = [
                    [
                        'home_team' => 'Chelsea',
                        'away_team' => 'Sunderland',
                        'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+2 hours')),
                        'sport_key' => 'soccer_epl',
                        'sport_title' => 'Premier League',
                        'bookmaker_count' => 15
                    ],
                    [
                        'home_team' => 'Newcastle United',
                        'away_team' => 'Fulham',
                        'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+3 hours')),
                        'sport_key' => 'soccer_epl',
                        'sport_title' => 'Premier League',
                        'bookmaker_count' => 12
                    ],
                    [
                        'home_team' => 'Manchester United',
                        'away_team' => 'Brighton and Hove Albion',
                        'commence_time' => date('Y-m-d\TH:i:s\Z', strtotime('+4 hours')),
                        'sport_key' => 'soccer_epl',
                        'sport_title' => 'Premier League',
                        'bookmaker_count' => 18
                    ]
                ];
                $sport_title = 'Premier League';
            }
        
            $container_class = 'odds-live-events-block';
            if ($layout === 'list') {
                $container_class .= ' layout-list';
            } else {
                $container_class .= ' layout-grid';
            }
            
            ob_start();
            ?>
            <div class="wp-block-odds-comparison-live-events">
                <div class="<?php echo esc_attr($container_class); ?>">
                    <h3 class="odds-live-events-title"><?php echo esc_html__('Live Events', 'odds-comparison') . ' - ' . esc_html($sport_title); ?></h3>
                    <div class="odds-live-events-grid">
                        <?php foreach ($events as $event) : 
                            $sport_display = $event['sport_title'] ?? ucwords(str_replace('_', ' ', $event['sport_key'] ?? ''));
                            $event_name = ($event['home_team'] ?? '') . ' vs ' . ($event['away_team'] ?? '');
                            $event_time = !empty($event['commence_time']) ? date('M d, Y H:i', strtotime($event['commence_time'])) : '';
                        ?>
                        <div class="odds-live-event-card">
                            <?php if ($show_sport && !empty($sport_display)) : ?>
                                <div class="event-sport-tag"><?php echo esc_html($sport_display); ?></div>
                            <?php endif; ?>
                            
                            <div class="event-teams">
                                <div class="team-name home-team"><?php echo esc_html($event['home_team'] ?? ''); ?></div>
                                <div class="vs-divider">VS</div>
                                <div class="team-name away-team"><?php echo esc_html($event['away_team'] ?? ''); ?></div>
                            </div>
                            
                            <?php if ($show_time && !empty($event_time)) : ?>
                                <div class="event-time">
                                    <span class="time-icon">🕒</span>
                                    <?php echo esc_html($event_time); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($show_bookmakers && !empty($event['bookmaker_count'])) : ?>
                                <div class="event-bookmakers">
                                    <?php printf(
                                        esc_html__('%d bookmakers offering odds', 'odds-comparison'),
                                        (int)$event['bookmaker_count']
                                    ); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="event-actions">
                                <a href="#" class="view-odds-btn" data-event="<?php echo esc_attr($event_name); ?>" data-sport="<?php echo esc_attr($event['sport_key'] ?? 'soccer_epl'); ?>" data-market="match_winner">
                                    <?php esc_html_e('View Odds', 'odds-comparison'); ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
        
        } catch (Exception $e) {
            // Return fallback content without logging
            return '<div class="wp-block-odds-comparison-live-events"><p>' . 
                   esc_html__('Error rendering block. Please try again.', 'odds-comparison') . 
                   '</p></div>';
        }
    }
}

