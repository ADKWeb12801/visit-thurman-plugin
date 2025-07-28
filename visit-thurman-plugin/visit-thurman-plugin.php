<?php
/**
 * Plugin Name: Visit Thurman Listings & Community Hub
 * Plugin URI: https://visitthurman.com
 * Description: A modular WordPress plugin for managing events, businesses, accommodations, and TCA members with social features.
 * Version: 1.1.0
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
define('VT_VERSION', '1.1.0');
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

        load_plugin_textdomain('visit-thurman', false, dirname(VT_PLUGIN_BASENAME) . '/languages');

        VT_Events::register();
        VT_Businesses::register();
        VT_Accommodations::register();
        VT_TCA_Members::register();
        VT_Shortcodes::register();

        add_action('rest_api_init', array('VT_REST_API', 'register_routes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        VT_User_Profiles::get_instance();
        VT_Claim_Listings::get_instance();
        VT_Bookmarks::get_instance();
        VT_Notifications::get_instance();
        VT_Social_Login::get_instance();
        VT_Import_Export::get_instance();
        VT_Ajax_Handler::get_instance();
        VT_Templates::get_instance();
        
        if (is_admin()) {
            VT_Admin::get_instance();
        }
    }

    private function load_dependencies() {
        // Core
        require_once VT_PLUGIN_PATH . 'includes/class-vt-database.php';
        require_once VT_PLUGIN_PATH . 'includes/class-vt-ajax-handler.php';
        require_once VT_PLUGIN_PATH . 'includes/class-vt-security.php';
        
        // Post Types (Using the standardized 'class-vt-' prefix)
        require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-events.php';
        require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-businesses.php';
        require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-accommodations.php';
        require_once VT_PLUGIN_PATH . 'includes/post-types/class-vt-tca-members.php';
        
        // Features
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-user-profiles.php';
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-claim-listings.php';
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-bookmarks.php';
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-notifications.php';
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-social-login.php';
        require_once VT_PLUGIN_PATH . 'includes/features/class-vt-import-export.php';
        require_once VT_PLUGIN_PATH . 'includes/features/vt-rest-api-class.php';
        
        // Frontend
        require_once VT_PLUGIN_PATH . 'includes/frontend/class-vt-shortcodes.php';
        require_once VT_PLUGIN_PATH . 'includes/frontend/class-vt-templates.php';
        
        // Admin
        if (is_admin()) {
            require_once VT_PLUGIN_PATH . 'includes/admin/class-vt-admin.php';
            require_once VT_PLUGIN_PATH . 'includes/admin/class-vt-admin-settings.php';
        }
    }
    
    public function activate() {
        $this->load_dependencies();
        VT_Database::create_tables();
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function enqueue_frontend_assets() {
        wp_enqueue_style('vt-frontend', VT_PLUGIN_URL . 'assets/css/frontend.css', array(), VT_VERSION);
        wp_enqueue_script('vt-frontend', VT_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), VT_VERSION, true);
        wp_localize_script('vt-frontend', 'vt_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vt_ajax_nonce'),
        ));
    }
    
    public function enqueue_admin_assets() {
        wp_enqueue_style('vt-admin', VT_PLUGIN_URL . 'assets/css/admin.css', array(), VT_VERSION);
        wp_enqueue_script('vt-admin', VT_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), VT_VERSION, true);
    }
}

VisitThurmanPlugin::get_instance();

