<?php
/**
 * Template handler for overriding theme files.
 * This is crucial for compatibility with builders like Breakdance that disable theme templates.
 * @package VisitThurman
 */

if (!defined('ABSPATH')) {
    exit;
}

class VT_Templates {
    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('single_template', array($this, 'load_single_template'));
        add_filter('archive_template', array($this, 'load_archive_template'));
    }

    public function load_single_template($template) {
        $post_types = [
            VT_Events::POST_TYPE, 
            VT_Businesses::POST_TYPE, 
            VT_Accommodations::POST_TYPE, 
            VT_TCA_Members::POST_TYPE
        ];

        if (is_singular($post_types)) {
            // By loading the header and footer manually and then returning a blank template,
            // we ensure our content is displayed without interference from the theme's single.php,
            // which might be disabled or altered by a builder.
            get_header();
            echo '<div id="primary" class="content-area vt-container">';
            echo '<main id="main" class="site-main">';
            
            while (have_posts()) : the_post();
                // We can create a more detailed template part here if needed
                echo '<article id="post-' . get_the_ID() . '" ' . get_post_class() . '>';
                echo '<header class="entry-header"><h1 class="entry-title vt-h1">' . get_the_title() . '</h1></header>';
                if (has_post_thumbnail()) {
                    echo '<div class="post-thumbnail">' . get_the_post_thumbnail(get_the_ID(), 'large') . '</div>';
                }
                echo '<div class="entry-content">';
                the_content();
                echo '</div>';
                echo '</article>';
            endwhile;

            echo '</main>';
            echo '</div>';
            get_footer();
            
            // Return our blank template to prevent the theme from loading its own file.
            return VT_PLUGIN_PATH . 'templates/blank.php'; 
        }

        return $template;
    }

    public function load_archive_template($template) {
        $post_types = [
            VT_Events::POST_TYPE, 
            VT_Businesses::POST_TYPE, 
            VT_Accommodations::POST_TYPE, 
            VT_TCA_Members::POST_TYPE
        ];

        if (is_post_type_archive($post_types)) {
            get_header();
            echo '<div id="primary" class="content-area vt-container">';
            echo '<main id="main" class="site-main">';

            echo '<header class="page-header"><h1 class="page-title vt-h1">' . post_type_archive_title('', false) . '</h1></header>';
            
            // Use the corresponding shortcode to display the archive grid.
            $post_type_slug = get_query_var('post_type');
            $shortcode_tag = 'vt_' . str_replace('vt_', '', $post_type_slug) . 's'; // e.g., vt_events
            echo do_shortcode("[$shortcode_tag]");

            echo '</main>';
            echo '</div>';
            get_footer();

            return VT_PLUGIN_PATH . 'templates/blank.php';
        }

        return $template;
    }
}
