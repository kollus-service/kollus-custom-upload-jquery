var fs = require('fs'),
  path = require('path'),
  gulp = require('gulp'),
  concat = require('gulp-concat'),
  header = require('gulp-header'),
  pkg = JSON.parse(
    fs.readFileSync(path.resolve(__dirname, './package.json'))
  ),
  banner = ['/**',
    ' * Copyright (c) <%= new Date().getFullYear() %>',
    ' * <%= pkg.name %> - <%= pkg.description %>',
    ' * Built on <%= (new Date).toISOString().slice(0,10) %>',
    ' * ',
    ' * @version <%= pkg.version %>',
    ' * @link <%= pkg.repository.url %>',
    ' * @license <%= pkg.license %>',
    ' */',
    ''].join('\n') + '\n';

gulp.task('build', function () {
  gulp.src(['src/'])
    .pipe(concat('kollus-upload.js'))
    .pipe(header(banner, {pkg: pkg}))
    .pipe(gulp.dest('dist/'));
});

gulp.task('publish', function() {
  gulp.src(['dist/kollus-upload.js'])
    .pipe(concat('default.js'))
    .pipe(gulp.dest('public/js/'));
});


