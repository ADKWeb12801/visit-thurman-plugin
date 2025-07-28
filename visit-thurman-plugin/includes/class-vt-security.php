<?php
/**
 * Security handler for the Visit Thurman plugin.
 *
 * This class will contain methods for sanitization, validation,
 * and permission checks to ensure the plugin is secure.
 *
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class VT_Security {

    /**
     * Verify a nonce for a specific action.
     *
     * A wrapper for wp_verify_nonce for consistent use throughout the plugin.
     * Dies with a 403 error if the nonce is invalid.
     *
     * @param string $nonce The nonce to verify.
     * @param string $action The nonce action.
     */
    public static function verify_nonce($nonce, $action) {
        if (!isset($nonce) || !wp_verify_nonce($nonce, $action)) {
            wp_die(__('Invalid security token.', 'visit-thurman'), 'Security Check', array('response' => 403));
        }
    }

    /**
     * Check if the current user has the required capability.
     *
     * Dies with a 403 error if the user does not have permission.
     *
     * @param string $capability The capability to check.
     */
    public static function check_capability($capability) {
        if (!current_user_can($capability)) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'visit-thurman'), 'Permission Denied', array('response' => 403));
        }
    }

    /**
     * Sanitize an array of data recursively.
     *
     * @param array $array The array to sanitize.
     * @return array The sanitized array.
     */
    public static function sanitize_array(array $array) {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = self::sanitize_array($value);
            } else {
                $value = sanitize_text_field($value);
            }
        }
        return $array;
    }
}
