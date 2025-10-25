<?php
/**
 * Simple Bookmaker List - API Driven
 *
 * @package OddsComparison\Admin
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Get bookmaker visibility settings
$bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'odds_comparison_bookmaker_visibility')) {
    // Get all bookmaker IDs from the form
    $all_bookmaker_ids = [];
    if (isset($_POST['all_bookmaker_ids']) && is_array($_POST['all_bookmaker_ids'])) {
        $all_bookmaker_ids = array_map('sanitize_text_field', $_POST['all_bookmaker_ids']);
    }
    
    // Initialize all bookmakers as hidden (false)
    $visibility_settings = [];
    foreach ($all_bookmaker_ids as $bookmaker_id) {
        $visibility_settings[$bookmaker_id] = false;
    }
    
    // Set checked bookmakers as visible (true)
    if (isset($_POST['bookmaker_visibility']) && is_array($_POST['bookmaker_visibility'])) {
        foreach ($_POST['bookmaker_visibility'] as $bookmaker_id => $value) {
            $visibility_settings[sanitize_text_field($bookmaker_id)] = (bool) $value;
        }
    }
    
    update_option('odds_comparison_bookmaker_visibility', $visibility_settings);
    $bookmaker_visibility = get_option('odds_comparison_bookmaker_visibility', []);
    
    // Debug information (remove in production)
    $debug_info = '<div class="notice notice-info"><p><strong>Debug Info:</strong><br>';
    $debug_info .= 'Total bookmakers: ' . count($all_bookmaker_ids) . '<br>';
    $debug_info .= 'Checked bookmakers: ' . count($_POST['bookmaker_visibility'] ?? []) . '<br>';
    $debug_info .= 'Settings saved: ' . (count($visibility_settings) > 0 ? 'Yes' : 'No') . '</p></div>';
    echo $debug_info;
    
    echo '<div class="notice notice-success"><p>' . esc_html__('Bookmaker settings saved successfully!', 'odds-comparison') . '</p></div>';
}

// Get API bookmakers using the BookmakerManager
$bookmaker_manager = new \OddsComparison\Admin\BookmakerManager();
$api_bookmakers = $bookmaker_manager->get_api_bookmakers();
?>

<div class="wrap">
    <h1><?php esc_html_e('Bookmaker List', 'odds-comparison'); ?></h1>
    <p class="description">
        <?php esc_html_e('All bookmakers from The Odds API. Click to hide/show them on your website.', 'odds-comparison'); ?>
    </p>
    
    <?php if (empty($api_bookmakers)): ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('No bookmakers available from API. Please check your API connection in Settings.', 'odds-comparison'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=odds-comparison-settings'); ?>" class="button button-primary">
                <?php esc_html_e('Go to Settings', 'odds-comparison'); ?>
            </a></p>
        </div>
    <?php else: ?>
        <form method="post" id="bookmaker-list-form">
            <?php wp_nonce_field('odds_comparison_bookmaker_visibility'); ?>
            
            <div class="bookmaker-list-container">
                <div class="bookmaker-controls">
                    <button type="button" class="button" id="show-all-bookmakers">
                        <?php esc_html_e('Show All', 'odds-comparison'); ?>
                    </button>
                    <button type="button" class="button" id="hide-all-bookmakers">
                        <?php esc_html_e('Hide All', 'odds-comparison'); ?>
                    </button>
                    <span class="bookmaker-count">
                        <?php printf(esc_html__('%d bookmakers from API', 'odds-comparison'), count($api_bookmakers)); ?>
                    </span>
                </div>
                
                <div class="bookmaker-list">
                    <?php foreach ($api_bookmakers as $bookmaker): 
                        $bookmaker_id = sanitize_title($bookmaker['title']);
                        $is_visible = isset($bookmaker_visibility[$bookmaker_id]) ? $bookmaker_visibility[$bookmaker_id] : true;
                    ?>
                    <!-- Hidden input to track all bookmaker IDs -->
                    <input type="hidden" name="all_bookmaker_ids[]" value="<?php echo esc_attr($bookmaker_id); ?>" />
                    
                    <div class="bookmaker-item">
                        <div class="bookmaker-toggle">
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       name="bookmaker_visibility[<?php echo esc_attr($bookmaker_id); ?>]" 
                                       value="1" 
                                       <?php checked($is_visible); ?>
                                       class="bookmaker-toggle-checkbox" />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        
                        <div class="bookmaker-details">
                            <div class="bookmaker-name">
                                <strong><?php echo esc_html($bookmaker['title']); ?></strong>
                            </div>
                            <div class="bookmaker-url">
                                <a href="<?php echo esc_url($bookmaker['url']); ?>" target="_blank" rel="nofollow">
                                    <?php echo esc_html($bookmaker['url']); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="bookmaker-status">
                            <span class="status-badge <?php echo $is_visible ? 'visible' : 'hidden'; ?>">
                                <?php echo $is_visible ? '✅ Visible' : '❌ Hidden'; ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="submit-section">
                    <input type="submit" name="submit" class="button button-primary button-large" value="<?php esc_attr_e('Save Changes', 'odds-comparison'); ?>" />
                </div>
            </div>
        </form>
    <?php endif; ?>
    </div>

<style>
.bookmaker-list-container {
    max-width: 1000px;
}

.bookmaker-controls {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e1e1e1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.bookmaker-controls .button {
    margin-right: 10px;
}

.bookmaker-count {
    color: #666;
    font-size: 14px;
}

.bookmaker-list {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.bookmaker-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #e1e1e1;
    transition: background-color 0.3s ease;
}

.bookmaker-item:last-child {
    border-bottom: none;
}

.bookmaker-item:hover {
    background: #f8f9fa;
}

.bookmaker-item.updating {
    background: #e3f2fd;
    opacity: 0.7;
}

.bookmaker-item.updating .toggle-switch {
    opacity: 0.5;
}


.bookmaker-toggle {
    margin-right: 20px;
}

.toggle-switch {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background-color: #0073aa;
}

input:checked + .toggle-slider:before {
    transform: translateX(26px);
}

.bookmaker-details {
    flex: 1;
}

.bookmaker-name {
    font-size: 16px;
    color: #23282d;
    margin-bottom: 5px;
}

.bookmaker-url {
    font-size: 14px;
}

.bookmaker-url a {
    color: #0073aa;
    text-decoration: none;
}

.bookmaker-url a:hover {
    text-decoration: underline;
}

.bookmaker-status {
    margin-left: 20px;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 600;
}

.status-badge.visible {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.status-badge.hidden {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.submit-section {
    margin-top: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e1e1e1;
    text-align: center;
}

.submit-section .button-large {
    padding: 12px 24px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .bookmaker-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .bookmaker-toggle {
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .bookmaker-status {
        margin-left: 0;
        align-self: flex-end;
    }
    
    .bookmaker-controls {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Show all bookmakers
    $('#show-all-bookmakers').on('click', function() {
        $('.bookmaker-toggle-checkbox').prop('checked', true);
        updateStatusBadges();
        saveAllBookmakerSettings();
    });
    
    // Hide all bookmakers
    $('#hide-all-bookmakers').on('click', function() {
        $('.bookmaker-toggle-checkbox').prop('checked', false);
        updateStatusBadges();
        saveAllBookmakerSettings();
    });
    
    // Individual toggle change - Simple form submission approach
    $('.bookmaker-toggle-checkbox').on('change', function() {
        var $checkbox = $(this);
        var $form = $checkbox.closest('form');
        
        // Submit form when toggle changes
        
        // Update status badge immediately for visual feedback
        updateStatusBadges();
        
        // Add loading state
        var $item = $checkbox.closest('.bookmaker-item');
        $item.addClass('updating');
        
        // Submit the form
        $form.submit();
    });
    
    
    function updateStatusBadges() {
        $('.bookmaker-toggle-checkbox').each(function() {
            var $item = $(this).closest('.bookmaker-item');
            var $badge = $item.find('.status-badge');
            
            if ($(this).is(':checked')) {
                $badge.removeClass('hidden').addClass('visible').text('✅ Visible');
            } else {
                $badge.removeClass('visible').addClass('hidden').text('❌ Hidden');
            }
        });
    }
    
    function saveAllBookmakerSettings() {
        // This function can be used to save all settings at once if needed
        // For now, individual toggles are saved via AJAX
    }
    
    // Initialize status badges
    updateStatusBadges();
    
    // Add loading states
    $('.bookmaker-toggle-checkbox').on('change', function() {
        var $item = $(this).closest('.bookmaker-item');
        $item.addClass('updating');
        
        setTimeout(function() {
            $item.removeClass('updating');
        }, 1000);
    });
});
</script>

