<?php
/**
 * Bookmarks Feature
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Bookmarks {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function user_has_bookmark($post_id, $user_id = null) {
        if (!is_user_logged_in()) return false;
        $user_id = $user_id ?? get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'vt_bookmarks';
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM $table WHERE user_id = %d AND post_id = %d",
            $user_id,
            $post_id
        ));
        return (bool) $exists;
    }
    
    public static function get_user_bookmarks_count($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_bookmarks';
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $table WHERE user_id = %d", $user_id));
    }
}
