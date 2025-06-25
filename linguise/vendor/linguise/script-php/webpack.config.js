const path = require('path');
const webpack = require('webpack');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const DotenvPlugin = require('dotenv-webpack');
const Dotenv = require('dotenv').config({path: __dirname + '/.env'}).parsed;

module.exports = {
    entry: {
        login: './assets/js/login.js',
        admin: './assets/js/admin.js',
    },
    mode: Dotenv.MODE,
    output: {
        path: path.resolve(__dirname, 'assets'),
        filename: 'js/[name].bundle.js'
    },
    devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
    externals: {
        jquery: 'jQuery',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components|vendor)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },
            {
                test: /\.(sc|c)ss$/i,
                use: [
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    "sass-loader"
                ]
            },
            {
                test: /\.(png|svg|jpg|jpeg|gif|lottie)$/i,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '[name].[ext]',
                            outputPath: (url, resourcePath, context) => {
                                // To get relative path you can use and replace windows backslashes with forward slashes
                                const relativePath = path.relative(context, resourcePath).replace(/\\/g, '/');

                                console.log(url, resourcePath, context, relativePath)

                                if (/^node_modules\//.test(relativePath)) {
                                    if (relativePath.includes('flags-rounded')) {
                                        return `./images/flags-rounded/${url}`;
                                    }
                                    return `./images/flags-rectangular/${url}`;
                                }

                                return '../' + relativePath;
                            },
                            publicPath: (url, resourcePath, context) => {
                                // To get relative path you can use and replace windows backslashes with forward slashes
                                const relativePath = path.relative(context, resourcePath).replace(/\\/g, '/');

                                if (/^node_modules\//.test(relativePath)) {
                                    if (relativePath.includes('flags-rounded')) {
                                        return `../../assets/images/flags-rounded/${url}`;
                                    }
                                    return `../../assets/images/flags-rectangular/${url}`;
                                }

                                return '../../' + relativePath;
                            },
                        },
                    }
                ]
            },
            {
                test: /\.(woff|woff2|ttf|eot)$/i,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: './fonts/[name].[ext]',
                            publicPath: (url, resourcePath, context) => {
                                return `../${url}`;
                            },
                        },
                    }
                ]
            },
        ]
    },
    plugins: [
        new webpack.optimize.LimitChunkCountPlugin({
            maxChunks: 1, // disable creating additional chunks
        }),
        require("autoprefixer"),
        new DotenvPlugin({path: __dirname + '/.env'}),
        new MiniCssExtractPlugin({
            filename: "css/[name].bundle.css",
        })
    ],
    optimization: {
        minimizer: [],
    },
}

if (Dotenv.MODE === 'production') {
    module.exports.optimization.minimize = true;
    module.exports.optimization.minimizer.push(
        new TerserPlugin(),
        new CssMinimizerPlugin(),
    );
}
