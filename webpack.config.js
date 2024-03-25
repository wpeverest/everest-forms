/**
 * External dependencies
 */
const { resolve } = require('path');
const NODE_ENV = process.env.NODE_ENV || "development";

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		"form-block": resolve(
			process.cwd(),
			'/assets/js/admin/gutenberg/form-block.js',
		),
		"dashboard": resolve(
			process.cwd(),
			'/src/dashboard/index.js',
		),
	},
	output: {
		path: resolve(process.cwd(), 'dist'),
		filename: "[name].min.js",
		libraryTarget: "this"
	},
	module: {
		rules: [
			{
				test: /.js$/,
				loader: "babel-loader",
				exclude: /node_modules/
			}
		]
	}
};

if (webpackConfig.mode !== "production") {
	webpackConfig.devtool = process.env.SOURCEMAP || "source-map";
}

module.exports = webpackConfig;
