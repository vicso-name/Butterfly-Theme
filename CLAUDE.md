# CLAUDE.md — Butterfly Theme

## Boilerplate Usage

This theme is a **reusable boilerplate**. Clone it for each new project, run setup once, and it becomes a clean project-specific theme.

### First run (after cloning)

```bash
npm install
npm run setup
```

`npm run setup` launches `scripts/setup.mjs` which:
1. Prompts for: theme name, slug, author, author URI, theme URI, description, local dev URL
2. Replaces all boilerplate identifiers across every PHP, JS, JSON, SCSS, and MD file
3. Commits the result via `git commit -m "chore: initialize project as <name>"`
4. Deletes itself (`scripts/setup.mjs` is removed; `scripts/` is removed if empty)

**Do not run setup on an existing project** — it makes irreversible in-place replacements.

### Identifiers replaced by setup

| What | Before (boilerplate) | After (your project) |
|---|---|---|
| WordPress Theme Name | `smplfy` (in style.css Theme Name line) | your theme name |
| PHP function prefix | `smplfy_` | `{slug}_` (underscores) |
| Text domain / slug | `smplfy` | your slug |
| npm package name | `butterfly-theme` | your slug |
| Author | `Smplfy.Development` | your author |
| Author URI | `https://smplfy.eu/` | your author URI |
| Theme URI | `https://smplfy.eu/` | your theme URI |
| Description | `Description` | your description |
| BrowserSync proxy | `http://localhost/wordpress/` | your local dev URL |

### What setup does NOT touch

- `docs/` — ACF pattern reference; slug replacement would corrupt SCSS/PHP code examples
- `scripts/` — the setup script's own directory
- `node_modules/`, `build/`, `assets/`, `.git/` — always skipped

### After setup

`CLAUDE.md` itself is updated by the setup script (all `smplfy` references become the new slug). After setup, this file will reflect the actual project name.

---

## Project Overview

**Package name:** butterfly-theme (package.json) — but all PHP code uses the name **smplfy** (style.css, functions.php) and some generator comments say **Barvy Theme**. These three names refer to the same project. The canonical WordPress theme name registered in `style.css` is `smplfy`.

**Purpose:** A custom WordPress theme built around ACF Gutenberg blocks. Each page section is an independently registered ACF block with its own PHP template, SCSS file, and JS file. Intended for agency-style custom builds.

**Dependencies:**
- WordPress: tested up to 5.4 (style.css header) — likely works on newer versions
- **ACF Pro required** — blocks use `acf_register_block_type()` and `get_field()`
- Node.js: any version supporting Gulp 5 (v18+ recommended)
- PHP 5.6+ per style.css (practically WordPress 5.x requires PHP 7.4+)
- Rank Math SEO plugin assumed (breadcrumb filters in `inc/theme_function.php`)
- Contact Form 7 assumed (`wpcf7_autop_or_not` filter in `inc/theme_function.php`)

---

## Architecture

### Section-Based Block System

Each visual section on a page is an **ACF Gutenberg block**. A complete section is made of exactly four files:

| Layer | File | Output |
|---|---|---|
| PHP template | `template-parts/sections/{name}.php` | Renders block HTML |
| SCSS styles | `src/scss/sections/{name}.scss` | `build/css/sections/{name}.min.css` |
| JS behavior | `src/js/sections/{name}.js` | `build/js/sections/{name}.min.js` |
| Registration | entry in `inc/acf_blocks.php` `$blocks` array | registers with WordPress |

### File Organization

