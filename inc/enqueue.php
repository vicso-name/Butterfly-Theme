<?php

function vicso_enqueue_scripts()
{
    // Load HTML5 shim for IE 8 and below
    wp_enqueue_script(
        'vicso-html5-shiv', 
        'https://cdnjs.cloudflare.com/ajax/libs/html5shiv/r29/html5.min.js', 
        array(), 
        null
    );
    wp_script_add_data('vicso-html5-shiv', 'conditional', 'lt IE 9');

    // Enqueue stylesheets
    wp_enqueue_style(
        'vicso-styles', 
        get_stylesheet_directory_uri() . '/css/app.min.css'
    );

    wp_enqueue_style(
        'vicso-style', 
        get_stylesheet_directory_uri() . '/style.css'
    );

    wp_enqueue_script(
        'vicso-scripts', 
        get_template_directory_uri() . '/js/app.min.js', 
        array('jquery'), 
        null, 
        true
    );
}

add_action('wp_enqueue_scripts', 'vicso_enqueue_scripts');
