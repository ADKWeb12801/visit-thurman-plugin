<?php
/**
 * Accommodations Custom Post Type
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Accommodations {
    const POST_TYPE = 'vt_accommodation';

    public static function register() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'register_taxonomies']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta_boxes']);
    }

    public static function register_post_type() {
        $labels = [
            'name' => _x('Accommodations', 'Post Type General Name', 'visit-thurman'),
            'singular_name' => _x('Accommodation', 'Post Type Singular Name', 'visit-thurman'),
            'menu_name' => __('Accommodations', 'visit-thurman'),
        ];
        $args = [
            'label' => __('Accommodations', 'visit-thurman'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'menu_position' => 27,
            'menu_icon' => 'dashicons-building',
            'has_archive' => true,
            'rewrite' => ['slug' => 'accommodations'],
            'show_in_rest' => true,
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    public static function register_taxonomies() {
        register_taxonomy('vt_accommodation_category', self::POST_TYPE, [
            'label' => __('Categories', 'visit-thurman'),
            'rewrite' => ['slug' => 'accommodation-category'],
            'hierarchical' => true,
        ]);
        register_taxonomy('vt_accommodation_amenity', self::POST_TYPE, [
            'label' => __('Amenities', 'visit-thurman'),
            'rewrite' => ['slug' => 'amenity'],
            'hierarchical' => false,
        ]);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'vt_accommodation_details',
            __('Accommodation Details', 'visit-thurman'),
            [__CLASS__, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('vt_save_accommodation_meta', 'vt_accommodation_meta_nonce');
        $fields = [
            '_vt_booking_url' => __('Website URL', 'visit-thurman'),
            '_vt_address'     => __('Physical Address', 'visit-thurman'),
        ];
        echo '<div class="vt-meta-box">';
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<div class="vt-field-group"><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat"></div>';
        }
        echo '</div>';
    }

    public static function save_meta_boxes($post_id) {
        if (!isset($_POST['vt_accommodation_meta_nonce']) || !wp_verify_nonce($_POST['vt_accommodation_meta_nonce'], 'vt_save_accommodation_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        
        $fields = ['_vt_booking_url', '_vt_address'];
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field($_POST[$key]);
                if ($key === '_vt_booking_url') {
                    $value = esc_url_raw($value);
                }
                update_post_meta($post_id, $key, $value);
            }
        }
    }
}
