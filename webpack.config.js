const path = require('path');
// const MiniCssExtractPlugin = require('mini-css-extract-plugin');
// const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
// const {CleanWebpackPlugin} = require('clean-webpack-plugin');

module.exports = {
    entry: {
        'common': './admin/js/src/common.js',
        'edit-tags-exec': './admin/js/src/edit-tags-exec.js',
        'editor-gutenberg': './admin/js/src/editor-gutenberg.js',
        'nav-menus-exec': './admin/js/src/nav-menus-exec.js',
        'notices': './admin/js/src/notices.js',
        'options': './admin/js/src/options.js',
        'post-exec': './admin/js/src/post-exec.js',
        'widgets-exec': './admin/js/src/widgets-exec.js',
    },
    output: {
        path: path.resolve(__dirname, 'dist'),
        filename: "[name].js"
    },
    externals: {
        jquery: 'jQuery'
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

/*
// plugins: [
    new MiniCssExtractPlugin({
        filename: "[name].css",
        chunkFilename: "[id].css"
    }),
    new CssMinimizerPlugin(),
    new CleanWebpackPlugin()
],
*/