```
Butterfly-Theme/
├── build/                      ← Gulp output (gitignored)
│   ├── css/
│   │   ├── style.min.css       ← compiled from src/scss/style.scss
│   │   ├── sections/           ← one .min.css per ACF block
│   │   └── fonts.css           ← auto-generated @font-face rules
│   ├── js/
│   │   ├── general.min.js      ← compiled from src/js/general.js
│   │   └── sections/           ← one .min.js per ACF block
│   └── fonts/                  ← copied font files + fonts.css
├── src/
│   ├── scss/
│   │   ├── style.scss          ← main entry point (imports partials only)
│   │   ├── partials/           ← underscore-prefixed partials
│   │   └── sections/           ← no underscore — compiled independently
│   └── js/
│       ├── general.js          ← global JS (header toggle, smooth scroll, SVG)
│       └── sections/           ← per-block JS
├── template-parts/sections/    ← ACF block PHP templates
├── inc/
│   ├── acf_blocks.php          ← block registration + per-page asset detection
│   ├── enqueue.php             ← global CSS/JS enqueue
│   └── theme_function.php      ← theme helpers (OG tags, nav classes, etc.)
├── assets/
│   ├── swiper/                 ← manually placed Swiper vendor files (NOT from npm)
│   └── img/                    ← static image assets (login-logo.jpg etc.)
├── fonts/                      ← font source files (.woff/.woff2)
│   └── {FontName}/             ← one subfolder per typeface
├── functions.php               ← bootstraps theme, requires inc/ files
├── style.css                   ← theme declaration header only
├── header.php / footer.php     ← global wrapper
├── page.php                    ← page template
├── index.php                   ← blog/archive fallback
├── single.php                  ← single post (EMPTY — stub)
├── archive.php                 ← archive (EMPTY — stub)
├── 404.php                     ← not found (EMPTY — stub)
├── generate-sections.js        ← CLI scaffold tool
├── gulpfile.js
└── package.json
```

---

## Development Commands

### npm scripts (canonical)

| Command | What it does |
|---|---|
| `npm run dev` | Clean → compile → start BrowserSync + file watchers |
| `npm run build` | **Lint first**, then clean → compile with minification (production) |
| `npm run new-section -- <name>` | Scaffold a new section (SCSS + PHP + block registration) |
| `npm run new-section:js -- <name>` | Same, also creates the section JS file |
| `npm run lint` | Run SCSS + JS linters (use before committing) |
| `npm run lint:scss` | Run Stylelint on `src/scss/**/*.scss` |
| `npm run lint:scss:fix` | Auto-fix Stylelint violations |
| `npm run lint:js` | Run ESLint on `src/js/**/*.js` |
| `npm run setup` | **First-run only** — rename boilerplate identifiers, commit, self-destruct |

Example: `npm run new-section -- pricing_section`

