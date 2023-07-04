const mix = require('laravel-mix');
const glob = require('glob');


let __config = {
    css: {
        files: [],
        ext: 'scss',
        destExt: 'css',
        src: 'src/scss',
        dest: 'dist/css',
    },
    js: {
        files: [],
        ext: 'js',
        destExt: 'js',
        src: 'src/js',
        dest: 'dist/js',
    }
};


glob.sync(`${__config.css.src}/[a-z0-9]*.scss`).forEach(file => __config.css.files.push(file));
glob.sync(`${__config.js.src}/[a-z0-9]*.js`).forEach(file => __config.js.files.push(file));


mix.webpackConfig({devtool: mix.inProduction() ? 'source-map' : 'inline-source-map'});
// mix.inProduction() && mix.disableNotifications();


runTasks({
    // suffix: mix.inProduction() ? '.min' : ''
    suffix: ''
});


function runTasks({suffix = ''}) {
    __config.css.files.forEach((file) => {
        const fileName = file
            .replace(`${__config.css.src}/`, '')
            .replace(`.${__config.css.ext}`, `${suffix}.${__config.css.destExt}`);

        mix.sass(file, `${__config.css.dest}/${fileName}`);
    });

    __config.js.files.forEach((file) => {
        const fileName = file
            .replace(`${__config.js.src}/`, '')
            .replace(`.${__config.js.ext}`, `${suffix}.${__config.js.destExt}`);

        mix.js(file, `${__config.js.dest}/${fileName}`)
    });
}
