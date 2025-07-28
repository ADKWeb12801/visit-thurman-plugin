<?php
/**
 * Shortcodes handler for Visit Thurman plugin
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Shortcodes {
    
    public static function register() {
        $post_types = ['events', 'businesses', 'accommodations', 'tca_members'];
        foreach ($post_types as $pt) {
            add_shortcode('vt_' . $pt, array(__CLASS__, 'render_listing_shortcode'));
        }

        add_shortcode('vt_next_events', array(__CLASS__, 'next_events_shortcode'));
        add_shortcode('vt_upcoming_events', array(__CLASS__, 'upcoming_events_shortcode'));
        
        add_shortcode('vt_user_profile', array(__CLASS__, 'user_profile_shortcode'));
        add_shortcode('vt_user_dashboard', array(__CLASS__, 'user_dashboard_shortcode'));
        add_shortcode('vt_claim_listing', array(__CLASS__, 'claim_listing_shortcode'));
        add_shortcode('vt_bookmark_button', array(__CLASS__, 'bookmark_button_shortcode'));
    }
    
    public static function render_listing_shortcode($atts, $content = null, $tag = '') {
        // Robustly map the shortcode tag (e.g., 'vt_events') to the CPT slug (e.g., 'vt_event')
        $tag_to_cpt_map = [
            'vt_events'        => 'vt_event',
            'vt_businesses'    => 'vt_business',
            'vt_accommodations' => 'vt_accommodation',
            'vt_tca_members'   => 'vt_tca_member',
        ];
        $post_type = $tag_to_cpt_map[$tag] ?? '';

        // If the tag is invalid, return an HTML comment to avoid breaking the page.
        if (empty($post_type) || !post_type_exists($post_type)) {
            return '<!-- Visit Thurman: Invalid shortcode tag: ' . esc_html($tag) . ' -->';
        }

        $atts = shortcode_atts(array(
            'limit' => 12,
            'columns' => 3,
        ), $atts);
        
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $args = array(
            'post_type' => $post_type,
            'posts_per_page' => intval($atts['limit']),
            'post_status' => 'publish',
            'paged' => $paged,
        );
        
        $query = new WP_Query($args);
        
        ob_start();
        
        if ($query->have_posts()) {
            echo '<div class="vt-grid vt-grid-' . esc_attr($atts['columns']) . '">';
            while ($query->have_posts()) {
                $query->the_post();
                self::render_card(get_the_ID());
            }
            echo '</div>';

            // Pagination
            echo '<div class="vt-pagination">';
            echo paginate_links(array(
                'total' => $query->max_num_pages,
                'current' => $paged,
                'prev_text' => __('&laquo; Prev', 'visit-thurman'),
                'next_text' => __('Next &raquo;', 'visit-thurman'),
            ));
            echo '</div>';

        } else {
            echo '<p>' . __('No listings found.', 'visit-thurman') . '</p>';
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function render_card($post_id) {
        ?>
        <div class="vt-card">
            <?php if (has_post_thumbnail($post_id)) : ?>
                <div class="vt-card-image">
                    <a href="<?php echo get_permalink($post_id); ?>">
                        <?php echo get_the_post_thumbnail($post_id, 'medium_large'); ?>
                    </a>
                </div>
            <?php endif; ?>
            <div class="vt-card-content">
                <h3 class="vt-card-title">
                    <a href="<?php echo get_permalink($post_id); ?>"><?php echo get_the_title($post_id); ?></a>
                </h3>
                <div class="vt-card-excerpt">
                    <?php echo get_the_excerpt($post_id); ?>
                </div>
                <div class="vt-card-footer">
                    <a href="<?php echo get_permalink($post_id); ?>" class="vt-btn vt-btn-primary vt-btn-sm"><?php _e('View Details', 'visit-thurman'); ?></a>
                    <?php echo self::bookmark_button_shortcode(['post_id' => $post_id]); ?>
                </div>
            </div>
        </div>
        <?php
    }

    public static function user_profile_shortcode() {
        if (!is_user_logged_in()) {
            return '<div class="vt-alert vt-alert-info">' . sprintf(
                __('Please <a href="%s">log in</a> to view your profile.', 'visit-thurman'),
                esc_url(wp_login_url(get_permalink()))
            ) . '</div>';
        }
        
        $user = wp_get_current_user();
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        ob_start();
        ?>
        <div class="vt-profile-wrapper">
            <header class="vt-profile-header">
                <div class="vt-container vt-flex vt-items-center vt-gap-3">
                    <?php echo get_avatar($user->ID, 120, '', '', ['class' => 'vt-profile-avatar']); ?>
                    <div>
                        <h1 class="vt-h1" style="color:white;"><?php echo esc_html($user->display_name); ?></h1>
                    </div>
                </div>
            </header>

            <div class="vt-container">
                <nav class="vt-profile-tabs">
                    <a href="?tab=overview" class="vt-profile-tab <?php if($active_tab === 'overview') echo 'is-active'; ?>"><?php _e('Overview', 'visit-thurman'); ?></a>
                    <a href="?tab=bookmarks" class="vt-profile-tab <?php if($active_tab === 'bookmarks') echo 'is-active'; ?>"><?php _e('Bookmarks', 'visit-thurman'); ?></a>
                    <a href="?tab=settings" class="vt-profile-tab <?php if($active_tab === 'settings') echo 'is-active'; ?>"><?php _e('Settings', 'visit-thurman'); ?></a>
                </nav>
                
                <div id="overview" class="vt-profile-tab-content <?php if($active_tab === 'overview') echo 'is-active'; ?>">
                    <h2 class="vt-h2"><?php _e('Your Listings', 'visit-thurman'); ?></h2>
                    <p><?php _e('Your claimed and created listings will appear here.', 'visit-thurman'); ?></p>
                </div>
                <div id="bookmarks" class="vt-profile-tab-content <?php if($active_tab === 'bookmarks') echo 'is-active'; ?>">
                     <h2 class="vt-h2"><?php _e('Your Bookmarks', 'visit-thurman'); ?></h2>
                    <p><?php _e('Your saved listings will appear here.', 'visit-thurman'); ?></p>
                </div>
                <div id="settings" class="vt-profile-tab-content <?php if($active_tab === 'settings') echo 'is-active'; ?>">
                     <h2 class="vt-h2"><?php _e('Profile Settings', 'visit-thurman'); ?></h2>
                     <p><?php printf(__('You can edit your profile on the <a href="%s">WordPress profile page</a>.', 'visit-thurman'), esc_url(get_edit_profile_url())); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public static function bookmark_button_shortcode($atts) {
        if (!is_user_logged_in() || !class_exists('VT_Bookmarks')) return '';

        $atts = shortcode_atts(['post_id' => get_the_ID()], $atts);
        $post_id = intval($atts['post_id']);
        $is_bookmarked = VT_Bookmarks::user_has_bookmark($post_id);
        
        $class = 'vt-bookmark-btn' . ($is_bookmarked ? ' is-bookmarked' : '');
        $title = $is_bookmarked ? __('Remove bookmark', 'visit-thurman') : __('Add bookmark', 'visit-thurman');

        return sprintf(
            '<button class="%s" data-post-id="%d" title="%s"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg></button>',
            esc_attr($class),
            esc_attr($post_id),
            esc_attr($title)
        );
    }

    public static function next_events_shortcode($atts) {
        $atts = shortcode_atts(['limit' => 3], $atts);
        $args = [
            'post_type' => VT_Events::POST_TYPE,
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_vt_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => '_vt_start_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ];
        $query = new WP_Query($args);
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="vt-grid vt-grid-1">';
            while ($query->have_posts()) {
                $query->the_post();
                self::render_card(get_the_ID());
            }
            echo '</div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function upcoming_events_shortcode($atts) {
        $atts = shortcode_atts(['category' => '', 'limit' => 6], $atts);
        $tax_query = [];
        if (!empty($atts['category'])) {
            $tax_query[] = [
                'taxonomy' => 'vt_event_category',
                'field' => 'slug',
                'terms' => sanitize_title($atts['category'])
            ];
        }

        $args = [
            'post_type' => VT_Events::POST_TYPE,
            'posts_per_page' => intval($atts['limit']),
            'meta_key' => '_vt_start_date',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'tax_query' => $tax_query,
            'meta_query' => [
                [
                    'key' => '_vt_start_date',
                    'value' => current_time('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ]
            ]
        ];

        $query = new WP_Query($args);
        ob_start();
        if ($query->have_posts()) {
            echo '<div class="vt-grid vt-grid-3">';
            while ($query->have_posts()) {
                $query->the_post();
                self::render_card(get_the_ID());
            }
            echo '</div>';
        }
        wp_reset_postdata();
        return ob_get_clean();
    }

    public static function user_dashboard_shortcode() { 
        return '<div class="vt-alert vt-alert-info">' . __('User dashboard is under construction.', 'visit-thurman') . '</div>'; 
    }
    public static function claim_listing_shortcode() { 
        return '<div class="vt-alert vt-alert-info">' . __('Claim listing functionality is under construction.', 'visit-thurman') . '</div>'; 
    }
}
