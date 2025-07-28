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
}
