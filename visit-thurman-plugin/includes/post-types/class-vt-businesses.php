<?php
/**
 * Businesses Custom Post Type
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Businesses {
    const POST_TYPE = 'vt_business';

    public static function register() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('init', [__CLASS__, 'register_taxonomies']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta_boxes']);
    }

    public static function register_post_type() {
        $labels = [
            'name' => _x('Businesses', 'Post Type General Name', 'visit-thurman'),
            'singular_name' => _x('Business', 'Post Type Singular Name', 'visit-thurman'),
            'menu_name' => __('Businesses', 'visit-thurman'),
            'all_items' => __('All Businesses', 'visit-thurman'),
            'add_new_item' => __('Add New Business', 'visit-thurman'),
        ];
        $args = [
            'label' => __('Businesses', 'visit-thurman'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'author'],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 26,
            'menu_icon' => 'dashicons-store',
            'has_archive' => true,
            'rewrite' => ['slug' => 'businesses'],
            'show_in_rest' => true,
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    public static function register_taxonomies() {
        register_taxonomy('vt_business_category', self::POST_TYPE, [
            'label' => __('Categories', 'visit-thurman'),
            'rewrite' => ['slug' => 'business-category'],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
        register_taxonomy('vt_business_tag', self::POST_TYPE, [
            'label' => __('Tags', 'visit-thurman'),
            'rewrite' => ['slug' => 'business-tag'],
            'hierarchical' => false,
            'show_in_rest' => true,
        ]);
    }

    public static function add_meta_boxes() {
        add_meta_box(
            'vt_business_details',
            __('Business Details', 'visit-thurman'),
            [__CLASS__, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post) {
        wp_nonce_field('vt_save_business_meta', 'vt_business_meta_nonce');
        $fields = [
            '_vt_services' => __('Services', 'visit-thurman'),
            '_vt_website'  => __('Website', 'visit-thurman'),
            '_vt_phone'    => __('Phone', 'visit-thurman'),
            '_vt_email'    => __('Email', 'visit-thurman'),
            '_vt_address'  => __('Address', 'visit-thurman'),
            '_vt_hours'    => __('Hours of Operation', 'visit-thurman'),
        ];

        $socials = [
            '_vt_facebook'  => __('Facebook URL', 'visit-thurman'),
            '_vt_instagram' => __('Instagram URL', 'visit-thurman'),
            '_vt_twitter'   => __('Twitter URL', 'visit-thurman'),
            '_vt_linkedin'  => __('LinkedIn URL', 'visit-thurman'),
            '_vt_tiktok'    => __('TikTok URL', 'visit-thurman'),
        ];
        echo '<div class="vt-meta-box">';
        foreach ($fields as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<div class="vt-field-group">';
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . '</label>';
            echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat">';
            echo '</div>';
        }

        echo '<h4>' . __('Socials', 'visit-thurman') . '</h4>';
        foreach ($socials as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<div class="vt-field-group">';
            echo '<label for="' . esc_attr($key) . '">' . esc_html($label) . '</label>';
            echo '<input type="url" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat">';
            echo '</div>';
        }
        echo '</div>';
    }

    public static function save_meta_boxes($post_id) {
        if (!isset($_POST['vt_business_meta_nonce']) || !wp_verify_nonce($_POST['vt_business_meta_nonce'], 'vt_save_business_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $fields = [
            '_vt_services','_vt_website','_vt_phone','_vt_email','_vt_address','_vt_hours',
            '_vt_facebook','_vt_instagram','_vt_twitter','_vt_linkedin','_vt_tiktok'
        ];
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field($_POST[$key]);
                $url_fields = ['_vt_website','_vt_facebook','_vt_instagram','_vt_twitter','_vt_linkedin','_vt_tiktok'];
                if (in_array($key, $url_fields, true)) {
                    $value = esc_url_raw($value);
                }
                if ($key === '_vt_email') {
                    $value = sanitize_email($value);
                }
                update_post_meta($post_id, $key, $value);
            }
        }
    }
}
