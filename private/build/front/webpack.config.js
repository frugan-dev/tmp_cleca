//https://github.com/hamzahamidi/webpack-typescript-starter
//https://github.com/VD39/typescript-webpack-boilerplate
//https://github.com/taniarascia/webpack-boilerplate
//https://dev.to/rinconcamilo/setting-up-eslint-prettier-with-webpack-in-vscode-29fg
//https://www.robinwieruch.de/webpack-eslint/
//https://blog.logrocket.com/using-prettier-eslint-automate-formatting-fixing-javascript/
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserWebpackPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ImageminWebpackPlugin = require('imagemin-webpack-plugin').default;
const SpritesmithPlugin = require('webpack-spritesmith');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const ESLintPlugin = require('eslint-webpack-plugin');
const StylelintPlugin = require('stylelint-webpack-plugin');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const Autoprefixer = require('autoprefixer')
//const FlexGapPolyfill = require('flex-gap-polyfill');

/**
 * Base webpack configuration
 *
 * @param env -> env parameters
 * @param argv -> CLI arguments, 'argv.mode' is the current webpack mode (development | production)
 * @returns object
 */
module.exports = (env, argv = {}) => {
  let isProduction = argv.mode === 'production';

  let config = {
    context: path.resolve(__dirname, 'src'),

    entry: [
      './js/vendor.ts',
      './js/index.ts',
      './scss/vendor.scss',
      './scss/index.scss',
    ],

    // When using the terser-webpack-plugin you must provide the sourceMap: true option to enable SourceMap support
    devtool: isProduction ? !isProduction : 'inline-source-map',

    watchOptions: {
      //https://stackoverflow.com/q/71000223/3929620
      poll: 1000,
    },

    output: {
      filename: 'js/' + (isProduction ? 'min/' : '') + '[name].js',
      path: path.resolve(__dirname, '../../../public/asset/front'),
      publicPath: '/asset/front/',
    },

    resolve: {
      extensions: ['.tsx', '.ts', '.js'],
      modules: ['node_modules', 'dist/sprite'],
    },

    //https://www.npmjs.com/package/sass-loader
    // Defaults, the output of @debug messages is disabled
    stats: {
      loggingDebug: !isProduction ? ['sass-loader'] : [],
    },

    plugins: [
      new CleanWebpackPlugin({
        dry: isProduction,
      }),

      new MiniCssExtractPlugin({
        filename: (pathData) => {
          return (
            'css/' +
            (isProduction ? 'min/' : '') +
            (pathData.chunk.name === 'main'
              ? '[name].css'
              : 'vendor.[contenthash].css')
          );
        },
        chunkFilename: 'css/' + (isProduction ? 'min/' : '') + '[id].css',
      }),

      // copy static assets directory
      new CopyWebpackPlugin({
        patterns: [
          { from: 'img', to: 'img' },
          //{from: 'fonts', to: 'fonts'},
          {
            context:
              path.resolve(__dirname, 'node_modules') + '/@uppy/locales/dist',
            from: '*',
            to: 'js/locales/@uppy',
          },
          'site.webmanifest',
        ],
      }),

      // image optimization
      new ImageminWebpackPlugin({
        // disable for dev builds
        //FIXME - Explicitly disabled to avoid pnpm binary path issues
        disable: true, // !isProduction
        test: /\.(jpe?g|png|gif)$/i,
        pngquant: { quality: '70-85' },
        optipng: { optimizationLevel: 9 },
      }),

      new SpritesmithPlugin({
        src: {
          cwd: path.resolve(__dirname, 'src/sprite'),
          glob: '*.+(png|jpg|gif)',
        },
        target: {
          image: path.resolve(__dirname, 'dist/sprite/sprite.[hash].png'),
          css: path.resolve(__dirname, 'dist/sprite/sprite.scss'),
        },
        apiOptions: {
          cssImageRef: '~sprite.[hash].png',
        },
      }),

      new webpack.IgnorePlugin({
        resourceRegExp: /^\.\/locale$/,
        contextRegExp: /moment$/,
      }),

      new ESLintPlugin({
        extensions: ['.js', '.ts', '.json'],
        // https://github.com/eslint/eslint/discussions/18484#discussioncomment-9516248
        //overrideConfigFile: 'eslint.config.js',
      }),

      new StylelintPlugin(),

      /*new BundleAnalyzerPlugin({
        analyzerMode: !isProduction ? 'static' : 'disabled',
      }),*/
    ],

    optimization: {
      runtimeChunk: 'single',

      splitChunks: {
        chunks: 'all',
        maxInitialRequests: Infinity,
        minSize: 0,
        cacheGroups: {
          vendor: {
            test(module) {
              return /[\\/]node_modules[\\/]/.test(module.context);
            },
            name(module) {
              // get the name. E.g. node_modules/packageName/not/this/part.js
              // or node_modules/packageName
              const packageName = module.context.match(
                // npm package names are URL-safe, but some servers don't like @ symbols
                /[\\/]\.pnpm[\\/].+?[\\/]node_modules[\\/](([^\@]*?)|\@(.*?)[\\/](.*?))([\\/]|$)/
              );

              return `node/${packageName[2] ?? packageName[3] + '-' + packageName[4]}`;
            },
          },
        },
      },

      // If you want to run it also in development set the optimization.minimize option to true
      minimize: isProduction,

      minimizer: [
        `...`,
        // CSS optimizer
        new CssMinimizerPlugin({
          parallel: true,
          minimizerOptions: {
            preset: [
              'default',
              {
                discardComments: {
                  removeAll: isProduction,
                },
              },
            ],
          },
        }),
        // JS optimizer by default
        new TerserWebpackPlugin({
          parallel: true,
          extractComments: !isProduction,
          terserOptions: {
            output: {
              comments: !isProduction,
            },
            sourceMap: !isProduction,
          },
        }),
      ],
    },

    module: {
      //https://dev.to/smelukov/webpack-5-asset-modules-2o3h
      //https://stackoverflow.com/a/69041786/3929620
      //https://stackoverflow.com/a/68537419/3929620
      //https://bobbyhadz.com/blog/javascript-get-string-after-last-slash
      rules: [
        {
          test: /\.tsx?$/,
          use: 'swc-loader',
          exclude: /node_modules/,
        },

        {
          test: /\.(sa|sc|c)ss$/,
          use: [
            // The order in which webpack apply loaders is from last to first
            MiniCssExtractPlugin.loader,
            // Creates `style` nodes from JS strings
            //'style-loader',
            // Translates CSS into CommonJS
            {
              loader: 'css-loader',
              options: {
                sourceMap: !isProduction,
              },
            },
            //https://medium.com/@marius.dras/fix-font-awesome-asset-loading-in-webpack-cbd00e978bd3
            {
              loader: 'resolve-url-loader',
              options: {
                sourceMap: !isProduction,
              },
            },
            // Run postcss actions
            {
              loader: 'postcss-loader',
              options: {
                // `postcssOptions` is needed for postcss 8.x;
                // if you use postcss 7.x skip the key
                postcssOptions: {
                  // postcss plugins, can be exported to postcss.config.js
                  plugins: [
                    Autoprefixer(),
                    //https://caniuse.com/flexbox-gap
                    //https://stackoverflow.com/a/66956594/3929620
                    //https://stackoverflow.com/a/67133945/3929620
                    //https://alistapart.com/article/axiomatic-css-and-lobotomized-owls/
                    //[
                    //  'flex-gap-polyfill',
                    //  {
                    //    flexGapNotSupported: '.no-flexgap',
                    //  },
                    //],
                  ],
                },
                //https://github.com/bholloway/resolve-url-loader/issues/212#issuecomment-1011630220
                // resolve-url-loader: error processing CSS a valid source-map is not present (ensure preceding loaders output a source-map)
                sourceMap: true,
              },
            },
            // Compiles Sass to CSS
            {
              loader: 'sass-loader',
              options: {
                //https://github.com/bholloway/resolve-url-loader/issues/212#issuecomment-1011630220
                // resolve-url-loader: error processing CSS a valid source-map is not present (ensure preceding loaders output a source-map)
                sourceMap: true,
              },
            },
          ],
        },

        {
          test: /\.(png|jpe?g|gif)$/,
          type: 'asset/resource',
          generator: {
            filename: (pathData) => {
              if (/node_modules\//.test(pathData.filename)) {
                const relativePath = pathData.filename
                  .split('node_modules/')
                  .pop();

                return 'img/node/' + relativePath;
              } else if (/dist\/sprite\//.test(pathData.filename)) {
                const relativePath = pathData.filename
                  .split('dist/sprite/')
                  .pop();

                return 'img/sprite/' + relativePath;
              }

              return 'img/[name][ext]';
            },
          },
        },

        {
          test: /\.(woff|woff2|eot|ttf|otf|svg)$/,
          type: 'asset/resource',
          generator: {
            filename: 'fonts/[name][ext]',
          },
        },

        {
          test: /\.modernizrrc$/,
          use: [{
            loader: 'val-loader',
            options: {
              executableFile: require.resolve('val-loader-modernizr'),
            }
          }]
        },
      ],
    },
  };

  return config;
};
