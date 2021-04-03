// Webpack configuration for qTranslate-XT

module.exports = {
    entry: {
        'common': './admin/js/common.js',
        'edit-tags-exec': './admin/js/edit-tags-exec.js',
        'editor-gutenberg': './admin/js/editor-gutenberg.js',
        'nav-menus-exec': './admin/js/nav-menus-exec.js',
        'notices': './admin/js/notices.js',
        'options': './admin/js/options.js',
        'post-exec': './admin/js/post-exec.js',
        'widgets-exec': './admin/js/widgets-exec.js',
        'modules/acf': './modules/acf/js/index.js',
    },
    output: {
        clean: true,
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
        ]
    },
};
