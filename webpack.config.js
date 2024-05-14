/**
 * External dependencies
 */
const { resolve } = require('path');
const path = require("path");
const CopyPlugin = require("copy-webpack-plugin");
const NODE_ENV = process.env.NODE_ENV || "development" || "production";

const webpackConfig = {
	mode: NODE_ENV,
	entry: {
		"dashboard": resolve(
			process.cwd(),
			'./src/dashboard/index.js',
		),
		"blocks": resolve(
			process.cwd(),
			'./src/blocks/index.js',
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
			},
			{
				test: /\.(png|svg|jpg|jpeg|gif|webp)$/i,
				use: [
					{
						loader: 'file-loader',
					},
				],
			}
		]
	},
	plugins: [
		new CopyPlugin({
			patterns: [
				{
					from: "./src/blocks/**/block.json",
					to({ absoluteFilename }) {
						return path.resolve(
							__dirname,
							"dist",
							path.basename(path.dirname(absoluteFilename)),
							"block.json",
						);
					},
				},
			],
		}),
	],
	externals: {
		"@wordpress/blocks": ["wp", "blocks"],
		"@wordpress/components": ["wp", "components"],
		"@wordpress/block-editor": ["wp", "blockEditor"],
		"@wordpress/server-side-render": ["wp", "serverSideRender"],
		react: ["React"],
	},
};

if (webpackConfig.mode !== "production") {
	webpackConfig.devtool = process.env.SOURCEMAP || "source-map";
}

module.exports = webpackConfig;
