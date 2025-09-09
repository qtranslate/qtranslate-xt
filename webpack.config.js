// Webpack configuration for qTranslate-XT

module.exports = {
    entry: {
        'main': {
            import: './js/main.js',
            library:
                {
                    name: 'qTranx',
                    type: 'var',
                },
        },
        'block-editor': './js/block-editor.js',
        'notices': './js/notices.js',
        'options': './js/options.js',
        'modules/acf': './js/acf/index.js',
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
