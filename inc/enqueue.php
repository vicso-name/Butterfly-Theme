<?php
/**
 * Clean, predictable enqueues with early critical CSS/JS,
 * conditional Swiper, and safe versioning.
 */

defined('ABSPATH') || exit;

/* -----------------------------------------------------------
 * Helpers
 * ----------------------------------------------------------- */

/**
 * Return theme URI (child theme aware).
 */
function smplfy_theme_uri(): string {
    return get_stylesheet_directory_uri();
}

/**
 * Return theme dir (child theme aware).
 */
function smplfy_theme_dir(): string {
    return get_stylesheet_directory();
}

/**
 * Smart asset version: filemtime if exists, else S_VERSION or theme version.
 */
function smplfy_asset_ver(string $rel): string {
    $abs = smplfy_theme_dir() . '/' . ltrim($rel, '/');
    if (file_exists($abs)) {
        return (string) filemtime($abs);
    }
    if (defined('S_VERSION')) return (string) S_VERSION;
    return (string) wp_get_theme()->get('Version');
}

/**
 * Build asset URL from relative path.
 */
function smplfy_asset_url(string $rel): string {
    return smplfy_theme_uri() . '/' . ltrim($rel, '/');
}

/**
 * Should we load Swiper on this request?
 * You can override with: add_filter('smplfy_load_swiper', '__return_false');
 */
function smplfy_should_load_swiper(): bool {
    return (bool) apply_filters('smplfy_load_swiper', true);
}

/* -----------------------------------------------------------
 * Admin assets
 * ----------------------------------------------------------- */
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_style(
        'btf-admin-styles',
        smplfy_asset_url('build/css/admin-styles.min.css'),
        [],
        smplfy_asset_ver('build/css/admin-styles.min.css')
    );
});

/* -----------------------------------------------------------
 * Frontend assets
 * ----------------------------------------------------------- */
/**
 * Load styles as early as possible to reduce FOUC.
 * Priority 5 -> earlier than default 10.
 */
add_action('wp_enqueue_scripts', function () {

    // 0) Optional: load style.css only if you actually use it.
    // If your theme's design is entirely in build/css/style.min.css, you can safely skip it.
    // wp_enqueue_style('btf-style', get_stylesheet_uri(), [], smplfy_asset_ver('style.css'));
    // wp_style_add_data('btf-style', 'rtl', 'replace');

    // 1) Swiper (conditionally)
    if (smplfy_should_load_swiper()) {
        wp_enqueue_style(
            'btf-swiper-style',
            smplfy_asset_url('assets/swiper/swiper-bundle.min.css'),
            [],
            smplfy_asset_ver('assets/swiper/swiper-bundle.min.css')
        );
    }

    // 2) Main theme CSS (make it depend on swiper-style if present)
    $style_deps = [];
    if (smplfy_should_load_swiper()) $style_deps[] = 'btf-swiper-style';

    wp_enqueue_style(
        'btf-main-styles',
        smplfy_asset_url('build/css/style.min.css'),
        $style_deps,
        smplfy_asset_ver('build/css/style.min.css')
    );

    // 3) Scripts
    // Swiper JS (conditionally)
    $script_deps = [];
    if (smplfy_should_load_swiper()) {
        wp_enqueue_script(
            'btf-swiper-script',
            smplfy_asset_url('assets/swiper/swiper-bundle.min.js'),
            [],
            smplfy_asset_ver('assets/swiper/swiper-bundle.min.js'),
            true // footer
        );
        $script_deps[] = 'btf-swiper-script';
    }

    wp_enqueue_script(
        'btf-main-scripts',
        smplfy_asset_url('build/js/general.min.js'),
        $script_deps,
        smplfy_asset_ver('build/js/general.min.js'),
        true
    );

}, 5);

/* -----------------------------------------------------------
 * Editor (block editor) assets
 * ----------------------------------------------------------- */
add_action('enqueue_block_editor_assets', function () {
    // Toggler add-on disabled — fully on ACF Blocks v3's own UI
    // (Edit-in-modal/sidebar) now instead of the collapsible inline toggle.
    // WordPress 7.1 removed the only way to opt out of the always-iframed
    // block editor canvas, and this toggle only ever worked in a
    // non-iframed canvas (it reaches in from the top-level document, which
    // no longer shares any DOM with the canvas). ACF Pro 6.6+'s "Blocks
    // v3" is what actually works inside the iframe.
    if (defined('SMPLFY_DISABLE_ACF_TOGGLE') && SMPLFY_DISABLE_ACF_TOGGLE) {
        return;
    }

    // Editor JS — runs in the top-level admin document, reaches into the
    // iframed canvas itself (see admin-scripts.js), so it doesn't need
    // to be iframe-replayed.
    wp_enqueue_script(
        'btf-editor-scripts',
        smplfy_asset_url('build/js/admin-scripts.min.js'),
        ['wp-blocks', 'wp-dom-ready', 'wp-edit-post'],
        smplfy_asset_ver('build/js/admin-scripts.min.js'),
        true
    );

    // Editor CSS (.acf-block-toggle) moved to enqueue_block_assets below
    // — the toggle headers admin-scripts.js inserts live INSIDE the
    // iframe (it manipulates the iframe's own DOM), but this hook only
    // reaches the top-level document, so this file's rules never applied
    // to them there. Same registration-vs-enqueue-hook gap as everything
    // else fixed below.
});

