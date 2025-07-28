<?php
/**
 * REST API handler for Visit Thurman plugin
 * 
 * @package VisitThurman
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VT_REST_API Class
 */
class VT_REST_API {
    
    /**
     * API namespace
     */
    const NAMESPACE = 'visit-thurman/v1';
    
    /**
     * Register REST API routes
     */
    public static function register_routes() {
        // Events endpoints
        register_rest_route(self::NAMESPACE, '/events', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_events'),
                'permission_callback' => '__return_true',
                'args' => self::get_collection_params(),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'create_event'),
                'permission_callback' => array(__CLASS__, 'create_permission_check'),
                'args' => self::get_event_args(),
            ),
        ));
        
        register_rest_route(self::NAMESPACE, '/events/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_event'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array(__CLASS__, 'update_event'),
                'permission_callback' => array(__CLASS__, 'update_permission_check'),
                'args' => self::get_event_args(),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array(__CLASS__, 'delete_event'),
                'permission_callback' => array(__CLASS__, 'delete_permission_check'),
            ),
        ));
        
        // Businesses endpoints
        register_rest_route(self::NAMESPACE, '/businesses', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_businesses'),
                'permission_callback' => '__return_true',
                'args' => self::get_collection_params(),
            ),
        ));
        
        // Accommodations endpoints
        register_rest_route(self::NAMESPACE, '/accommodations', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_accommodations'),
                'permission_callback' => '__return_true',
                'args' => self::get_collection_params(),
            ),
        ));
        
        // TCA Members endpoints
        register_rest_route(self::NAMESPACE, '/tca-members', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_tca_members'),
                'permission_callback' => '__return_true',
                'args' => self::get_collection_params(),
            ),
        ));
        
        // User endpoints
        register_rest_route(self::NAMESPACE, '/user/bookmarks', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_user_bookmarks'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'add_bookmark'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
                'args' => array(
                    'post_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array(__CLASS__, 'remove_bookmark'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
                'args' => array(
                    'post_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
        ));
        
        register_rest_route(self::NAMESPACE, '/user/notifications', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_user_notifications'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
            ),
        ));
        
        register_rest_route(self::NAMESPACE, '/user/notifications/(?P<id>\d+)/mark-read', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array(__CLASS__, 'mark_notification_read'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
            ),
        ));
        
        // Claim listing endpoints
        register_rest_route(self::NAMESPACE, '/claims', array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array(__CLASS__, 'create_claim'),
                'permission_callback' => array(__CLASS__, 'user_permission_check'),
                'args' => array(
                    'post_id' => array(
                        'required' => true,
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                    'verification_docs' => array(
                        'required' => false,
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ),
                ),
            ),
        ));
        
        // Search endpoint
        register_rest_route(self::NAMESPACE, '/search', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'search'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'q' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'type' => array(
                        'required' => false,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
        ));
        
        // Statistics endpoint
        register_rest_route(self::NAMESPACE, '/stats', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_stats'),
                'permission_callback' => '__return_true',
            ),
        ));
    }
    
    /**
     * Get collection parameters
     */
    private static function get_collection_params() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default' => 10,
                'sanitize_callback' => 'absint',
            ),
            'orderby' => array(
                'default' => 'date',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'order' => array(
                'default' => 'desc',
                'enum' => array('asc', 'desc'),
            ),
            'search' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'category' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'tag' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'meta_key' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'meta_value' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }
    
    /**
     * Get event arguments
     */
    private static function get_event_args() {
        return array(
            'title' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'content' => array(
                'sanitize_callback' => 'wp_kses_post',
            ),
            'excerpt' => array(
                'sanitize_callback' => 'sanitize_textarea_field',
            ),
            'start_date' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'start_time' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'end_date' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'end_time' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'venue' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'address' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'city' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'state' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'zip' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'online' => array(
                'sanitize_callback' => 'rest_sanitize_boolean',
            ),
            'online_url' => array(
                'sanitize_callback' => 'esc_url_raw',
            ),
            'cost' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'organizer' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'organizer_email' => array(
                'sanitize_callback' => 'sanitize_email',
            ),
            'organizer_phone' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'registration_url