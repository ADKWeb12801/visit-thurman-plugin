<?php
/**
 * AJAX Handler
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

/**
 * Handles AJAX requests for Visit Thurman plugin.
 */
class VT_Ajax_Handler {
    private static $instance;

    /**
     * Get singleton instance.
     * @return VT_Ajax_Handler
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor: Registers AJAX actions.
     */
    private function __construct() {
        add_action('wp_ajax_vt_toggle_bookmark', [$this, 'toggle_bookmark']);
        add_action('wp_ajax_nopriv_vt_toggle_bookmark', [$this, 'toggle_bookmark']);
        add_action('wp_ajax_vt_fetch_listings', [$this, 'fetch_listings']);
        add_action('wp_ajax_nopriv_vt_fetch_listings', [$this, 'fetch_listings']);
    }

    /**
     * Toggle bookmark for a post via AJAX.
     * Checks user capability and validates input.
     */
    public function toggle_bookmark() {
        check_ajax_referer('vt_ajax_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in to bookmark posts.', 'visit-thurman')]);
        }

        // Optionally, check capability (customize as needed)
        if (!current_user_can('read')) {
            wp_send_json_error(['message' => __('You do not have permission to bookmark posts.', 'visit-thurman')]);
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error(['message' => __('Invalid or missing post.', 'visit-thurman')]);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vt_bookmarks';
        $user_id = get_current_user_id();

        $is_bookmarked = VT_Bookmarks::user_has_bookmark($post_id, $user_id);

        if ($is_bookmarked) {
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table WHERE user_id = %d AND post_id = %d",
                    $user_id,
                    $post_id
                )
            );
            if ($deleted !== false) {
                wp_send_json_success(['status' => 'removed']);
            } else {
                wp_send_json_error(['message' => __('Failed to remove bookmark. Please try again.', 'visit-thurman')]);
            }
        } else {
            $inserted = $wpdb->insert(
                $table,
                [
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'post_type' => sanitize_text_field(get_post_type($post_id)),
                ],
                ['%d', '%d', '%s']
            );
            if ($inserted) {
                wp_send_json_success(['status' => 'added']);
            } else {
                wp_send_json_error(['message' => __('Failed to add bookmark. Please try again.', 'visit-thurman')]);
            }
        }
    }

    /**
     * Fetch listings via AJAX.
     * Validates and sanitizes all input.
     */
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

        if (!$post_type || !post_type_exists($post_type)) {
            wp_send_json_error(['message' => __('Invalid or missing post type.', 'visit-thurman')]);
        }

        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => $limit,
            'paged'          => $paged,
            'orderby'        => $orderby,
            'order'          => $order,
            's'              => $search,
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
        if ($html) {
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => __('No listings found.', 'visit-thurman')]);
        }
    }
}
