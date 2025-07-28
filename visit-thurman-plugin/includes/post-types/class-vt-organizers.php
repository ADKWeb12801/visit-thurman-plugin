<?php
if (!defined('ABSPATH')) exit;

class VT_Organizers {
    const POST_TYPE = 'vt_organizer';

    public static function register() {
        add_action('init', [__CLASS__, 'register_post_type']);
        add_action('add_meta_boxes', [__CLASS__, 'add_meta_boxes']);
        add_action('save_post_' . self::POST_TYPE, [__CLASS__, 'save_meta']);
    }

    public static function register_post_type() {
        $labels = [
            'name' => _x('Organizers', 'Post Type General Name', 'visit-thurman'),
            'singular_name' => _x('Organizer', 'Post Type Singular Name', 'visit-thurman'),
            'menu_name' => __('Organizers', 'visit-thurman'),
        ];
        $args = [
            'label' => __('Organizers', 'visit-thurman'),
            'labels' => $labels,
            'supports' => ['title', 'editor', 'thumbnail'],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=' . VT_Events::POST_TYPE,
            'menu_position' => 5,
            'show_in_rest' => true,
        ];
        register_post_type(self::POST_TYPE, $args);
    }

    public static function add_meta_boxes() {
        add_meta_box('vt_organizer_details', __('Organizer Details', 'visit-thurman'), [__CLASS__, 'render_meta'], self::POST_TYPE, 'normal', 'high');
    }

    public static function render_meta($post) {
        wp_nonce_field('vt_save_organizer_meta', 'vt_organizer_meta_nonce');
        $email = get_post_meta($post->ID, '_vt_email', true);
        $phone = get_post_meta($post->ID, '_vt_phone', true);
        echo '<p><label>' . __('Email', 'visit-thurman') . '</label><br/>';
        echo '<input type="email" name="_vt_email" value="' . esc_attr($email) . '" class="widefat"/></p>';
        echo '<p><label>' . __('Phone', 'visit-thurman') . '</label><br/>';
        echo '<input type="text" name="_vt_phone" value="' . esc_attr($phone) . '" class="widefat"/></p>';
    }

    public static function save_meta($post_id) {
        if (!isset($_POST['vt_organizer_meta_nonce']) || !wp_verify_nonce($_POST['vt_organizer_meta_nonce'], 'vt_save_organizer_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        if (isset($_POST['_vt_email'])) update_post_meta($post_id, '_vt_email', sanitize_email($_POST['_vt_email']));
        if (isset($_POST['_vt_phone'])) update_post_meta($post_id, '_vt_phone', sanitize_text_field($_POST['_vt_phone']));
    }
}
