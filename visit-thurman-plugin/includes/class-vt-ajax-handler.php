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
}
