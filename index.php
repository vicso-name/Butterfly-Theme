<?php 
// Redirect to front page if a static front page is set
if (is_front_page() && !is_home()) {
    wp_safe_redirect(get_permalink(get_option('page_on_front')));
    exit();
}

get_header(); ?>

<!-- You can add main content or a loop here if needed -->

<?php get_footer(); ?>
