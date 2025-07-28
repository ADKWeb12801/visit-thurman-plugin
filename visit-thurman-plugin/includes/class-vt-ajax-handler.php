<?php
/**
 * AJAX Handler
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Ajax_Handler {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_vt_toggle_bookmark', [$this, 'toggle_bookmark']);
        add_action('wp_ajax_nopriv_vt_toggle_bookmark', [$this, 'toggle_bookmark']);
        add_action('wp_ajax_vt_fetch_listings', [$this, 'fetch_listings']);
        add_action('wp_ajax_nopriv_vt_fetch_listings', [$this, 'fetch_listings']);
    }

    public function toggle_bookmark() {
        check_ajax_referer('vt_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(['message' => 'Invalid post.']);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vt_bookmarks';
        $user_id = get_current_user_id();

        $is_bookmarked = VT_Bookmarks::user_has_bookmark($post_id, $user_id);

        if ($is_bookmarked) {
            $wpdb->delete($table, ['user_id' => $user_id, 'post_id' => $post_id]);
            wp_send_json_success(['status' => 'removed']);
        } else {
            $wpdb->insert($table, [
                'user_id' => $user_id,
                'post_id' => $post_id,
                'post_type' => get_post_type($post_id),
            ]);
            wp_send_json_success(['status' => 'added']);
        }
    }

    public function fetch_listings() {
        check_ajax_referer('vt_ajax_nonce', 'nonce');

        $post_type = isset($_POST['post_type']) ? sanitize_key($_POST['post_type']) : '';
        $limit     = isset($_POST['limit']) ? intval($_POST['limit']) : 12;
        $columns   = isset($_POST['columns']) ? intval($_POST['columns']) : 3;
        $orderby   = isset($_POST['orderby']) ? sanitize_text_field($_POST['orderby']) : 'date';
        $order     = isset($_POST['order']) ? sanitize_text_field($_POST['order']) : 'DESC';
        $search    = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $category  = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $paged     = isset($_POST['page']) ? intval($_POST['page']) : 1;

        if (!post_type_exists($post_type)) {
            wp_send_json_error(['message' => 'Invalid post type']);
        }

        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
            's'             => $search,
        ];

        if ($category) {
            $taxonomy = $post_type . '_category';
            if (taxonomy_exists($taxonomy)) {
                $args['tax_query'][] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $category,
                ];
            }
        }

        $html = VT_Shortcodes::render_listings($args, $columns, true);
        wp_send_json_success(['html' => $html]);
    }
}
