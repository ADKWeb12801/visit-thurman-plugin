<?php
/**
 * TCA Members Custom Post Type
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_TCA_Members {
    const POST_TYPE = 'vt_tca_member';

    public static function register() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta_boxes']);
    }

    public static function register_post_type() {
        $labels = [
            'name' => _x('TCA Members', 'Post Type General Name', 'visit-thurman'),
            'singular_name' => _x('TCA Member', 'Post Type Singular Name', 'visit-thurman'),
            'menu_name' => __('TCA Members', 'visit-thurman'),
        ];
        $args = [
            'label' => __('TCA Members', 'visit-thurman'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'menu_position' => 28,
            'menu_icon' => 'dashicons-groups',
            'has_archive' => true,
            'rewrite' => ['slug' => 'tca-members'],
            'show_in_rest' => true,
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'vt_tca_member_details',
            __('TCA Member Details', 'visit-thurman'),
            [__CLASS__, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('vt_save_tca_member_meta', 'vt_tca_member_meta_nonce');
        $fields = [
            '_vt_role' => __('Role', 'visit-thurman'),
            '_vt_years_active' => __('Years Active', 'visit-thurman'),
            '_vt_business_affiliation' => __('Business Affiliation', 'visit-thurman'),
        ];
        echo '<div class="vt-meta-box">';
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<div class="vt-field-group"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat"></div>';
        }
        echo '</div>';
    }

    public static function save_meta_boxes($post_id) {
        if (!isset($_POST['vt_tca_member_meta_nonce']) || !wp_verify_nonce($_POST['vt_tca_member_meta_nonce'], 'vt_save_tca_member_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $fields = ['_vt_role', '_vt_years_active', '_vt_business_affiliation'];
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field($_POST[$key]));
            }
        }
    }
}
