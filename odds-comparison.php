<?php
/**
 * Plugin Name: Advanced Odds Comparison
 * Plugin URI: https://github.com/garikmuradyan/odds-comparison
 * Description: A professional odds comparison plugin that fetches live odds from multiple bookmakers, provides Gutenberg blocks, and offers comprehensive odds conversion tools.
 * Version: 1.0.0
 * Author: Garik Muradyan
 * Author URI: mailto:muradyangarik@gmail.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: odds-comparison
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package OddsComparison
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants.
define('ODDS_COMPARISON_VERSION', '1.0.0');
define('ODDS_COMPARISON_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ODDS_COMPARISON_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ODDS_COMPARISON_PLUGIN_FILE', __FILE__);
define('ODDS_COMPARISON_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Autoloader for plugin classes.
 *
 * @param string $class_name The fully-qualified class name.
 * @return void
 */
spl_autoload_register(function ($class_name) {
    // Check if the class belongs to our namespace.
    if (strpos($class_name, 'OddsComparison\\') !== 0) {
        return;
    }

    // Remove namespace prefix.
    $class_name = str_replace('OddsComparison\\', '', $class_name);
    
    // Convert namespace separators to directory separators.
    $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
    
    // Build the file path.
    $file = ODDS_COMPARISON_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR . $class_name . '.php';
    
    // Include the file if it exists.
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include the main plugin class.
require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Plugin.php';

/**
 * Initialize the plugin.
 *
 * @return void
 */
function odds_comparison_init() {
    $plugin = OddsComparison\Plugin::get_instance();
    $plugin->run();
}

// Hook the initialization.
add_action('plugins_loaded', 'odds_comparison_init');

/**
 * Activation hook callback.
 *
 * @return void
 */
function odds_comparison_activate() {
    // Create necessary database tables and set default options.
    require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Activator.php';
    OddsComparison\Activator::activate();
}

register_activation_hook(__FILE__, 'odds_comparison_activate');

/**
 * Deactivation hook callback.
 *
 * @return void
 */
function odds_comparison_deactivate() {
    // Clean up scheduled events and temporary data.
    require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Deactivator.php';
    OddsComparison\Deactivator::deactivate();
}

register_deactivation_hook(__FILE__, 'odds_comparison_deactivate');

/**
 * Uninstall hook callback.
 *
 * @return void
 */
function odds_comparison_uninstall() {
    // Remove all plugin data from the database.
    require_once ODDS_COMPARISON_PLUGIN_DIR . 'includes/Uninstaller.php';
    OddsComparison\Uninstaller::uninstall();
}

register_uninstall_hook(__FILE__, 'odds_comparison_uninstall');


