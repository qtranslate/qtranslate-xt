// Webpack configuration for qTranslate-XT

module.exports = {
    entry: {
        'main': './admin/js/main.js',
        'editor-gutenberg': './admin/js/editor-gutenberg.js',
        'notices': './admin/js/notices.js',
        'options': './admin/js/options.js',
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
