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
     *
     * @action vt_before_toggle_bookmark (int $post_id, int $user_id, bool $is_bookmarked)
     * @action vt_after_toggle_bookmark (int $post_id, int $user_id, string $action, bool $result)
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

        /**
         * Fires before toggling a bookmark.
         *
         * @param int $post_id
         * @param int $user_id
         * @param bool $is_bookmarked
         */
        do_action('vt_before_toggle_bookmark', $post_id, $user_id, $is_bookmarked);

        if ($is_bookmarked) {
            $deleted = $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $table WHERE user_id = %d AND post_id = %d",
                    $user_id,
                    $post_id
                )
            );
            /**
             * Fires after toggling a bookmark (removal).
             *
             * @param int $post_id
             * @param int $user_id
             * @param string $action
             * @param bool $result
             */
            do_action('vt_after_toggle_bookmark', $post_id, $user_id, 'removed', $deleted !== false);
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
            /**
             * Fires after toggling a bookmark (addition).
             *
             * @param int $post_id
             * @param int $user_id
             * @param string $action
             * @param bool $result
             */
            do_action('vt_after_toggle_bookmark', $post_id, $user_id, 'added', (bool)$inserted);
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
     *
     * @filter vt_fetch_listings_args (array $args, array $raw_input)
     * @action vt_before_fetch_listings (array $args)
     * @action vt_after_fetch_listings (array $args, string $html)
     */
    public function fetch_listings() {
        check_ajax_referer('vt_ajax_nonce', 'nonce');

        $raw_input = $_POST;
        $post_type = isset($raw_input['post_type']) ? sanitize_key($raw_input['post_type']) : '';
        $limit     = isset($raw_input['limit']) ? intval($raw_input['limit']) : 12;
        $columns   = isset($raw_input['columns']) ? intval($raw_input['columns']) : 3;
        $orderby   = isset($raw_input['orderby']) ? sanitize_text_field($raw_input['orderby']) : 'date';
        $order     = isset($raw_input['order']) ? sanitize_text_field($raw_input['order']) : 'DESC';
        $search    = isset($raw_input['search']) ? sanitize_text_field($raw_input['search']) : '';
        $category  = isset($raw_input['category']) ? sanitize_text_field($raw_input['category']) : '';
        $paged     = isset($raw_input['page']) ? intval($raw_input['page']) : 1;

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

        /**
         * Filter the query args before fetching listings.
         *
         * @param array $args
         * @param array $raw_input
         */
        $args = apply_filters('vt_fetch_listings_args', $args, $raw_input);

        /**
         * Fires before fetching listings.
         *
         * @param array $args
         */
        do_action('vt_before_fetch_listings', $args);

        $html = VT_Shortcodes::render_listings($args, $columns, true);

        /**
         * Fires after fetching listings.
         *
         * @param array $args
         * @param string $html
         */
        do_action('vt_after_fetch_listings', $args, $html);

        if ($html) {
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => __('No listings found.', 'visit-thurman')]);
        }
    }
}
