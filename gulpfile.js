const { src, dest, parallel, series, watch } = require('gulp');
const browserSync = require('browser-sync').create();
const terser = require('gulp-terser');
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer').default;
const cleanCSS = require('gulp-clean-css');
const plumber = require('gulp-plumber');
const gulpIf = require('gulp-if');
const del = require('del');
const notify = require('gulp-notify');
const rename = require('gulp-rename');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');
const util = require('util');
const execAsync = util.promisify(exec);

const isProduction = process.env.NODE_ENV === 'production';

const paths = {
  src: 'src',
  build: 'build',
  scripts: {
    main: 'src/js/*.js',
    sections: 'src/js/sections/**/*.js',
    dest: 'build/js',
    destSections: 'build/js/sections'
  },
  styles: {
    main: 'src/scss/style.scss',
    sections: 'src/scss/sections/**/*.scss',
    admin: 'src/scss/admin-style.scss',
    blockToggle: 'src/scss/acf-block-toggle.scss',
    editorCanvas: 'src/scss/editor-canvas-background.scss',
    dest: 'build/css',
    destSections: 'build/css/sections',
    destAdmin: 'build/css'
  },
  php: {
    src: 'src/**/*.php'
  }
};

async function clean() {
  await del([paths.build]);
}

function copyFonts() {
  return src('fonts/**/*.{woff,woff2}')
    .pipe(dest('build/fonts'));
}

function generateFontsCSS(done) {
  const fontsDir = 'fonts';
  const outputDir = 'build/fonts';
  const outputFile = path.join(outputDir, 'fonts.css');
  let cssContent = '';

  // Проверяем, существует ли папка fonts, если нет - пропускаем
  if (!fs.existsSync(fontsDir)) {
    console.log('⚠ Папка fonts не найдена. Пропускаем генерацию fonts.css.');
    done();
    return;
  }

  // Проверяем, существует ли папка build/fonts, если нет - создаем
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  // Читаем папку fonts
  fs.readdirSync(fontsDir).forEach(folder => {
    const folderPath = path.join(fontsDir, folder);
    if (fs.lstatSync(folderPath).isDirectory()) {
      fs.readdirSync(folderPath).forEach(file => {
        if (file.endsWith('.woff') || file.endsWith('.woff2')) {
          const fontName = file.replace(/\.(woff2|woff)$/, '');
          const fontWeight = getFontWeight(file);

          cssContent += `
@font-face {
  font-family: '${folder}';
  src: url('../fonts/${folder}/${file}') format('${file.endsWith('.woff2') ? 'woff2' : 'woff'}');
  font-weight: ${fontWeight};
  font-style: normal;
}\n`;
        }
      });
    }
  });

  // Записываем стили в build/fonts/fonts.css
  fs.writeFileSync(outputFile, cssContent);
  done();
}


function getFontWeight(filename) {
  if (filename.toLowerCase().includes('bold')) return 700;
  if (filename.toLowerCase().includes('semibold')) return 600;
  if (filename.toLowerCase().includes('medium')) return 500;
  if (filename.toLowerCase().includes('light')) return 300;
  if (filename.toLowerCase().includes('extralight')) return 200;
  return 400;
}