BrowserSync proxy is hardcoded to `http://localhost/wordpress/` in [gulpfile.js:159](gulpfile.js#L159). Change it there if your local URL differs.

**Production vs development:** `NODE_ENV=production` (set by `npm run build` via `cross-env`) enables terser (JS minification) and cleanCSS (CSS minification). In dev mode output is expanded but not minified.

### Individual Gulp tasks (via `npx gulp <task>`)

| Task | What it does |
|---|---|
| `clean` | Delete entire `build/` directory |
| `styles` | Compile main + section + admin SCSS in parallel |
| `scripts` | Compile main JS (incl. admin-scripts) + section JS in parallel |
| `copyFonts` | Copy `fonts/**/*.{woff,woff2}` → `build/fonts/` |
| `generateFontsCSS` | Auto-generate `build/fonts/fonts.css` from font files |
| `browsersync` | Start BrowserSync server only |
| `watch` | Start file watchers only (no initial compile) |
| `stylesAdmin` | Compile `src/scss/admin-style.scss` → `build/css/admin-styles.min.css` |
| `stylesBlockToggle` | Compile `src/scss/acf-block-toggle.scss` → `build/css/acf-block-toggle.min.css` |
| `stylesEditorCanvas` | Compile `src/scss/editor-canvas-background.scss` → `build/css/editor-canvas-background.min.css` (block editor iframe canvas only) |
| `stylesEditorPreview` | Concatenate compiled `style.min.css` + all section CSS, wrap in `@scope (body) to (:where(.acf-fields))` → `build/css/editor-preview.min.css`. Must run after `styles` (reads its output off disk) |

---

## Adding a New Section (Step-by-Step)

### Naming convention

Use `snake_case` for all section names. Example: `contact_form_section`.

### Step 1 — Run the generator (creates all files and registers the block)

```bash
node generate-sections.js contact_form_section
# add --js if the section needs its own JavaScript:
node generate-sections.js contact_form_section --js
```

This creates and registers everything in one command:
- `src/scss/sections/contact_form_section.scss`
- `template-parts/sections/contact_form_section.php`
- `src/js/sections/contact_form_section.js` (only with `--js`)
- Appends `'contact_form_section'` to `$blocks` in `inc/acf_blocks.php`

Use `--dry-run` to preview output before writing.

### Step 2 — Build

The Gulp watcher picks up the new `.scss` and `.js` files automatically if already running. Otherwise:

```bash
npm run dev
```

### Step 4 — Add ACF field group

In the WordPress admin under ACF → Field Groups, create a new group and assign it to the block using **Location Rules** → Block → equals → the block title (auto-generated from slug: `contact_form_section` → "Contact Form Section").

### Files created per section (complete list)

```
template-parts/sections/contact_form_section.php    ← PHP template (edit this)
src/scss/sections/contact_form_section.scss          ← styles (edit this)
src/js/sections/contact_form_section.js              ← JS, only with --js (edit this)
inc/acf_blocks.php $blocks array                     ← auto-updated by generator
```

No imports needed in `style.scss` — section SCSS files compile independently to `build/css/sections/`.

---

## Naming Conventions

### Files
- All section-related files: `snake_case` (e.g., `hero_section`, `contact_form`)
- SCSS partials (in `src/scss/partials/`): underscore prefix + snake_case (`_variables.scss`)
- SCSS sections (in `src/scss/sections/`): NO underscore prefix (`hero_section.scss`)

### CSS classes
No strict BEM enforcement is visible in the codebase. The `hero_section.php` template uses `.hero` as the block class. General utility classes in `_general.scss` follow a flat pattern (`.grid-flex`, `.grid-item-2`, `.section-first`). Recommendation: use BEM for block-specific styles inside section SCSS files.

### PHP function prefixes
All functions across all `inc/` files now use the canonical **`smplfy_`** prefix.

| File | Prefix used |
|---|---|
| `functions.php` | `smplfy_` |
| `inc/acf_blocks.php` | `smplfy_` |
| `inc/enqueue.php` | `smplfy_` |
| `inc/theme_function.php` | `smplfy_` |

For all new functions, use `smplfy_`.

### ACF block names
Block `name` in `acf_register_block_type` matches the `$blocks` array value exactly (`hero_section`). The block is registered in WordPress as `acf/hero-section` (ACF converts underscores to hyphens). The block category slug is `smlfy`.

---

## SCSS Architecture

### Design Tokens

`src/scss/partials/_tokens.scss` is the **single source of truth** for all design values. It declares CSS custom properties on `:root` that are then referenced by `_variables.scss` SCSS variables (for backward compatibility) and by component SCSS files.

**How to add a new token:**
1. Add the CSS custom property to `_tokens.scss` under the appropriate section
2. If a SCSS `$variable` alias is needed, add it to `_variables.scss`
3. Reference `var(--token-name)` in component SCSS files directly

**Do not** add raw color, spacing, or typography values to component files — always define a token first.

### Import order in `src/scss/style.scss`

```scss
@use 'partials/tokens';   // ← CSS custom properties on :root (ALWAYS FIRST)
@use 'partials/fonts';
@use 'partials/general';
@use 'partials/header';
@use 'partials/footer';
@use 'partials/errors';
```

Section SCSS files (`src/scss/sections/*.scss`) are **not imported** into `style.scss`. They compile to separate files via the `stylesSections` Gulp task.

### Partials

| File | Status | Responsibility |
|---|---|---|
| `_tokens.scss` | **Has content** | CSS custom properties — single source of truth for all design values |
| `_variables.scss` | Has content | SCSS variables (now reference `_tokens.scss` custom properties) |
| `_general.scss` | Has content | CSS reset, layout utilities, typography scale, grid helpers |
| `_fonts.scss` | Has content | Manual `@font-face` declarations for Formular typeface |
| `_header.scss` | **EMPTY** | Header styles (not yet written) |
| `_footer.scss` | **EMPTY** | Footer styles (not yet written) |
| `_errors.scss` | **EMPTY** | Error page styles (not yet written) |

### Variable naming and status

After the 2026-05-26 audit and cleanup, `_variables.scss` contains only live variables:

| Variable | Status | Used in |
|---|---|---|
| `$font` | **USED** | `_general.scss` (font-family, inputs) |
| `$mobile` (480px) | **USED** | `_general.scss` — `.mobile`/`.no-mobile` utilities |
| `$small` (576px) | **USED** | `_general.scss` — 14 usages |
| `$medium` (768px) | **USED** | `_general.scss` — 6 usages |
| `$large` (992px) | **USED** | `_general.scss` — 3 usages |

All unused variables (`$primary-color`, `$secondary-color`, `$background-color`, `$text-color`, `$spacing-small`, `$spacing-medium`, `$spacing-large`, `$laptop`, `$extra-large`) were deleted on 2026-05-26.

**Breakpoints must remain as SCSS literal values** — CSS custom properties cannot be used inside `@media` queries.

### Using variables in partials and sections

```scss
@use "../partials/variables";
// reference as:
font-family: variables.$font;

// or for breakpoints:
@media screen and (max-width: variables.$medium) { ... }
```

Prefer CSS custom properties directly for color/spacing in new code:
```scss
color: var(--color-primary);
padding: var(--space-6);
transition: var(--transition-base);
```

---

## ACF Block Patterns

Field type rules and PHP rendering patterns → docs/acf-block-patterns.md § Field Type Rules

### Registration (in `inc/acf_blocks.php`)

```php
$blocks = [
    'hero_section',
    // add new blocks here
];

foreach ($blocks as $block_name) {
    acf_register_block_type([
        'name'            => $block_name,
        // snake_case → Title Case (e.g. hero_section → "Hero Section")
        'title'           => ucwords(str_replace('_', ' ', $block_name)),
        'render_template' => "template-parts/sections/{$block_name}.php",
        'category'        => 'smlfy',
        'icon'            => 'admin-customizer',
        'mode'            => 'preview',
        'keywords'        => ['section', $block_name],
        // Drives the editor canvas's full-width behavior — required for
        // ACF Blocks v3 / WP 7.1's always-iframed editor. See "ACF Blocks
        // v3 / iframe editor compatibility" below before changing this.
        'align'                    => 'full',
        'expanded_editor_buttons'  => ['toolbar'],
        'supports'        => [
            'align' => ['wide', 'full'],
            'mode'  => true,
            'jsx'   => true,
        ],
    ]);
}
```

For full ACF field group recipes and a manual block creation checklist, see [docs/acf-block-patterns.md](docs/acf-block-patterns.md).

### PHP template pattern

```php
<?php // template-parts/sections/example_section.php

$field = get_field('field_name');
?>
<section class="example-section">
    <div class="container">
        <?php if ($field): ?>
            <h2><?php echo esc_html($field); ?></h2>
        <?php endif; ?>
    </div>
</section>
```

The existing `hero_section.php` is minimal (`<section class="hero"><div class="container"></div></section>`) — no ACF fields are implemented yet.

---

## Enqueue Rules

### Global assets (every frontend page)

Handled in `inc/enqueue.php` at `wp_enqueue_scripts` priority **5**:

1. **Swiper CSS** — `assets/swiper/swiper-bundle.min.css` (conditional on `smplfy_should_load_swiper()`, which always returns true by default)
2. **Main CSS** — `build/css/style.min.css` (depends on Swiper CSS if loaded)
3. **Swiper JS** — `assets/swiper/swiper-bundle.min.js` (footer, conditional)
4. **Main JS** — `build/js/general.min.js` (footer, depends on Swiper JS if loaded)

Swiper is loaded from `assets/swiper/` — **these are manually placed vendor files, not from the npm `swiper` package**. The npm `swiper` package is listed in `dependencies` but is not wired up to the build.

To disable Swiper globally:
```php
add_filter('smplfy_load_swiper', '__return_false');
```

### Per-block assets (sections)

Handled in `inc/acf_blocks.php` at `wp_enqueue_scripts` priority **6** (after global at 5):

- Only runs on singular pages (`is_singular()`)
- Parses `$post->post_content` with `parse_blocks()` to detect which ACF blocks are actually on the page
- Enqueues `build/css/sections/{slug}.min.css` and `build/js/sections/{slug}.min.js` only for blocks that are present
- **Fallback:** if no blocks are detected (e.g., classic editor content), enqueues CSS for all registered blocks
- Handle pattern: `block-acf-hero-section-css` / `block-acf-hero-section-js` (underscores become hyphens)

### Admin/Editor assets

- Admin CSS: `build/css/admin-styles.min.css` ← compiled from `src/scss/admin-style.scss` (via `admin_enqueue_scripts`) — reaches the **top-level wp-admin document only** (toolbar, Inspector sidebar). Currently hides ACF's duplicate sidebar fields panel (`.acf-block-panel`) now that editing is modal-only.
- Editor JS: `build/js/admin-scripts.min.js` ← compiled from `src/js/admin-scripts.js` (via `enqueue_block_editor_assets`) — **skipped entirely** when `SMPLFY_DISABLE_ACF_TOGGLE` is defined true (see mu-plugin below); this is the old inline-toggle add-on, incompatible with ACF Blocks v3's iframed canvas.
- Editor CSS: `build/css/acf-block-toggle.min.css` ← compiled from `src/scss/acf-block-toggle.scss` — enqueued via `enqueue_block_assets` (not `enqueue_block_editor_assets` — the toggle markup lives INSIDE the iframe canvas, which only the former reaches), also skipped when the toggle is disabled.
- **Block editor iframe canvas** (`enqueue_block_assets`, gated to the iframe-collection pass only — see "ACF Blocks v3 / iframe editor compatibility" below): `build/css/editor-preview.min.css` (full frontend CSS, `@scope`-wrapped) and `build/css/editor-canvas-background.min.css` (canvas background + custom-block width pin). **Never confuse this hook's two passes** — `enqueue_block_assets` fires for both the top-level document and the iframe; gate iframe-only code behind `apply_filters('should_load_block_editor_scripts_and_styles', true)`.

### ACF Blocks v3 / iframe editor compatibility

WordPress 7.1 removed the last opt-out from the always-iframed block editor canvas. This theme's blocks are registered with `align: 'full'` + `expanded_editor_buttons: ['toolbar']` (see the registration snippet above) so editing happens through ACF Blocks v3's own "Edit" modal — this REQUIRES ACF Pro 6.6+. A companion mu-plugin, **not tracked in this repo** (mu-plugins are WP-instance-level, not theme-level), must be deployed to every environment's `wp-content/mu-plugins/`:

- `acf_blocks_wp71_compat.php` — forces ACF Blocks v3 API on all blocks server-side, defines `SMPLFY_DISABLE_ACF_TOGGLE` (read by `inc/enqueue.php`), shows an admin notice if ACF < 6.6 is active.
- `block_editor_reveal_animation_fix.php` — fixes three canvas-only cosmetic bugs common to any project once the canvas is iframed: scroll-reveal animations stuck invisible, clickable preview links breaking the iframe, Swiper sliders stuck in a dimmed state.

Both files are portable/drop-in (no project-specific assumptions) — ask whoever set up this project for a copy, or regenerate from `~/Desktop/ACF-BLOCKS-V3-WP71-MIGRATION-GUIDE.md` if the same author's other projects have them (kasinot/fcs-theme/kcs-theme all do).

**Before touching any editor-only CSS**, understand there are three separate editor stylesheets reaching three different documents — the wrong one is a silent no-op, not an error:
1. `editor-preview.min.css` / `editor-canvas-background.min.css` (`enqueue_block_assets`, iframe-gated) → **iframe canvas body only**.
2. `admin-styles.min.css` (`admin_enqueue_scripts`) → **top-level wp-admin document only** (sidebar/toolbar) — never the iframe.

If a new project built from this starter later adds its own generic alignment CSS (e.g. `.alignfull > .container { max-width: 100%; }`), re-check `editor-canvas-background.scss`'s `.acf-block-preview > *` rule still applies correctly — it's what prevents that generic rule from wrongly stretching custom blocks' containers edge-to-edge in the canvas only.

### Asset versioning

`smplfy_asset_ver()` uses `filemtime()` for cache busting — no manual version bumps needed.

---

## generate-sections.js

### Usage

```bash
node generate-sections.js <section_name> [--js] [--dry-run]
```

| Flag | Effect |
|---|---|
| `<section_name>` | Required. `snake_case` name matching `/^[a-z][a-z0-9_]*$/` |
| `--js` | Also create `src/js/sections/<name>.js` |
| `--dry-run` | Print all changes without writing anything |

### What it creates

Given `node generate-sections.js pricing_section --js`:

- `src/scss/sections/pricing_section.scss` — BEM root selector with `__container` and `__content` blocks
- `template-parts/sections/pricing_section.php` — Full BEM template with `get_field()` calls for `title`, `description`, `button_text`, `button_url`
- `src/js/sections/pricing_section.js` — `DOMContentLoaded` wrapper with section guard (only with `--js`)
- `inc/acf_blocks.php` — appends `'pricing_section'` to the `$blocks` string array

### What it does NOT do (by design)

- Does not touch `style.scss` — section SCSS files compile independently via the Gulp glob
- Does not touch `inc/enqueue.php` — per-section CSS/JS is enqueued automatically by `smplfy_enqueue_detected_block_assets()` which reads `$blocks` at runtime; no manual enqueue entries are needed
- Does not create ACF field groups — do that in the WordPress admin after running the generator

### Safety

- Aborts if `<section_name>` fails the regex check
- Aborts if any target file already exists (lists all conflicts)
- Aborts if the section name is already present in `inc/acf_blocks.php`
- `--dry-run` shows exact file contents and a diff context before writing anything

---

## Code Quality

### Linters

| Tool | Config file | Scope |
|---|---|---|
| **Stylelint** 17 | `.stylelintrc.json` | `src/scss/**/*.scss` |
| **ESLint** 10 | `eslint.config.js` | `src/js/**/*.js` |

Both extend their respective recommended configs with minimal rule overrides tuned to this codebase.

### Stylelint — `.stylelintrc.json`

Extends `stylelint-config-standard-scss`. Rules disabled and why:

| Rule | Reason disabled |
|---|---|
| `no-empty-source` | Empty stub files (`_header.scss`, `_footer.scss`, `_errors.scss`, `hero_section.scss`) are intentional |
| `font-family-name-quotes` | `'Formular'` is quoted in `_fonts.scss` — intentional WordPress convention |
| `property-no-vendor-prefix` | `-webkit-appearance` / `-moz-appearance` in `_general.scss` are intentional; autoprefixer handles the build output |
| `rule-empty-line-before` | Existing codebase style does not use blank lines between rules |
| `at-rule-empty-line-before` | Same reason as above |
| `color-hex-length` | `_tokens.scss` uses long hex form (`#333333`) — file was just stabilized; no rewrites |
| `scss/comment-no-empty` | `//` lines used as visual separators in comment blocks in `_tokens.scss` |
| `scss/double-slash-comment-empty-line-before` | Comments embedded in design token blocks would require reformatting |
| `scss/dollar-variable-colon-space-after` | Double-space alignment after `:` in `_variables.scss` is intentional |

### ESLint — `eslint.config.js`

Flat config format (ESLint v9+ style). Extends `@eslint/js` recommended. CommonJS (`require`) — no `"type": "module"` in package.json.

Browser globals declared manually: `document`, `window`, `navigator`, `console`, `fetch`, `MutationObserver`, `DOMParser`.

| Rule | Reason disabled |
|---|---|
| `no-console` | `console.error()` used intentionally in `replaceImagesWithInlineSVGs()` to report SVG fetch failures |

### How lint runs

- **`npm run build`** — lint runs first (SCSS + JS in parallel via `gulp lint`). Build aborts if any violation is found.
- **`npm run lint`** — run both linters manually at any time, e.g. before committing.
- **`npm run lint:scss:fix`** — auto-fix Stylelint violations (safe to run; only formatting fixes).
- **Dev/watch mode** (`npm run dev`) — lint does **not** run on every file change. Only on build.

### Adding new sections

When `generate-sections.js` creates a new SCSS file or JS file, lint will run against it on the next `npm run build`. The generator's templates are already lint-clean.

---

## Known Issues / Tech Debt

1. **Three theme names in use:** `butterfly-theme` (package.json), `smplfy` (style.css, functions.php), `Barvy Theme` (generate-sections.js comment). The codebase is a renamed/repurposed starter; `smplfy` is the active WordPress theme name.

2. ~~**Three PHP function prefixes:**~~ — **FIXED 2026-05-26.** All functions in `inc/acf_blocks.php`, `inc/enqueue.php`, and `inc/theme_function.php` renamed to `smplfy_` prefix. Filter hook names updated (`smplfy_registered_acf_blocks`, `smplfy_load_swiper`). Text domains `'barvy'` and `'lumina'` corrected to `'smplfy'`. `is_blog()` renamed to `smplfy_is_blog()`.

3. ~~**`getFontWeight` defined twice in gulpfile.js**~~ — **FIXED 2026-05-26.** Duplicate definition removed; single definition kept at gulpfile.js:93.

4. ~~**`scriptsMain` glob overlaps with `scriptsSections`**~~ — **FIXED 2026-05-26.** `paths.scripts.main` changed from `src/js/**/*.js` to `src/js/*.js`. Section JS no longer double-compiled. `admin-scripts.js` (at `src/js/`) is now correctly compiled to `build/js/admin-scripts.min.js` by `scriptsMain`.

5. ~~**`stylesAdmin` glob overlaps with `stylesMain`**~~ — **FIXED 2026-05-26.** `paths.styles.admin` changed from `src/scss/**/*.scss` to `src/scss/admin-style.scss`. The `stylesAdmin` rename now explicitly outputs `admin-styles.min.css` to match `inc/enqueue.php`'s expectation.

6. **Swiper from manual vendor files:** `assets/swiper/` must be manually kept in sync with `node_modules/swiper`. The npm package is installed but not used in the build pipeline.

7. ~~**`acf-block-toggle.min.css` has no source file**~~ — **FIXED 2026-05-26.** `src/scss/acf-block-toggle.scss` was already present in the repo but not compiled. Added `stylesBlockToggle` Gulp task compiling it to `build/css/acf-block-toggle.min.css`. Wired into `styles` parallel and `startwatch`.

8. **`_header.scss` and `_footer.scss` are empty** — header and footer have no styles yet.

9. **`hero_section.js` is empty** — section JS stub was never implemented.

10. **`single.php`, `archive.php`, `404.php` are empty stubs** — these templates have no content.

11. ~~**Duplicate breakpoint variable sets**~~ — **FIXED 2026-05-26.** Exact duplicate variables deleted (`$tablet` = same as `$medium`; `$desktop` = same as `$extra-large`). Discovered `$mobile` (480px) is genuinely used in `_general.scss` for `.mobile`/`.no-mobile` utility classes — kept. `$extra-large` and `$laptop` have zero usages and are marked `// UNUSED` in `_variables.scss`.

12. **`gulp-concat` in devDependencies but not used** in gulpfile.js.

13. **`smplfy_should_load_swiper()` always returns true** — the filter hook exists but no default-false path or conditional logic is implemented.

14. ~~**`str_replace('investments_', '', $block_name)`**~~ — **FIXED 2026-05-26.** Legacy no-op removed from block title generation. Title now uses `ucwords(str_replace('_', ' ', $block_name))` directly.

15. ~~**Blocks had `supports.align: false`, no top-level `align`**~~ — **FIXED 2026-08-21.** WordPress 7.1 removed the ability to opt out of the always-iframed block editor canvas; blocks now register `align: 'full'` + `expanded_editor_buttons: ['toolbar']` and rely on ACF Blocks v3's own modal for field editing instead of an inline-canvas toggle (which cannot reach into an iframe). See "ACF Blocks v3 / iframe editor compatibility" above — requires a companion mu-plugin not tracked in this repo.

---

## File Map

```
functions.php                    Theme bootstrap; defines THEME_URI, THEME_DIR, THEME_NAME, S_VERSION; requires inc/ files
style.css                        WordPress theme declaration header only (no actual CSS)
header.php                       HTML doc open, <head>, opens #wrapper > #header + <main>
footer.php                       Closes <main>, renders .footer, wp_footer(), closes </html>
index.php                        Blog/front fallback; redirects to static front page if set
page.php                         Standard page template; renders the_content() in a loop
single.php                       Single post template — EMPTY STUB
archive.php                      Archive template — EMPTY STUB
404.php                          404 template — EMPTY STUB

inc/acf_blocks.php               Registers ACF blocks; detects page blocks and conditionally enqueues per-section CSS/JS
inc/enqueue.php                  Enqueues global CSS/JS (Swiper, main styles, main scripts, admin, editor)
inc/theme_function.php           Misc helpers: nav active class, SVG upload, OG meta tags, login logo, ACF-driven feature flags

src/scss/style.scss                    Main SCSS entry; imports partials in order: tokens → fonts → general → header → footer → errors
src/scss/partials/_tokens.scss         CSS custom properties on :root — single source of truth for all design values
src/scss/partials/_variables.scss      SCSS variables (now reference _tokens.scss custom properties; kept for backward compat)
src/scss/partials/_general.scss        CSS reset, typography, layout grid, utility classes
src/scss/partials/_fonts.scss          @font-face declarations for Formular typeface
src/scss/partials/_header.scss         Header styles — EMPTY
src/scss/partials/_footer.scss         Footer styles — EMPTY
src/scss/partials/_errors.scss         Error page styles — EMPTY
src/scss/acf-block-toggle.scss         Block editor ACF toggle panel styles → build/css/acf-block-toggle.min.css (disabled when SMPLFY_DISABLE_ACF_TOGGLE is set — see ACF Blocks v3 section above)
src/scss/admin-style.scss              Admin CSS source → build/css/admin-styles.min.css. Hides ACF's duplicate sidebar fields panel (top-level wp-admin document only, never the iframe canvas)
src/scss/editor-canvas-background.scss Block editor iframe canvas background + custom-block width pin → build/css/editor-canvas-background.min.css (iframe canvas only, never the top-level document)
src/scss/sections/hero_section.scss    Hero section styles — EMPTY

src/js/general.js                Global JS: header mobile toggle, smooth scroll, img.svg→inline SVG
src/js/sections/hero_section.js  Hero section JS — EMPTY

template-parts/sections/hero_section.php  Hero block PHP template (minimal stub: <section class="hero">)

generate-sections.js             CLI scaffold: creates PHP/JS/SCSS stubs for blocks in the `blocks` array
gulpfile.js                      Gulp 5 build pipeline: compile SCSS, JS, fonts; BrowserSync; watch; lint on build
package.json                     npm manifest; scripts: dev, build, lint, lint:scss, lint:scss:fix, lint:js
.editorconfig                    Editor config: utf-8, LF, 4-space indent for PHP/JS/SCSS, 2-space for JSON
.stylelintrc.json                Stylelint config: extends stylelint-config-standard-scss with codebase overrides
eslint.config.js                 ESLint flat config (v9+): extends @eslint/js recommended, browser globals
scripts/setup.mjs                One-shot boilerplate setup: prompts, replaces identifiers, commits, self-destructs
README.md                        Project readme with first-time setup instructions
```
