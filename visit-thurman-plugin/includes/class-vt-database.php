<?php
/**
 * Database handler for Visit Thurman plugin.
 * Manages the creation and maintenance of custom database tables.
 *
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class VT_Database {
    
    /**
     * Create all custom tables required by the plugin.
     * This method is called on plugin activation.
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // --- Bookmarks Table ---
        $table_name = $wpdb->prefix . 'vt_bookmarks';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            post_type varchar(50) NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_post (user_id,post_id),
            KEY user_id (user_id),
            KEY post_id (post_id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Notifications Table ---
        $table_name = $wpdb->prefix . 'vt_notifications';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            message text NOT NULL,
            link varchar(255) DEFAULT '' NOT NULL,
            is_read tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY is_read (is_read)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Claim Requests Table ---
        $table_name = $wpdb->prefix . 'vt_claim_requests';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            verification_docs text,
            admin_notes text,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            reviewed_at datetime,
            reviewed_by bigint(20) UNSIGNED,
            PRIMARY KEY  (id),
            UNIQUE KEY user_post (user_id,post_id),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);

        // --- User Notes Table ---
        $table_name = $wpdb->prefix . 'vt_user_notes';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            post_id bigint(20) UNSIGNED NOT NULL,
            note text NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY user_post (user_id,post_id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Private Messages Table ---
        $table_name = $wpdb->prefix . 'vt_messages';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            thread_id bigint(20) UNSIGNED NOT NULL,
            sender_id bigint(20) UNSIGNED NOT NULL,
            recipient_id bigint(20) UNSIGNED NOT NULL,
            subject varchar(255) DEFAULT '' NOT NULL,
            message text NOT NULL,
            is_read tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY thread_id (thread_id),
            KEY sender_id (sender_id),
            KEY recipient_id (recipient_id)
        ) $charset_collate;";
        dbDelta($sql);

        // --- Activity Feed Table ---
        $table_name = $wpdb->prefix . 'vt_activity';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            action_type varchar(50) NOT NULL,
            object_id bigint(20) UNSIGNED,
            object_type varchar(50),
            description text NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY action_type (action_type)
        ) $charset_collate;";
        dbDelta($sql);

        // Store a version number to handle future upgrades
        update_option('vt_db_version', '1.0.0');
    }
}
