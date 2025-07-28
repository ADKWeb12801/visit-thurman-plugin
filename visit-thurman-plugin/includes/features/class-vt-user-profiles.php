<?php
/**
 * User Profiles Feature
 * @package VisitThurman
 */

if (!defined('ABSPATH')) exit;

class VT_User_Profiles {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('show_user_profile', [$this, 'show_extra_profile_fields']);
        add_action('edit_user_profile', [$this, 'show_extra_profile_fields']);
        add_action('personal_options_update', [$this, 'save_extra_profile_fields']);
        add_action('edit_user_profile_update', [$this, 'save_extra_profile_fields']);
    }

    public function show_extra_profile_fields($user) {
        ?>
        <h3><?php _e('Visit Thurman Profile', 'visit-thurman'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="_vt_verified"><?php _e('Verified User', 'visit-thurman'); ?></label></th>
                <td>
                    <input type="checkbox" name="_vt_verified" id="_vt_verified" value="1" <?php checked(get_user_meta($user->ID, '_vt_verified', true), 1); ?> <?php if (!current_user_can('manage_options')) echo 'disabled'; ?>>
                    <span class="description"><?php _e('Verified users get a badge on their profile.', 'visit-thurman'); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_extra_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        if (current_user_can('manage_options') && isset($_POST['_vt_verified'])) {
            update_user_meta($user_id, '_vt_verified', 1);
        } else {
            delete_user_meta($user_id, '_vt_verified');
        }
    }

    public static function calculate_profile_completeness($user_id) {
        $user = get_userdata($user_id);
        $total_fields = 5;
        $completed_fields = 0;

        if (!empty($user->first_name)) $completed_fields++;
        if (!empty($user->last_name)) $completed_fields++;
        if (!empty($user->description)) $completed_fields++;
        if (get_avatar_url($user_id)) $completed_fields++; // Check if user has an avatar
        if (count_user_posts($user_id, 'any') > 0) $completed_fields++; // Check if user has created any posts

        return ($completed_fields / $total_fields) * 100;
    }
}
