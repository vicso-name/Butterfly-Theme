<?php
if (have_rows('content_flexible')):
    while (have_rows('content_flexible')): the_row();
        $page_title = get_the_title();
        
        switch (get_row_layout()) {
            case 'hero_section':
                get_template_part('template-parts/blocks/front-page-builder/flexible-sections/hero-section');
                break;
            
            case 'cases_section':
                get_template_part('template-parts/blocks/front-page-builder/flexible-sections/cases-section');
                break;

            case 'about_section':
                get_template_part('template-parts/blocks/front-page-builder/flexible-sections/about-section');
                break;
        }
    endwhile;
else:
    echo '<p>No section have been added yet</p>';
endif;
?>