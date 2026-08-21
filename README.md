# smplfy — ACF Gutenberg Theme Boilerplate

A WordPress theme boilerplate where every page section is an independently registered ACF Gutenberg block with its own PHP template, SCSS, and JS.

---

## Requirements

- WordPress 7.1+ (always-iframed block editor canvas — see below) with **ACF Pro 6.6+** installed and active (Blocks v3 API, required for the iframed canvas)
- PHP 7.4+
- Node.js 18+
- A local development server (MAMP, LocalWP, Laragon, etc.)

### Required mu-plugins (not tracked in this repo)

This theme's blocks are built for ACF Blocks v3's own editing UI (modal/
sidebar), required because WordPress 7.1 removed the ability to opt out of
the always-iframed block editor canvas. Two small, portable mu-plugins are
required on **every environment** (they're WP-instance-level, not theme-
level, hence not tracked here) — copy them to `wp-content/mu-plugins/`:

- `acf_blocks_wp71_compat.php` — forces the ACF Blocks v3 API
- `block_editor_reveal_animation_fix.php` — fixes canvas-only cosmetic bugs (scroll animations, sliders, clickable preview links)

Without these, block editing will appear broken (fields won't show where
expected, blocks may render incorrectly in the canvas). See `CLAUDE.md` §
"ACF Blocks v3 / iframe editor compatibility" for the full explanation.

---

## First-time setup

```bash
git clone <repo-url> your-theme-name
cd your-theme-name
npm install
npm run setup
```

`npm run setup` will ask for your project name, slug, author details, and local dev URL, then replace every boilerplate identifier across the codebase and commit the result. The script deletes itself when done.

> If `scripts/setup.mjs` does not exist, setup has already been run on this project — skip straight to Development.

> **Deployment via git?** Remove `build/` from `.gitignore` so compiled assets are tracked and available on the server after `git pull`.

---

## Development

```bash
npm run dev      # compile + BrowserSync + file watchers
npm run build    # lint → compile (minified, production)
```

BrowserSync proxies your local WordPress URL. If it differs from the default, update it in `gulpfile.js` on the `proxy` line.

---

## Adding a section

```bash
npm run new-section -- section_name          # SCSS + PHP + block registration
npm run new-section:js -- section_name       # same, also creates the JS file
```

Use `snake_case` for section names. After running the command, create the matching ACF field group in the WordPress admin (ACF → Field Groups → Location: Block → equals → the block title).

---

## Project structure

```
src/
├── scss/
│   ├── partials/          ← shared styles: tokens, variables, general, fonts
│   └── sections/          ← one .scss per ACF block (compile independently)
└── js/
    ├── general.js         ← global JS loaded on every page
    └── sections/          ← one .js per ACF block

template-parts/sections/   ← PHP templates for ACF blocks
inc/
├── acf_blocks.php         ← block registration + per-page asset detection
├── enqueue.php            ← global CSS/JS enqueue
└── theme_function.php     ← theme helpers
fonts/                     ← source font files (.woff/.woff2)
docs/                      ← developer reference
build/                     ← Gulp output (gitignored)
```

---

## Code quality

```bash
npm run lint             # run SCSS + JS linters
npm run lint:scss:fix    # auto-fix SCSS violations
```

Linters run automatically as the first step of `npm run build`.

---

## Docs

- [ACF block patterns, field group recipes, and enqueue behaviour](docs/acf-block-patterns.md)
