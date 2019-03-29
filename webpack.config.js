/**
 * External dependencies
 */
const path = require("path");
const NODE_ENV = process.env.NODE_ENV || "development";

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		"form-block": "./assets/js/admin/gutenberg/form-block.js"
	},
	output: {
		path: path.resolve(__dirname, "assets/js/admin/gutenberg"),
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
