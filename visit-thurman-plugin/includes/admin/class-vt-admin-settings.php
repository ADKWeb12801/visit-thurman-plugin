<?php
/**
 * Admin Settings Page handler for Visit Thurman plugin
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Admin_Settings {

    public static function create_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Visit Thurman Settings', 'visit-thurman'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('vt_settings_group');
                do_settings_sections('vt-settings-admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function page_init() {
        register_setting(
            'vt_settings_group',
            'vt_options',
            array(__CLASS__, 'sanitize')
        );

        add_settings_section(
            'vt_setting_section_general',
            __('General Settings', 'visit-thurman'),
            array(__CLASS__, 'print_section_info'),
            'vt-settings-admin'
        );

        add_settings_field(
            'primary_color',
            __('Primary Color', 'visit-thurman'),
            array(__CLASS__, 'primary_color_callback'),
            'vt-settings-admin',
            'vt_setting_section_general'
        );

        add_settings_field(
            'accent_color',
            __('Accent Color', 'visit-thurman'),
            array(__CLASS__, 'accent_color_callback'),
            'vt-settings-admin',
            'vt_setting_section_general'
        );
    }

    public static function sanitize($input) {
        $sanitized_input = array();
        if (isset($input['primary_color'])) {
            $sanitized_input['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        if (isset($input['accent_color'])) {
            $sanitized_input['accent_color'] = sanitize_hex_color($input['accent_color']);
        }
        return $sanitized_input;
    }

    public static function print_section_info() {
        _e('Enter your plugin settings below:', 'visit-thurman');
    }

    public static function primary_color_callback() {
        $options = get_option('vt_options');
        printf(
            '<input type="text" id="primary_color" name="vt_options[primary_color]" value="%s" class="color-picker" />',
            isset($options['primary_color']) ? esc_attr($options['primary_color']) : '#336633'
        );
    }

    public static function accent_color_callback() {
        $options = get_option('vt_options');
        printf(
            '<input type="text" id="accent_color" name="vt_options[accent_color]" value="%s" class="color-picker" />',
            isset($options['accent_color']) ? esc_attr($options['accent_color']) : '#FF9933'
        );
    }
}

if (is_admin()) {
    add_action('admin_init', array('VT_Admin_Settings', 'page_init'));
}
