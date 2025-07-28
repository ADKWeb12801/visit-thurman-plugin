<?php
/**
 * Notifications Feature
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_Notifications {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function get_unread_count($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_notifications';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM $table WHERE user_id = %d AND is_read = 0",
            $user_id
        ));
    }
}
