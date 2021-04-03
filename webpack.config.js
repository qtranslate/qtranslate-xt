const path = require('path');
const {CleanWebpackPlugin} = require('clean-webpack-plugin');

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
    },
    output: {
        path: path.resolve(__dirname, 'dist'),
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
    plugins: [
        new CleanWebpackPlugin()
    ],
};
