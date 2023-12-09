const path = require("path");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = {
  entry: {
    app: './includes/global-sections/display-options/src/index.jsx',
  },
  output: {
    filename: "[name].js",
    path: path.resolve(__dirname + '/includes/global-sections/display-options/', "build"),
  },
  plugins: [
    new MiniCssExtractPlugin({
      filename: '[name].css',
    }),
  ],
  module: {
    rules: [
      {
        test: /\.s[ac]ss$/i,
        use: [
          //MiniCssExtractPlugin to keep css in separate files from js
          MiniCssExtractPlugin.loader, 
          'css-loader',
          'sass-loader'
        ]
      },
      {
        test: /\.(js|jsx)$/,
        exclude: /(node_modules)/,
        use: {
          loader: 'babel-loader',
          options: {
            presets: ['@babel/preset-env', '@babel/preset-react']
          }
        }
      },
    ],
  },
  mode: 'production'
};