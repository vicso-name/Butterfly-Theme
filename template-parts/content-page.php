<?php

/**
 * Part for displaying page content
 **/

?>

<?php

function display_flexible_content($layout) {
    switch ($layout) {
        case 'page_content':
            echo '<h1>' . get_the_title() . '</h1>';
            the_content();
            break;
        
        case 'hero_section':
            get_template_part('template-parts/flex/section-hero');
            break;

        case 'about_section':
            get_template_part('template-parts/flex/section-about');
            break;

        case 'cases_section':
            get_template_part('template-parts/flex/section-cases');
            break;

        default:
            // Optionally handle cases where the layout does not match known types
            break;
    }
}

if (have_rows('content_flexible')) :
    while (have_rows('content_flexible')) : the_row();
        $layout = get_row_layout();
        display_flexible_content($layout);
    endwhile;
else :
    // Optionally handle the case where no content is found
    echo '<p>No content available.</p>';
endif;

?>
