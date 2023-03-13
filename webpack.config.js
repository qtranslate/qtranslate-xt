// Webpack configuration for qTranslate-XT

module.exports = {
    entry: {
        'main': './js/main.js',
        'editor-gutenberg': './js/editor-gutenberg.js',
        'notices': './js/notices.js',
        'options': './js/options.js',
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
