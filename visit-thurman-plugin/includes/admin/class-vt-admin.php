<?php
/**
 * Admin handler for Visit Thurman plugin
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Visit Thurman', 'visit-thurman'),
            __('Visit Thurman', 'visit-thurman'),
            'manage_options',
            'visit-thurman',
            array($this, 'create_dashboard_page'),
            'dashicons-location-alt',
            20
        );

        add_submenu_page(
            'visit-thurman',
            __('Settings', 'visit-thurman'),
            __('Settings', 'visit-thurman'),
            'manage_options',
            'vt-settings',
            array('VT_Admin_Settings', 'create_settings_page')
        );
    }

    public function create_dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p><?php _e('Welcome to the Visit Thurman plugin dashboard. Manage your listings, settings, and more from this central hub.', 'visit-thurman'); ?></p>
            
            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div class="postbox-container">
                        <div class="meta-box-sortables">
                            <div class="postbox">
                                <h2 class="hndle"><span><?php _e('At a Glance', 'visit-thurman'); ?></span></h2>
                                <div class="inside">
                                    <ul>
                                        <li><strong><?php echo wp_count_posts(VT_Events::POST_TYPE)->publish; ?></strong> <?php _e('Events', 'visit-thurman'); ?></li>
                                        <li><strong><?php echo wp_count_posts(VT_Businesses::POST_TYPE)->publish; ?></strong> <?php _e('Businesses', 'visit-thurman'); ?></li>
                                        <li><strong><?php echo wp_count_posts(VT_Accommodations::POST_TYPE)->publish; ?></strong> <?php _e('Accommodations', 'visit-thurman'); ?></li>
                                        <li><strong><?php echo wp_count_posts(VT_TCA_Members::POST_TYPE)->publish; ?></strong> <?php _e('TCA Members', 'visit-thurman'); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }
}
