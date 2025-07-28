<?php
/**
 * Claim Listings Feature
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Claim_Listings {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'maybe_handle_claim_request'));
    }

    public static function get_user_claim($post_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d AND user_id = %d", $post_id, $user_id));
    }
    
    public static function get_user_claims_count($user_id, $status = 'pending') {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table WHERE user_id = %d AND status = %s", $user_id, $status));
    }

    public static function get_claims_for_user($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC", $user_id));
    }

    public function maybe_handle_claim_request() {
        if (!isset($_POST['vt_claim_listing_nonce']) || !isset($_POST['post_id'])) {
            return;
        }
        if (!is_user_logged_in()) return;
        if (!wp_verify_nonce($_POST['vt_claim_listing_nonce'], 'vt_claim_listing')) return;

        $post_id = intval($_POST['post_id']);
        $user_id = get_current_user_id();

        if (self::get_user_claim($post_id, $user_id)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        $wpdb->insert($table, array(
            'user_id'    => $user_id,
            'post_id'    => $post_id,
            'status'     => 'pending',
            'created_at' => current_time('mysql'),
        ), array('%d','%d','%s','%s'));
    }

    public static function render_claim_button($post_id = null) {
        $post_id = $post_id ? intval($post_id) : get_the_ID();
        if (!post_type_exists(get_post_type($post_id))) return '';

        if (!is_user_logged_in()) {
            return '<a href="' . esc_url(wp_login_url(get_permalink($post_id))) . '" class="vt-button">' . __('Log in to claim', 'visit-thurman') . '</a>';
        }

        $user_id = get_current_user_id();
        $existing = self::get_user_claim($post_id, $user_id);
        if ($existing) {
            return '<span class="vt-meta">' . __('Claim pending approval', 'visit-thurman') . '</span>';
        }

        $nonce = wp_create_nonce('vt_claim_listing');
        return '<form method="post" class="vt-claim-form"><input type="hidden" name="post_id" value="' . esc_attr($post_id) . '"><input type="hidden" name="vt_claim_listing_nonce" value="' . esc_attr($nonce) . '"><button type="submit" class="vt-button">' . __('Claim This Listing', 'visit-thurman') . '</button></form>';
    }
}
