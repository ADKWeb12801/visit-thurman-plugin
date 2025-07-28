<?php
/**
 * Plugin Name: Visit Thurman Listings & Community Hub
 * Plugin URI: https://visitthurman.com
 * Description: A modular WordPress plugin for managing events, businesses, accommodations, and TCA members with social features.
 * Version: 1.0.3-debug
 * Author: Visit Thurman Development Team
 * License: GPL v2 or later
 * Text Domain: visit-thurman
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('VT_VERSION', '1.0.3-debug');
define('VT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('VT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The main plugin class.
 */
final class VisitThurmanPlugin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        $this->load_dependencies();

        // Register ONLY the Events CPT for this test.
        VT_Events::register();
        // VT_Businesses::register(); // Temporarily disabled
        // VT_Accommodations::register(); // Temporarily disabled
        // VT_TCA_Members::register(); // Temporarily disabled
    }

    private function load_dependencies() {
        // Core (Essential)
        require_once VT_PLUGIN_PATH . 'includes/class-vt-database.php';
        require_once VT_PLUGIN_PATH . 'includes/class-vt-security.php';
        
        // Post Types (Essential for activation and basic function)
        require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-events.php';
        // require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-businesses.php'; // Temporarily disabled
        // require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-accommodations.php'; // Temporarily disabled
        // require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-tca-members.php'; // Temporarily disabled
    }
    
    public function activate() {
        $this->load_dependencies();
        VT_Database::create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize the plugin.
VisitThurmanPlugin::get_instance();