function scriptsMain() {
  return src(paths.scripts.main)
    .pipe(plumber({ errorHandler: notify.onError("Ошибка в Scripts Main: <%= error.message %>") }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulpIf(isProduction, terser()))
    .pipe(dest(paths.scripts.dest))
    .pipe(browserSync.stream());
}

function scriptsSections() {
  return src(paths.scripts.sections)
    .pipe(plumber({ errorHandler: notify.onError("Ошибка в Scripts Sections: <%= error.message %>") }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulpIf(isProduction, terser()))
    .pipe(dest(paths.scripts.destSections))
    .pipe(browserSync.stream());
}

function stylesAdmin() {
  return src(paths.styles.admin)
    .pipe(plumber({ errorHandler: notify.onError("Ошибка в Styles Admin: <%= error.message %>") }))
    .pipe(sass({ outputStyle: 'expanded' }))
    .pipe(rename({ basename: 'admin-styles', suffix: '.min' }))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(gulpIf(isProduction, cleanCSS({ level: { 1: { specialComments: 0 } } })))
    .pipe(dest(paths.styles.destAdmin))
    .pipe(browserSync.stream());
}

function stylesBlockToggle() {
  return src(paths.styles.blockToggle)
    .pipe(plumber({ errorHandler: notify.onError("Error in Styles Block Toggle: <%= error.message %>") }))
    .pipe(sass({ outputStyle: 'expanded' }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(gulpIf(isProduction, cleanCSS({ level: { 1: { specialComments: 0 } } })))
    .pipe(dest(paths.styles.dest))
    .pipe(browserSync.stream());
}

function stylesMain() {
  return src(paths.styles.main)
    .pipe(plumber({ errorHandler: notify.onError("Ошибка в Styles Main: <%= error.message %>") }))
    .pipe(sass({ outputStyle: 'expanded' }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(gulpIf(isProduction, cleanCSS({ level: { 1: { specialComments: 0 } } })))
    .pipe(dest(paths.styles.dest))
    .pipe(browserSync.stream())
    .on('end', () => {
      fs.appendFileSync(`${paths.styles.dest}/style.min.css`, '\n@import "../fonts/fonts.css";\n');
    });
}

function stylesSections() {
  return src(paths.styles.sections)
    .pipe(plumber({ errorHandler: notify.onError("Ошибка в Styles Sections: <%= error.message %>") }))
    .pipe(sass({ outputStyle: 'expanded' }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(gulpIf(isProduction, cleanCSS({ level: { 1: { specialComments: 0 } } })))
    .pipe(dest(paths.styles.destSections))
    .pipe(browserSync.stream());
}

function stylesEditorCanvas() {
  return src(paths.styles.editorCanvas)
    .pipe(plumber({ errorHandler: notify.onError("Error in Styles Editor Canvas: <%= error.message %>") }))
    .pipe(sass({ outputStyle: 'expanded' }))
    .pipe(rename({ suffix: '.min' }))
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
    .pipe(gulpIf(isProduction, cleanCSS({ level: { 1: { specialComments: 0 } } })))
    .pipe(dest(paths.styles.dest))
    .pipe(browserSync.stream());
}

/**
 * Block editor preview CSS — the block editor's iframe canvas (where ACF
 * block previews render) is the SAME document as ACF's own field-editing
 * UI (.acf-fields), so simply loading the theme's normal CSS there makes
 * its un-scoped resets/typography (bare `body`, `h1`-`h5`, `a`, `input`,
 * `textarea`... selectors — fine on the frontend, where nothing else
 * shares the page) collide with and mangle ACF's own field styling.
 *
 * Rather than trying to patch each collision with overrides, this
 * concatenates the already-compiled style.min.css + every section's CSS
 * and wraps the result in `@scope (body) to (:where(.acf-fields))` —
 * every selector inside can only ever match within the iframe body and
 * is structurally barred from matching anything inside an .acf-fields
 * subtree, so it cannot touch ACF's UI no matter what selectors get
 * added to the theme later.
 *
 * Selectors inside `@scope (X) { ... }` get an implicit `:scope ` prefix
 * — a DESCENDANT combinator — so they can only ever match descendants of
 * the scope root, never the root element X itself. `:root` and bare
 * `html { ... }` rules both resolve to the single <html> element, which
 * can't be its own descendant, so nested inside @scope (of any root)
 * they're permanently dead — this would break every custom property
 * (--color-primary etc. never resolving anywhere), so :root and bare
 * html{} blocks are hoisted out above the @scope wrapper as plain global
 * rules instead. Also handles a responsive `@media (...) { :root { ... } }`
 * override — Sass compiles a media-wrapped :root block that way, and
 * hoisting just the inner :root would silently drop the media condition,
 * so whole @media blocks whose *entire* body is :root rule(s) are hoisted
 * intact instead. Must run after stylesMain + stylesSections (reads their
 * output), see stylesEditorPreview below.
 */
function extractDocumentRootBlocks(css) {
  const extracted = [];
  let remainder = '';
  let lastIndex = 0;
  const re = /@media[^{]*\{|:root\s*\{|html\s*\{/g;
  let match;

  while ((match = re.exec(css))) {
    let depth = 1;
    let i = match.index + match[0].length;
    while (depth > 0 && i < css.length) {
      if (css[i] === '{') depth++;
      else if (css[i] === '}') depth--;
      i++;
    }
    const block = css.slice(match.index, i);

    let shouldHoist = true;
    if (match[0].startsWith('@media')) {
      const inner = block.slice(block.indexOf('{') + 1, block.lastIndexOf('}')).trim();
      shouldHoist = /^((:root|html)\s*\{[^{}]*\}\s*)+$/.test(inner);
    }

    if (shouldHoist) {
      remainder += css.slice(lastIndex, match.index);
      extracted.push(block);
      lastIndex = i;
    }

    re.lastIndex = i;
  }

  remainder += css.slice(lastIndex);
  return { extracted, remainder };
}

function stylesEditorPreview(done) {
  const files = [path.join(paths.styles.dest, 'style.min.css')];

  if (fs.existsSync(paths.styles.destSections)) {
    fs.readdirSync(paths.styles.destSections)
      .filter((f) => f.endsWith('.min.css'))
      .forEach((f) => files.push(path.join(paths.styles.destSections, f)));
  }

  let combined = '';
  files.forEach((file) => {
    if (fs.existsSync(file)) {
      combined += fs.readFileSync(file, 'utf8') + '\n';
    }
  });

  // @import must be the first thing in a stylesheet (style.min.css
  // appends one for fonts.css) — @scope can't contain a nested @import,
  // so hoist any out above the wrapper instead of dropping them.
  const imports = [];
  let bodyCss = combined.replace(/@import\s+[^;]+;/g, (match) => {
    imports.push(match);
    return '';
  });

  const { extracted: rootBlocks, remainder } = extractDocumentRootBlocks(bodyCss);
  bodyCss = remainder;

  const output = `${imports.join('\n')}\n${rootBlocks.join('\n')}\n@scope (body) to (:where(.acf-fields)) {\n${bodyCss}\n}\n`;
  fs.writeFileSync(path.join(paths.styles.dest, 'editor-preview.min.css'), output);
  done();
}


function browsersyncServe(done) {
  browserSync.init({
    proxy: "http://localhost/wordpress/",
    notify: false,
    open: false
  });
  done();
}

function browsersyncReload(done) {
  browserSync.reload();
  done();
}

function startwatch() {
  watch('src/scss/**/*.scss', series(stylesMain, stylesEditorPreview));
  watch(paths.styles.sections, series(stylesSections, stylesEditorPreview));
  watch(paths.styles.admin, stylesAdmin);
  watch(paths.styles.blockToggle, stylesBlockToggle);
  watch(paths.styles.editorCanvas, stylesEditorCanvas);
  watch(paths.scripts.main, scriptsMain);
  watch(paths.scripts.sections, scriptsSections);
  watch(paths.php.src, browsersyncReload);
}

async function lintScss() {
  try {
    const { stdout } = await execAsync('npx stylelint "src/scss/**/*.scss"', { cwd: __dirname });
    if (stdout) process.stdout.write(stdout);
  } catch (err) {
    if (err.stdout) process.stdout.write(err.stdout);
    throw new Error('SCSS lint failed — run npm run lint:scss for details');
  }
}

async function lintJs() {
  try {
    const { stdout } = await execAsync('npx eslint "src/js/**/*.js"', { cwd: __dirname });
    if (stdout) process.stdout.write(stdout);
  } catch (err) {
    if (err.stdout) process.stdout.write(err.stdout);
    throw new Error('JS lint failed — run npm run lint:js for details');
  }
}

const lint = parallel(lintScss, lintJs);
const scripts = parallel(scriptsMain, scriptsSections);
const styles = parallel(stylesMain, stylesSections, stylesAdmin, stylesBlockToggle, stylesEditorCanvas);
// stylesEditorPreview reads stylesMain/stylesSections' compiled output off
// disk, so it must run after that parallel group finishes, not inside it.
const stylesAll = series(styles, stylesEditorPreview);

const build = series(lint, clean, parallel(stylesAll, scripts, copyFonts, generateFontsCSS));
const dev = series(clean, parallel(stylesAll, scripts), browsersyncServe, startwatch);

exports.clean = clean;
exports.lint = lint;
exports.scripts = scripts;
exports.styles = styles;
exports.stylesAdmin = stylesAdmin;
exports.stylesBlockToggle = stylesBlockToggle;
exports.stylesEditorCanvas = stylesEditorCanvas;
exports.stylesEditorPreview = stylesEditorPreview;
exports.browsersync = browsersyncServe;
exports.watch = startwatch;
exports.copyFonts = copyFonts;
exports.generateFontsCSS = generateFontsCSS;
exports.dev = dev;
exports.build = build;
exports.default = dev;