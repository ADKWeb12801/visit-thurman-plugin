<?php
/**
 * Events Custom Post Type
 * @package VisitThurman
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VT_Events')) {
    class VT_Events {
        
        const POST_TYPE = 'vt_event';
        
        public static function register() {
            add_action('init', array(__CLASS__, 'register_post_type'));
            add_action('init', array(__CLASS__, 'register_taxonomies'));
            add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
            add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save_meta_boxes'), 10, 2);
        }
        
        public static function register_post_type() {
            $labels = array(
                'name' => _x('Events', 'Post Type General Name', 'visit-thurman'),
                'singular_name' => _x('Event', 'Post Type Singular Name', 'visit-thurman'),
                'menu_name' => __('Events', 'visit-thurman'),
            );
            $args = array(
                'label' => __('Event', 'visit-thurman'),
                'labels' => $labels,
                'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_position' => 25,
                'menu_icon' => 'dashicons-calendar-alt',
                'has_archive' => true,
                'rewrite' => array('slug' => 'events'),
                'show_in_rest' => true,
            );
            register_post_type(self::POST_TYPE, $args);
        }
        
        public static function register_taxonomies() {
            register_taxonomy('vt_event_category', self::POST_TYPE, array(
                'label' => __('Event Categories', 'visit-thurman'),
                'rewrite' => array('slug' => 'event-category'),
                'hierarchical' => true,
            ));
            register_taxonomy('vt_event_tag', self::POST_TYPE, array(
                'label' => __('Event Tags', 'visit-thurman'),
                'rewrite' => array('slug' => 'event-tag'),
                'hierarchical' => false,
            ));
        }
        
        public static function add_meta_boxes() {
            add_meta_box(
                'vt_event_details',
                __('Event Details', 'visit-thurman'),
                array(__CLASS__, 'render_event_details_meta_box'),
                self::POST_TYPE,
                'normal',
                'high'
            );
        }
        
        public static function render_event_details_meta_box($post) {
            wp_nonce_field('vt_save_event_meta', 'vt_event_meta_nonce');
            // Meta box content would go here.
        }
        
        public static function save_meta_boxes($post_id, $post) {
            if (!isset($_POST['vt_event_meta_nonce']) || !wp_verify_nonce($_POST['vt_event_meta_nonce'], 'vt_save_event_meta')) {
                return;
            }
            // Save logic would go here.
        }
    }
}