/* -----------------------------------------------------------
 * Block editor iframe canvas assets
 * -----------------------------------------------------------
 * `enqueue_block_assets` fires for BOTH the top-level admin document AND
 * the block editor's iframe canvas — WordPress rebuilds the iframe's own
 * stylesheet/script set by replaying this action in a fresh registry (see
 * _wp_get_iframed_editor_assets() in wp-includes/block-editor.php). It
 * does NOT replay enqueue_block_editor_assets, so anything that needs to
 * reach the canvas itself has to be registered here, gated by the
 * `should_load_block_editor_scripts_and_styles` filter (forced false only
 * during the iframe-collection pass) to avoid double-loading it into the
 * top-level document too.
 * ----------------------------------------------------------- */
add_action('enqueue_block_assets', function () {
    if (apply_filters('should_load_block_editor_scripts_and_styles', true)) {
        return;
    }

    // ACF Pro's own field-editing CSS (the actual repeater UI, image/link
    // field pickers, etc.) is registered on `init` but only ever
    // *enqueued* via `admin_enqueue_scripts` — top-document-only. It's
    // registered globally by this point in the request regardless of
    // which pass we're in, so re-enqueuing by handle here is enough to
    // pull it into the iframe too. wp_style_is(..., 'registered') keeps
    // the acf-pro-* handles safe on sites running the free version of
    // ACF, where they don't exist.
    foreach (['acf-global', 'acf-input', 'acf-field-group', 'acf-pro-input', 'acf-pro-field-group'] as $acf_handle) {
        if (wp_style_is($acf_handle, 'registered')) {
            wp_enqueue_style($acf_handle);
        }
    }

    // WP core's own <a class="button"> styling (padding, border,
    // border-radius) lives in wp-includes/css/buttons.css, handle
    // `buttons`. It's registered with no dependents pulling it in
    // automatically, and the iframe replay only pulls in `wp-edit-blocks`'s
    // own dependency chain, which never reaches it either — any site using
    // ACF's iframed block editor has always rendered ACF's "Select Link"/
    // "Add Image" buttons unstyled without this.
    wp_enqueue_style('buttons');

    // Design tokens + typography + layout that every section's SCSS reads
    // via var(--...) — without this, ACF block previews in the editor
    // render with every custom property unresolved, showing up blank/
    // broken rather than merely unstyled.
    //
    // build/css/editor-preview.min.css (gulpfile.js: stylesEditorPreview)
    // is style.min.css + every section's CSS concatenated and wrapped in
    // `@scope (body) to (:where(.acf-fields))` at build time — its
    // selectors can only ever match inside the iframe body and are
    // structurally barred from matching anything inside ACF's own field-
    // editing UI.
    wp_enqueue_style(
        'btf-editor-preview-styles',
        smplfy_asset_url('build/css/editor-preview.min.css'),
        [],
        smplfy_asset_ver('build/css/editor-preview.min.css')
    );

    // Matches the canvas <body> background to the frontend's, and pins
    // custom blocks to a fixed width in the canvas — see
    // editor-canvas-background.scss's own header comment. NOT loaded via
    // add_editor_style(): combined with the always-iframed (WP 7.1+)
    // canvas on a classic (non-theme.json) theme, that mechanism caused
    // the whole iframe to spuriously remount on certain interactions.
    wp_enqueue_style(
        'btf-editor-canvas-bg-styles',
        smplfy_asset_url('build/css/editor-canvas-background.min.css'),
        [],
        smplfy_asset_ver('build/css/editor-canvas-background.min.css')
    );

    // ACF block collapse toggle (admin-scripts.js's markup).
    // Toggler add-on disabled — see enqueue_block_editor_assets above.
    if (!(defined('SMPLFY_DISABLE_ACF_TOGGLE') && SMPLFY_DISABLE_ACF_TOGGLE)) {
        wp_enqueue_style(
            'btf-editor-styles',
            smplfy_asset_url('build/css/acf-block-toggle.min.css'),
            ['wp-edit-blocks'],
            smplfy_asset_ver('build/css/acf-block-toggle.min.css')
        );
    }
});

/* -----------------------------------------------------------
 * Optional optimizations
 * ----------------------------------------------------------- */

/**
 * Remove jQuery Migrate on frontend in production (optional).
 */
add_action('wp_default_scripts', function ($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $deps = $scripts->registered['jquery']->deps;
        $scripts->registered['jquery']->deps = array_diff($deps, ['jquery-migrate']);
    }
});

/**
 * If you don't use core block library CSS (classic theme) you can dequeue it.
 * Be careful: if you rely on block styles, don't remove them.
 */
// add_action('wp_enqueue_scripts', function () {
//     wp_dequeue_style('wp-block-library');
//     wp_dequeue_style('global-styles');
// }, 100);

/**
 * Resource hints (if you use external fonts/CDNs).
 * Keep minimal to avoid unnecessary DNS work.
 */
// add_filter('wp_resource_hints', function($urls, $relation_type) {
//     if ('preconnect' === $relation_type) {
//         $urls[] = 'https://fonts.googleapis.com';
//         $urls[] = 'https://fonts.gstatic.com';
//     }
//     if ('dns-prefetch' === $relation_type) {
//         $urls[] = '//fonts.googleapis.com';
//         $urls[] = '//fonts.gstatic.com';
//     }
//     return $urls;
// }, 10, 2);
