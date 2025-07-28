<?php
/**
 * Import/Export handler for Visit Thurman plugin
 * 
 * @package VisitThurman
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VT_Import_Export Class
 */
class VT_Import_Export {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Handle exports
        add_action('admin_init', array($this, 'handle_export'));
        
        // Handle imports
        add_action('admin_init', array($this, 'handle_import'));
        
        // AJAX handlers
        add_action('wp_ajax_vt_import_preview', array($this, 'ajax_import_preview'));
        add_action('wp_ajax_vt_process_import', array($this, 'ajax_process_import'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('Visit Thurman Import/Export', 'visit-thurman'),
            __('VT Import/Export', 'visit-thurman'),
            'manage_options',
            'vt-import-export',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Visit Thurman Import/Export', 'visit-thurman'); ?></h1>
            
            <?php
            if (isset($_GET['imported'])) {
                echo '<div class="notice notice-success"><p>' . 
                     sprintf(__('Successfully imported %d items.', 'visit-thurman'), intval($_GET['imported'])) . 
                     '</p></div>';
            }
            ?>
            
            <div class="vt-admin-tabs">
                <h2 class="nav-tab-wrapper">
                    <a href="#export" class="nav-tab nav-tab-active" data-tab="export">
                        <?php _e('Export', 'visit-thurman'); ?>
                    </a>
                    <a href="#import" class="nav-tab" data-tab="import">
                        <?php _e('Import', 'visit-thurman'); ?>
                    </a>
                </h2>
                
                <div id="export" class="vt-tab-content" style="display: block;">
                    <?php $this->render_export_section(); ?>
                </div>
                
                <div id="import" class="vt-tab-content" style="display: none;">
                    <?php $this->render_import_section(); ?>
                </div>
            </div>
        </div>
        
        <style>
            .vt-admin-tabs { margin-top: 20px; }
            .vt-tab-content { background: white; padding: 20px; border: 1px solid #ccc; }
            .vt-export-section { margin-bottom: 30px; }
            .vt-export-section h3 { margin-top: 0; }
            .vt-button-group { display: flex; gap: 10px; margin-top: 10px; }
            .vt-import-preview { margin-top: 20px; }
            .vt-import-preview table { width: 100%; }
            .vt-import-preview th { text-align: left; }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');
                
                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                $('.vt-tab-content').hide();
                $('#' + tab).show();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Render export section
     */
    private function render_export_section() {
        $post_types = array(
            VT_Events::POST_TYPE => __('Events', 'visit-thurman'),
            VT_Businesses::POST_TYPE => __('Businesses', 'visit-thurman'),
            VT_Accommodations::POST_TYPE => __('Accommodations', 'visit-thurman'),
            VT_TCA_Members::POST_TYPE => __('TCA Members', 'visit-thurman'),
        );
        
        foreach ($post_types as $post_type => $label) :
            $count = wp_count_posts($post_type)->publish;
            ?>
            <div class="vt-export-section">
                <h3><?php echo esc_html($label); ?></h3>
                <p><?php printf(__('Export %d published %s', 'visit-thurman'), $count, strtolower($label)); ?></p>
                
                <div class="vt-button-group">
                    <a href="<?php echo $this->get_export_url($post_type, 'csv'); ?>" 
                       class="button button-primary">
                        <?php _e('Export as CSV', 'visit-thurman'); ?>
                    </a>
                    <a href="<?php echo $this->get_export_url($post_type, 'json'); ?>" 
                       class="button">
                        <?php _e('Export as JSON', 'visit-thurman'); ?>
                    </a>
                </div>
            </div>
            <?php
        endforeach;
    }
    
    /**
     * Render import section
     */
    private function render_import_section() {
        ?>
        <form method="post" enctype="multipart/form-data" id="vt-import-form">
            <?php wp_nonce_field('vt_import', 'vt_import_nonce'); ?>
            
            <h3><?php _e('Import Data', 'visit-thurman'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="post_type"><?php _e('Post Type', 'visit-thurman'); ?></label>
                    </th>
                    <td>
                        <select name="post_type" id="post_type" required>
                            <option value=""><?php _e('Select post type...', 'visit-thurman'); ?></option>
                            <option value="<?php echo VT_Events::POST_TYPE; ?>"><?php _e('Events', 'visit-thurman'); ?></option>
                            <option value="<?php echo VT_Businesses::POST_TYPE; ?>"><?php _e('Businesses', 'visit-thurman'); ?></option>
                            <option value="<?php echo VT_Accommodations::POST_TYPE; ?>"><?php _e('Accommodations', 'visit-thurman'); ?></option>
                            <option value="<?php echo VT_TCA_Members::POST_TYPE; ?>"><?php _e('TCA Members', 'visit-thurman'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="import_file"><?php _e('File', 'visit-thurman'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="import_file" id="import_file" accept=".csv,.json" required>
                        <p class="description"><?php _e('Select a CSV or JSON file to import.', 'visit-thurman'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="update_existing"><?php _e('Update Existing', 'visit-thurman'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="update_existing" id="update_existing" value="1">
                            <?php _e('Update existing posts if they match', 'visit-thurman'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="vt_import" class="button button-primary">
                    <?php _e('Upload and Preview', 'visit-thurman'); ?>
                </button>
            </p>
        </form>
        
        <div id="vt-import-preview" class="vt-import-preview" style="display: none;">
            <!-- Preview content will be loaded here -->
        </div>
        <?php
    }
    
    /**
     * Get export URL
     */
    private function get_export_url($post_type, $format) {
        return wp_nonce_url(
            add_query_arg(array(
                'vt_export' => 1,
                'post_type' => $post_type,
                'format' => $format,
            ), admin_url('tools.php?page=vt-import-export')),
            'vt_export_' . $post_type . '_' . $format
        );
    }
    
    /**
     * Handle export
     */
    public function handle_export() {
        if (!isset($_GET['vt_export']) || !isset($_GET['post_type']) || !isset($_GET['format'])) {
            return;
        }
        
        $post_type = sanitize_text_field($_GET['post_type']);
        $format = sanitize_text_field($_GET['format']);
        
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'vt_export_' . $post_type . '_' . $format)) {
            wp_die(__('Invalid nonce', 'visit-thurman'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'visit-thurman'));
        }
        
        // Get posts
        $posts = get_posts(array(
            'post_type' => $post_type,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ));
        
        if ($format === 'csv') {
            $this->export_csv($posts, $post_type);
        } else {
            $this->export_json($posts, $post_type);
        }
        
        exit;
    }
    
    /**
     * Export as CSV
     */
    private function export_csv($posts, $post_type) {
        $filename = 'vt-' . $post_type . '-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Get headers based on post type
        $headers = $this->get_csv_headers($post_type);
        fputcsv($output, $headers);
        
        // Export data
        foreach ($posts as $post) {
            $row = $this->get_csv_row($post, $post_type);
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    /**
     * Export as JSON
     */
    private function export_json($posts, $post_type) {
        $filename = 'vt-' . $post_type . '-' . date('Y-m-d') . '.json';
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        
        $data = array(
            'post_type' => $post_type,
            'export_date' => current_time('mysql'),
            'export_count' => count($posts),
            'posts' => array(),
        );
        
        foreach ($posts as $post) {
            $data['posts'][] = $this->get_json_data($post, $post_type);
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Get CSV headers
     */
    private function get_csv_headers($post_type) {
        $headers = array('ID', 'Title', 'Content', 'Excerpt', 'Status', 'Author', 'Date', 'Featured Image');
        
        switch ($post_type) {
            case VT_Events::POST_TYPE:
                $headers = array_merge($headers, array(
                    'Start Date', 'End Date', 'Time', 'Location Name', 'Address',
                    'Organizer', 'Venue', 'Website', 'Categories', 'Tags'
                ));
                break;
                
            case VT_Businesses::POST_TYPE:
                $headers = array_merge($headers, array(
                    'Services', 'Website', 'Phone', 'Email', 'Address', 'Hours',
                    'Facebook', 'Instagram', 'Twitter', 'LinkedIn', 'TikTok',
                    'Categories', 'Tags'
                ));
                break;
                
            case VT_Accommodations::POST_TYPE:
                $headers = array_merge($headers, array(
                    'Website', 'Address', 'Categories', 'Tags'
                ));
                break;
                
            case VT_TCA_Members::POST_TYPE:
                $headers = array_merge($headers, array(
                    'Role', 'Business Affiliation', 'Website', 'Phone', 'Email', 'Address',
                    'Facebook', 'Instagram', 'Twitter', 'LinkedIn', 'TikTok',
                    'Categories', 'Tags'
                ));
                break;
        }
        
        return $headers;
    }
    
    /**
     * Get CSV row
     */
    private function get_csv_row($post, $post_type) {
        $row = array(
            $post->ID,
            $post->post_title,
            $post->post_content,
            $post->post_excerpt,
            $post->post_status,
            get_the_author_meta('display_name', $post->post_author),
            $post->post_date,
            get_the_post_thumbnail_url($post->ID, 'full'),
        );
        
        switch ($post_type) {
            case VT_Events::POST_TYPE:
                $row = array_merge($row, array(
                    get_post_meta($post->ID, '_vt_start_date', true),
                    get_post_meta($post->ID, '_vt_end_date', true),
                    get_post_meta($post->ID, '_vt_time', true),
                    get_post_meta($post->ID, '_vt_location_name', true),
                    get_post_meta($post->ID, '_vt_address', true),
                    get_post_meta($post->ID, '_vt_organizer_id', true),
                    get_post_meta($post->ID, '_vt_venue_id', true),
                    get_post_meta($post->ID, '_vt_website', true),
                    $this->get_terms_string($post->ID, 'vt_event_category'),
                    $this->get_terms_string($post->ID, 'vt_event_tag'),
                ));
                break;
                
            case VT_Businesses::POST_TYPE:
                $row = array_merge($row, array(
                    get_post_meta($post->ID, '_vt_services', true),
                    get_post_meta($post->ID, '_vt_website', true),
                    get_post_meta($post->ID, '_vt_phone', true),
                    get_post_meta($post->ID, '_vt_email', true),
                    get_post_meta($post->ID, '_vt_address', true),
                    get_post_meta($post->ID, '_vt_hours', true),
                    get_post_meta($post->ID, '_vt_facebook', true),
                    get_post_meta($post->ID, '_vt_instagram', true),
                    get_post_meta($post->ID, '_vt_twitter', true),
                    get_post_meta($post->ID, '_vt_linkedin', true),
                    get_post_meta($post->ID, '_vt_tiktok', true),
                    $this->get_terms_string($post->ID, 'vt_business_category'),
                    $this->get_terms_string($post->ID, 'vt_business_tag'),
                ));
                break;
                
            case VT_Accommodations::POST_TYPE:
                $row = array_merge($row, array(
                    get_post_meta($post->ID, '_vt_website', true),
                    get_post_meta($post->ID, '_vt_address', true),
                    $this->get_terms_string($post->ID, 'vt_accommodation_category'),
                    $this->get_terms_string($post->ID, 'vt_accommodation_tag'),
                ));
                break;
                
            case VT_TCA_Members::POST_TYPE:
                $row = array_merge($row, array(
                    get_post_meta($post->ID, '_vt_role', true),
                    get_post_meta($post->ID, '_vt_business_affiliation', true),
                    get_post_meta($post->ID, '_vt_website', true),
                    get_post_meta($post->ID, '_vt_phone', true),
                    get_post_meta($post->ID, '_vt_email', true),
                    get_post_meta($post->ID, '_vt_address', true),
                    get_post_meta($post->ID, '_vt_facebook', true),
                    get_post_meta($post->ID, '_vt_instagram', true),
                    get_post_meta($post->ID, '_vt_twitter', true),
                    get_post_meta($post->ID, '_vt_linkedin', true),
                    get_post_meta($post->ID, '_vt_tiktok', true),
                    $this->get_terms_string($post->ID, 'vt_tca_member_category'),
                    $this->get_terms_string($post->ID, 'vt_tca_member_tag'),
                ));
                break;
        }
        
        return $row;
    }
    
    /**
     * Get JSON data
     */
    private function get_json_data($post, $post_type) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'status' => $post->post_status,
            'author' => array(
                'id' => $post->post_author,
                'name' => get_the_author_meta('display_name', $post->post_author),
                'email' => get_the_author_meta('user_email', $post->post_author),
            ),
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'featured_image' => get_the_post_thumbnail_url($post->ID, 'full'),
            'meta' => array(),
        );
        
        // Get all meta data
        $meta_data = get_post_meta($post->ID);
        foreach ($meta_data as $key => $value) {
            if (strpos($key, '_vt_') === 0) {
                $data['meta'][$key] = maybe_unserialize($value[0]);
            }
        }
        
        // Get taxonomies
        $taxonomies = get_object_taxonomies($post_type);
        foreach ($taxonomies as $taxonomy) {
            $terms = wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'all'));
            if (!is_wp_error($terms) && !empty($terms)) {
                $data['taxonomies'][$taxonomy] = array_map(function($term) {
                    return array(
                        'id' => $term->term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                    );
                }, $terms);
            }
        }
        
        return $data;
    }
    
    /**
     * Get terms as string
     */
    private function get_terms_string($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'names'));
        return !is_wp_error($terms) ? implode(', ', $terms) : '';
    }
    
    /**
     * Handle import
     */
    public function handle_import() {
        if (!isset($_POST['vt_import']) || !isset($_FILES['import_file'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vt_import_nonce'], 'vt_import')) {
            wp_die(__('Invalid nonce', 'visit-thurman'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'visit-thurman'));
        }
        
        $post_type = sanitize_text_field($_POST['post_type']);
        $update_existing = isset($_POST['update_existing']);
        
        // Handle file upload
        $uploaded_file = $_FILES['import_file'];
        $file_type = wp_check_filetype($uploaded_file['name']);
        
        if (!in_array($file_type['ext'], array('csv', 'json'))) {
            wp_die(__('Invalid file type. Please upload a CSV or JSON file.', 'visit-thurman'));
        }
        
        // Process import
        if ($file_type['ext'] === 'csv') {
            $imported = $this->import_csv($uploaded_file['tmp_name'], $post_type, $update_existing);
        } else {
            $imported = $this->import_json($uploaded_file['tmp_name'], $post_type, $update_existing);
        }
        
        // Redirect with success message
        wp_redirect(add_query_arg(array(
            'page' => 'vt-import-export',
            'imported' => $imported,
        ), admin_url('tools.php')));
        exit;
    }
    
    /**
     * Import CSV
     */
    private function import_csv($file_path, $post_type, $update_existing) {
        $imported = 0;
        
        if (($handle = fopen($file_path, 'r')) !== false) {
            // Skip BOM if present
            $bom = fread($handle, 3);
            if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
                rewind($handle);
            }
            
            // Get headers
            $headers = fgetcsv($handle);
            
            // Process rows
            while (($data = fgetcsv($handle)) !== false) {
                $post_data = array_combine($headers, $data);
                
                if ($this->import_post($post_data, $post_type, $update_existing)) {
                    $imported++;
                }
            }
            
            fclose($handle);
        }
        
        return $imported;
    }
    
    /**
     * Import JSON
     */
    private function import_json($file_path, $post_type, $update_existing) {
        $imported = 0;
        
        $json_data = file_get_contents($file_path);
        $data = json_decode($json_data, true);
        
        if (isset($data['posts']) && is_array($data['posts'])) {
            foreach ($data['posts'] as $post_data) {
                if ($this->import_post($post_data, $post_type, $update_existing)) {
                    $imported++;
                }
            }
        }
        
        return $imported;
    }
    
    /**
     * Import single post
     */
    private function import_post($data, $post_type, $update_existing) {
        // Prepare post data
        $post_args = array(
            'post_type' => $post_type,
            'post_title' => isset($data['Title']) ? $data['Title'] : $data['title'],
            'post_content' => isset($data['Content']) ? $data['Content'] : $data['content'],
            'post_excerpt' => isset($data['Excerpt']) ? $data['Excerpt'] : $data['excerpt'],
            'post_status' => 'publish',
        );
        
        // Check if updating existing
        if ($update_existing && isset($data['ID'])) {
            $existing = get_post($data['ID']);
            if ($existing && $existing->post_type === $post_type) {
                $post_args['ID'] = $data['ID'];
            }
        }
        
        // Insert or update post
        $post_id = wp_insert_post($post_args);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Import meta data
        $this->import_post_meta($post_id, $data, $post_type);

        // Import taxonomies
        $this->import_post_taxonomies($post_id, $data, $post_type);

        // Set featured image if provided
        $image_url = $data['Featured Image'] ?? $data['featured_image'] ?? '';
        if ($image_url) {
            include_once ABSPATH . 'wp-admin/includes/file.php';
            include_once ABSPATH . 'wp-admin/includes/media.php';
            include_once ABSPATH . 'wp-admin/includes/image.php';
            $image_id = media_sideload_image(esc_url_raw($image_url), $post_id, null, 'id');
            if (!is_wp_error($image_id)) {
                set_post_thumbnail($post_id, $image_id);
            }
        }

        return true;
    }
    
    /**
     * Import post meta
     */
    private function import_post_meta($post_id, $data, $post_type) {
        // Map CSV fields to meta keys based on post type
        switch ($post_type) {
            case VT_Events::POST_TYPE:
                $meta_map = array(
                    'Start Date'   => '_vt_start_date',
                    'End Date'     => '_vt_end_date',
                    'Time'         => '_vt_time',
                    'Location Name'=> '_vt_location_name',
                    'Address'      => '_vt_address',
                    'Organizer'    => '_vt_organizer_id',
                    'Venue'        => '_vt_venue_id',
                    'Website'      => '_vt_website',
                );
                break;

            case VT_Businesses::POST_TYPE:
                $meta_map = array(
                    'Services'  => '_vt_services',
                    'Website'   => '_vt_website',
                    'Phone'     => '_vt_phone',
                    'Email'     => '_vt_email',
                    'Address'   => '_vt_address',
                    'Hours'     => '_vt_hours',
                    'Facebook'  => '_vt_facebook',
                    'Instagram' => '_vt_instagram',
                    'Twitter'   => '_vt_twitter',
                    'LinkedIn'  => '_vt_linkedin',
                    'TikTok'    => '_vt_tiktok',
                );
                break;

            case VT_Accommodations::POST_TYPE:
                $meta_map = array(
                    'Website' => '_vt_website',
                    'Address' => '_vt_address',
                );
                break;

            case VT_TCA_Members::POST_TYPE:
                $meta_map = array(
                    'Role'               => '_vt_role',
                    'Business Affiliation'=> '_vt_business_affiliation',
                    'Website'            => '_vt_website',
                    'Phone'              => '_vt_phone',
                    'Email'              => '_vt_email',
                    'Address'            => '_vt_address',
                    'Facebook'           => '_vt_facebook',
                    'Instagram'          => '_vt_instagram',
                    'Twitter'            => '_vt_twitter',
                    'LinkedIn'           => '_vt_linkedin',
                    'TikTok'             => '_vt_tiktok',
                );
                break;
        }
        
        // Apply meta mappings
        if (isset($meta_map)) {
            foreach ($meta_map as $csv_field => $meta_key) {
                if (isset($data[$csv_field]) && !empty($data[$csv_field])) {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($data[$csv_field]));
                }
            }
        }
        
        // Handle JSON meta format
        if (isset($data['meta']) && is_array($data['meta'])) {
            foreach ($data['meta'] as $key => $value) {
                update_post_meta($post_id, $key, $value);
            }
        }
    }
    
    /**
     * Import post taxonomies
     */
    private function import_post_taxonomies($post_id, $data, $post_type) {
        // Handle CSV format
        $taxonomy_map = array(
            VT_Events::POST_TYPE => array(
                'Categories' => 'vt_event_category',
                'Tags'       => 'vt_event_tag',
            ),
            VT_Businesses::POST_TYPE => array(
                'Categories' => 'vt_business_category',
                'Tags'       => 'vt_business_tag',
            ),
            VT_Accommodations::POST_TYPE => array(
                'Categories' => 'vt_accommodation_category',
                'Tags'       => 'vt_accommodation_tag',
            ),
            VT_TCA_Members::POST_TYPE => array(
                'Categories' => 'vt_tca_member_category',
                'Tags'       => 'vt_tca_member_tag',
            ),
        );
        
        if (isset($taxonomy_map[$post_type])) {
            foreach ($taxonomy_map[$post_type] as $csv_field => $taxonomy) {
                if (isset($data[$csv_field]) && !empty($data[$csv_field])) {
                    $terms = array_map('trim', explode(',', $data[$csv_field]));
                    wp_set_object_terms($post_id, $terms, $taxonomy);
                }
            }
        }
        
        // Handle JSON format
        if (isset($data['taxonomies']) && is_array($data['taxonomies'])) {
            foreach ($data['taxonomies'] as $taxonomy => $terms) {
                $term_names = array_column($terms, 'name');
                wp_set_object_terms($post_id, $term_names, $taxonomy);
            }
        }
    }
}