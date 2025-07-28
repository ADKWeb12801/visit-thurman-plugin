<?php
/**
 * Events Custom Post Type
 * @package VisitThurman
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('VT_Events')) {
    class VT_Events {
        
        const POST_TYPE = 'vt_event';
        
        public static function register() {
            add_action('init', array(__CLASS__, 'register_post_type'));
            add_action('init', array(__CLASS__, 'register_taxonomies'));
            add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
            add_action('save_post_' . self::POST_TYPE, array(__CLASS__, 'save_meta_boxes'), 10, 2);
        }
        
        public static function register_post_type() {
            $labels = array(
                'name' => _x('Events', 'Post Type General Name', 'visit-thurman'),
                'singular_name' => _x('Event', 'Post Type Singular Name', 'visit-thurman'),
                'menu_name' => __('Events', 'visit-thurman'),
            );
            $args = array(
                'label' => __('Event', 'visit-thurman'),
                'labels' => $labels,
                'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'author', 'comments'),
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_position' => 25,
                'menu_icon' => 'dashicons-calendar-alt',
                'has_archive' => true,
                'rewrite' => array('slug' => 'events'),
                'show_in_rest' => true,
            );
            register_post_type(self::POST_TYPE, $args);
        }
        
        public static function register_taxonomies() {
            register_taxonomy('vt_event_category', self::POST_TYPE, array(
                'label' => __('Event Categories', 'visit-thurman'),
                'rewrite' => array('slug' => 'event-category'),
                'hierarchical' => true,
            ));
            register_taxonomy('vt_event_tag', self::POST_TYPE, array(
                'label' => __('Event Tags', 'visit-thurman'),
                'rewrite' => array('slug' => 'event-tag'),
                'hierarchical' => false,
            ));
        }
        
        public static function add_meta_boxes() {
            add_meta_box(
                'vt_event_details',
                __('Event Details', 'visit-thurman'),
                array(__CLASS__, 'render_event_details_meta_box'),
                self::POST_TYPE,
                'normal',
                'high'
            );
        }
        
        public static function render_event_details_meta_box($post) {
            wp_nonce_field('vt_save_event_meta', 'vt_event_meta_nonce');
            $fields = [
                '_vt_start_date'    => __('Event Start Date', 'visit-thurman'),
                '_vt_end_date'      => __('Event End Date', 'visit-thurman'),
                '_vt_time'          => __('Time Range', 'visit-thurman'),
                '_vt_location_name' => __('Location Name', 'visit-thurman'),
                '_vt_address'       => __('Address', 'visit-thurman'),
                '_vt_website'       => __('Website / RSVP', 'visit-thurman'),
            ];

            $organizers = get_posts(['post_type' => VT_Organizers::POST_TYPE, 'numberposts' => -1]);
            $venues = get_posts(['post_type' => VT_Venues::POST_TYPE, 'numberposts' => -1]);

            echo '<div class="vt-meta-box">';
            foreach ($fields as $key => $label) {
                $value = get_post_meta($post->ID, $key, true);
                echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br/>';
                $type = ($key === '_vt_start_date' || $key === '_vt_end_date') ? 'date' : 'text';
                echo '<input type="' . $type . '" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat"/></p>';
            }

            // Organizer dropdown
            $selected_org = get_post_meta($post->ID, '_vt_organizer_id', true);
            echo '<p><label>' . __('Organizer', 'visit-thurman') . '</label><br/>';
            echo '<select name="_vt_organizer_id"><option value="">' . __('Select organizer', 'visit-thurman') . '</option>';
            foreach ($organizers as $org) {
                printf('<option value="%d"%s>%s</option>', $org->ID, selected($selected_org, $org->ID, false), esc_html($org->post_title));
            }
            echo '</select></p>';

            // Venue dropdown
            $selected_venue = get_post_meta($post->ID, '_vt_venue_id', true);
            echo '<p><label>' . __('Venue', 'visit-thurman') . '</label><br/>';
            echo '<select name="_vt_venue_id"><option value="">' . __('Select venue', 'visit-thurman') . '</option>';
            foreach ($venues as $venue) {
                printf('<option value="%d"%s>%s</option>', $venue->ID, selected($selected_venue, $venue->ID, false), esc_html($venue->post_title));
            }
            echo '</select></p>';
            echo '</div>';
        }

        public static function save_meta_boxes($post_id, $post) {
            if (!isset($_POST['vt_event_meta_nonce']) || !wp_verify_nonce($_POST['vt_event_meta_nonce'], 'vt_save_event_meta')) {
                return;
            }
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (!current_user_can('edit_post', $post_id)) return;

            $fields = ['_vt_start_date','_vt_end_date','_vt_time','_vt_location_name','_vt_address','_vt_website','_vt_organizer_id','_vt_venue_id'];
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    $value = sanitize_text_field($_POST[$field]);
                    update_post_meta($post_id, $field, $value);
                }
            }

            $start = isset($_POST['_vt_start_date']) ? sanitize_text_field($_POST['_vt_start_date']) : '';
            if ($start && $start < current_time('Y-m-d')) {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('Event start date is in the past.', 'visit-thurman') . '</p></div>';
                });
            }
        }
    }
}
