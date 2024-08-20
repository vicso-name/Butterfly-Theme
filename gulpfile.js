const { src, dest, parallel, series, watch } = require('gulp');
const browserSync = require('browser-sync').create();
const concat = require('gulp-concat');
const uglify = require('gulp-uglify-es').default;
const sass = require('gulp-sass')(require('sass'));
const autoprefixer = require('gulp-autoprefixer');
const cleancss = require('gulp-clean-css');
const plumber = require('gulp-plumber');
const gulpIf = require('gulp-if');
const cached = require('gulp-cached');
const remember = require('gulp-remember');

const isProduction = process.env.NODE_ENV === 'production';

// Define the preprocessor used (e.g., 'sass' or 'scss')
const preprocessor = 'sass'; // Use 'scss' if your project uses SCSS files

// BrowserSync configuration for live reloading
function browsersync() {
    browserSync.init({
        proxy: {
            target: "http://localhost:8000/", // Adjust this to your local dev server URL
            ws: true
        },
        notify: false,
        online: true
    });
}

// Scripts task - compiles, concatenates, and optionally minifies JS
function scripts() {
    return src([
        'node_modules/swiper/swiper-bundle.min.js',
        'node_modules/glightbox/dist/js/glightbox.js',
        'js/app.js' // Adjusted to relative path within the theme folder
    ])
    .pipe(plumber()) // Prevents pipe breaking caused by errors
    .pipe(cached('scripts')) // Cache the scripts for faster recompiling
    .pipe(remember('scripts')) // Ensures all files are passed
    .pipe(concat('app.min.js')) // Concatenate into one file
    .pipe(gulpIf(isProduction, uglify())) // Minify only if in production
    .pipe(dest('js')) // Output the JS file in your theme's js folder
    .pipe(browserSync.stream()); // Inject changes without a full reload
}

// Styles task - compiles, concatenates, and optionally minifies CSS
function styles() {
    return src([
        'node_modules/bootstrap/dist/css/bootstrap-grid.min.css', // Bootstrap grid CSS
        'node_modules/swiper/swiper-bundle.min.css', // Swiper CSS
        'css/' + preprocessor + '/main.' + preprocessor // Your main Sass/SCSS file
    ])
    .pipe(plumber()) // Prevents pipe breaking
    .pipe(sass()) // Compile Sass/SCSS to CSS
    .pipe(concat('app.min.css')) // Concatenate into one file
    .pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true })) // Add vendor prefixes
    .pipe(gulpIf(isProduction, cleancss({ level: { 1: { specialComments: 0 } } }))) // Minify only if in production
    .pipe(dest('css')) // Output the CSS file in your theme's css folder
    .pipe(browserSync.stream()); // Inject changes without a full reload
}

// Watcher task - monitors changes and triggers respective tasks
function startwatch() {
    // Watch JS files and trigger scripts task
    watch(['js/**/*.js', '!js/**/*.min.js'], scripts);

    // Watch Sass/SCSS files and trigger styles task
    watch(['css/sass/*.sass'], styles).on("change", browserSync.reload);

    // Watch PHP files and trigger browser reload on changes
    watch(['*.php']).on("change", browserSync.reload);
}

// Define tasks
exports.browsersync = browsersync;
exports.scripts = scripts;
exports.styles = styles;
exports.default = parallel(styles, scripts, browsersync, startwatch);
