<?php

// Hook to initialize theme setup
add_action('after_setup_theme', 'vicso_theme_setup');

function vicso_theme_setup()
{
    // Load theme textdomain for translations
    load_theme_textdomain('vicso', get_template_directory() . '/languages');

    // Add theme support for various features
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
    add_theme_support('customize-selective-refresh-widgets');
    add_theme_support('post-thumbnails');

    // Register custom image sizes
    add_image_size('full-thumbnail', 2000, 9999, true); // No cropping, keep ratio
    add_image_size('container-thumbnail', 1404, 9999, true);
    add_image_size('half-container-thumbnail', 640, 9999, true);

    // Register navigation menus
    register_nav_menus(array(
        'primary' => __('Primary Navigation', 'vicso'),
        'footer_menu' => __('Footer Navigation', 'vicso'),
    ));
}

// Add custom data attributes to <a> tag in menu
add_filter('nav_menu_link_attributes', 'vicso_add_menu_data_attributes', 10, 3);

function vicso_add_menu_data_attributes($atts, $item, $args)
{
    $data_target = get_field('data_target', $item); // ACF custom field for menu item
    if ($data_target) {
        $atts['data-target'] = esc_attr($data_target);
    }
    return $atts;
}

// Enqueue scripts and styles
require_once get_template_directory() . '/inc/enqueue.php';

// Load additional theme functions
require_once get_template_directory() . '/inc/theme_function.php';

// Load custom post type definitions
require_once get_template_directory() . '/inc/custom_post_type.php';

