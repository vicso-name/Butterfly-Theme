<?php
// Enable shortcodes in widgets
add_filter('widget_text', 'do_shortcode', 11);

// Remove WordPress version from head for security
remove_action('wp_head', 'wp_generator');

// Remove emoji scripts and styles for performance optimization
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('admin_print_scripts', 'print_emoji_detection_script');
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('admin_print_styles', 'print_emoji_styles');

// Replace default WordPress menu classes with custom classes
function change_menu_classes($classes) {
    $classes = str_replace(['current-menu-item', 'current-menu-parent', 'current_page_item'], 'active', $classes);
    return $classes;
}
add_filter('nav_menu_css_class', 'change_menu_classes');
add_filter('page_css_class', 'change_menu_classes');

// Automatically add title as alt text if alt is missing
function add_title_to_empty_alt($response) {
    if (empty($response['alt'])) {
        $response['alt'] = sanitize_text_field($response['title']);
    }
    return $response;
}
add_filter('wp_prepare_attachment_for_js', 'add_title_to_empty_alt');

// Disable automatic paragraph wrapping in Contact Form 7
add_filter('wpcf7_autop_or_not', '__return_false');

// Fallback functions if ACF (Advanced Custom Fields) is not available
if (!class_exists('acf') && !is_admin()) {
    function get_field_reference() { return ''; }
    function get_field_objects() { return false; }
    function get_fields() { return false; }
    function get_field() { return false; }
    function get_field_object() { return false; }
    function the_field() {}
    function have_rows() { return false; }
    function the_row() {}
    function reset_rows() {}
    function has_sub_field() { return false; }
    function get_sub_field() { return false; }
    function the_sub_field() {}
    function get_sub_field_object() { return false; }
    function acf_get_child_field_from_parent_field() { return false; }
    function register_field_group() {}
    function get_row_layout() { return false; }
    function acf_form_head() {}
    function acf_form() {}
    function update_field() { return false; }
    function delete_field() {}
    function create_field() {}
    function reset_the_repeater_field() {}
    function the_repeater_field() { return false; }
    function the_flexible_field() { return false; }
    function acf_filter_post_id($post_id) { return $post_id; }
}

// Add Open Graph meta tags for social sharing
function add_opengraph_namespace($output) {
    return $output . ' xmlns="http://www.w3.org/1999/xhtml" prefix="og: http://ogp.me/ns# fb: http://www.facebook.com/2008/fbml"';
}
add_filter('language_attributes', 'add_opengraph_namespace');

function add_opengraph_meta_tags() {
    global $post;
    
    if (is_single() || is_page()) {
        $img_src = '';
        $image_share = get_field('image_share', $post->ID);
        
        if ($image_share) {
            $img_src = $image_share['url'];
            $img_width = $image_share['width'];
            $img_height = $image_share['height'];
        } elseif (has_post_thumbnail($post->ID)) {
            $img_data = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
            $img_src = $img_data[0];
            $img_width = $img_data[1];
            $img_height = $img_data[2];
        }
        
        $excerpt = $post->post_content ? wp_trim_words(strip_shortcodes(strip_tags($post->post_content)), 55) : get_bloginfo('description');
        
        echo '<meta property="og:type" content="article"/>';
        echo '<meta property="og:title" content="' . get_the_title() . '"/>';
        if ($img_src) {
            echo '<meta property="og:image" content="' . esc_url($img_src) . '"/>';
            echo '<meta property="og:image:width" content="' . esc_attr($img_width) . '"/>';
            echo '<meta property="og:image:height" content="' . esc_attr($img_height) . '"/>';
        }
        echo '<meta property="og:description" content="' . esc_attr($excerpt) . '"/>';
        echo '<meta property="og:url" content="' . esc_url(get_the_permalink()) . '"/>';
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo()) . '"/>';
        echo '<meta property="article:published_time" content="' . esc_attr(date('c', strtotime($post->post_date_gmt))) . '"/>';
        echo '<meta property="article:modified_time" content="' . esc_attr(date('c', strtotime($post->post_date_gmt))) . '"/>';

        echo '<meta name="twitter:card" content="summary_large_image"/>';
        echo '<meta name="twitter:site" content="@' . esc_attr(get_bloginfo('name')) . '"/>';
        echo '<meta name="twitter:text:title" content="' . esc_attr(get_the_title()) . '"/>';
        echo '<meta name="twitter:url" content="' . esc_url(get_the_permalink()) . '"/>';
        echo '<meta name="twitter:text:description" content="' . esc_attr($excerpt) . '"/>';
        if ($img_src) {
            echo '<meta name="twitter:image" content="' . esc_url($img_src) . '"/>';
        }
    }
}
add_action('wp_head', 'add_opengraph_meta_tags', 5);

// Add ACF option pages
if (function_exists('acf_add_options_page')) {
    acf_add_options_page(array(
        'page_title'    => 'Theme Options',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-general-settings',
        'capability'    => 'edit_posts',
    ));

    acf_add_options_sub_page(array(
        'page_title'    => 'Projects Settings',
        'menu_title'    => 'Projects Settings',
        'parent_slug'   => 'edit.php?post_type=project',
    ));
}

// Add custom body classes
add_filter('body_class', 'add_custom_body_class');
function add_custom_body_class($classes) {
    if (is_post_type_archive('project') || is_tax('project_category') || is_singular('project')) {
        $classes[] = 'color-theme-black';
    }
    return $classes;
}

// Get page ID by template
function get_template_page_id($template_name = '') {
    if (!$template_name) {
        return '';
    }
    
    $pages = new WP_Query(array(
        'post_type'     => 'page',
        'fields'        => 'ids',
        'nopaging'      => true,
        'meta_key'      => '_wp_page_template',
        'meta_value'    => $template_name,
    ));
    
    return !empty($pages->posts[0]) ? $pages->posts[0] : '';
}

// Customize project post type archive query
add_action('pre_get_posts', 'customize_project_archive_query');
function customize_project_archive_query($query) {
    if ($query->is_main_query() && !is_admin() && $query->is_post_type_archive('project')) {
        $query->set('posts_per_page', -1);
    }
}
