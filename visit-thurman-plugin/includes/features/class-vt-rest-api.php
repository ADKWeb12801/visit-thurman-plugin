<?php
/**
 * REST API handler for Visit Thurman plugin
 * @package VisitThurman
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VT_REST_API')) {
    class VT_REST_API {
        
        const NAMESPACE = 'visit-thurman/v1';
        
        public static function register_routes() {
            // Generic endpoint for all CPTs
            $post_types = ['events', 'businesses', 'accommodations', 'tca-members'];
            foreach ($post_types as $pt) {
                register_rest_route(self::NAMESPACE, '/' . $pt, array(
                    array(
                        'methods' => WP_REST_Server::READABLE,
                        'callback' => array(__CLASS__, 'get_posts_by_type'),
                        'permission_callback' => '__return_true',
                    ),
                ));
            }
            
            // User bookmarks endpoint
            register_rest_route(self::NAMESPACE, '/user/bookmarks', array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array(__CLASS__, 'get_user_bookmarks'),
                    'permission_callback' => array(__CLASS__, 'user_permission_check'),
                ),
            ));
        }

        public static function get_posts_by_type(WP_REST_Request $request) {
            $route = $request->get_route();
            $post_type_slug = trim(str_replace('/' . self::NAMESPACE . '/', '', $route), '/');

            $cpt_map = [
                'events'        => 'vt_event',
                'businesses'    => 'vt_business',
                'accommodations' => 'vt_accommodation',
                'tca-members'   => 'vt_tca_member',
            ];
            $post_type = $cpt_map[$post_type_slug] ?? '';

            if (empty($post_type)) {
                return new WP_Error('not_found', 'Invalid post type route', ['status' => 404]);
            }

            $args = ['post_type' => $post_type, 'posts_per_page' => 10];
            $query = new WP_Query($args);
            
            $posts_data = array();
            foreach ($query->get_posts() as $post) {
                $posts_data[] = self::prepare_post_for_response($post);
            }

            return new WP_REST_Response($posts_data, 200);
        }

        public static function get_user_bookmarks(WP_REST_Request $request) {
            // Stub for user bookmarks
            return new WP_REST_Response(['message' => 'User bookmarks endpoint.'], 200);
        }

        public static function user_permission_check() {
            return current_user_can('read');
        }

        protected static function prepare_post_for_response($post) {
            return [
                'id' => $post->ID,
                'title' => get_the_title($post),
                'link' => get_permalink($post),
                'excerpt' => get_the_excerpt($post),
                'featured_image' => get_the_post_thumbnail_url($post->ID, 'large'),
            ];
        }
    }
}
