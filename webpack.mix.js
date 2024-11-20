let mix = require('laravel-mix');

mix
    .copy('assets/js/js.cookie.js', 'dist')
    .js('assets/js/cookies-editor.js', 'dist').react()
    .js('assets/js/cookies-front.js', 'dist')
    .setPublicPath('dist');