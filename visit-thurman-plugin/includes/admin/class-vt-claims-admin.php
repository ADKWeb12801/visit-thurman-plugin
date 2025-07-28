<?php
if (!defined('ABSPATH')) exit;

class VT_Claims_Admin {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public function add_menu() {
        add_submenu_page(
            'visit-thurman',
            __('Claim Requests', 'visit-thurman'),
            __('Claim Requests', 'visit-thurman'),
            'manage_options',
            'vt-claim-requests',
            array($this, 'render_page')
        );
    }

    public function handle_actions() {
        if (!isset($_GET['vt_claim_action']) || !isset($_GET['_claim_nonce']) || !isset($_GET['claim_id'])) {
            return;
        }
        if (!wp_verify_nonce($_GET['_claim_nonce'], 'vt_claim_action')) {
            return;
        }
        if (!current_user_can('manage_options')) return;

        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        $claim_id = intval($_GET['claim_id']);
        $claim = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id=%d", $claim_id));
        if (!$claim) return;

        if ($_GET['vt_claim_action'] === 'approve') {
            wp_update_post(array(
                'ID' => $claim->post_id,
                'post_author' => $claim->user_id
            ));
            $wpdb->update($table, array('status' => 'approved', 'reviewed_at' => current_time('mysql'), 'reviewed_by' => get_current_user_id()), array('id' => $claim_id));
        } elseif ($_GET['vt_claim_action'] === 'deny') {
            $wpdb->update($table, array('status' => 'denied', 'reviewed_at' => current_time('mysql'), 'reviewed_by' => get_current_user_id()), array('id' => $claim_id));
        }

        wp_redirect(remove_query_arg(array('vt_claim_action', '_claim_nonce', 'claim_id')));
        exit;
    }

    public function render_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'vt_claim_requests';
        $claims = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
        echo '<div class="wrap"><h1>' . esc_html__('Claim Requests', 'visit-thurman') . '</h1>';
        if ($claims) {
            echo '<table class="widefat fixed striped"><thead><tr><th>' . esc_html__('Listing', 'visit-thurman') . '</th><th>' . esc_html__('User', 'visit-thurman') . '</th><th>' . esc_html__('Status', 'visit-thurman') . '</th><th>' . esc_html__('Actions', 'visit-thurman') . '</th></tr></thead><tbody>';
            foreach ($claims as $claim) {
                $title = get_the_title($claim->post_id);
                $user = get_userdata($claim->user_id);
                $approve_url = wp_nonce_url(add_query_arg(array('vt_claim_action' => 'approve', 'claim_id' => $claim->id)), 'vt_claim_action', '_claim_nonce');
                $deny_url = wp_nonce_url(add_query_arg(array('vt_claim_action' => 'deny', 'claim_id' => $claim->id)), 'vt_claim_action', '_claim_nonce');
                echo '<tr>';
                echo '<td>' . esc_html($title) . '</td>';
                echo '<td>' . esc_html($user ? $user->display_name : $claim->user_id) . '</td>';
                echo '<td>' . esc_html(ucfirst($claim->status)) . '</td>';
                echo '<td>';
                if ($claim->status === 'pending') {
                    echo '<a class="button" href="' . esc_url($approve_url) . '">' . esc_html__('Approve', 'visit-thurman') . '</a> ';
                    echo '<a class="button" href="' . esc_url($deny_url) . '">' . esc_html__('Deny', 'visit-thurman') . '</a>';
                } else {
                    echo esc_html__('Reviewed', 'visit-thurman');
                }
                echo '</td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__('No claim requests found.', 'visit-thurman') . '</p>';
        }
        echo '</div>';
    }
}
