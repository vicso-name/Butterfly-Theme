<?php
    get_header();
?>

<?php if (have_posts()) : ?>
        <?php 
        // Loop through posts if available
        while (have_posts()) : the_post(); ?>
            <section class="page-content">
                <div class="container">
                    <div class="page-content__holder">
                        <?php 
                            the_content(); 
                        ?>
                    </div>
                </div>
            </section>
        <?php endwhile; ?>
<?php endif; ?>

<?php get_footer(); ?>
