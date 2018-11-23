module.exports = {
	entry: './assets/js/admin/gutenberg/form-block.js',
	output: {
		path: __dirname,
		filename: 'assets/js/admin/gutenberg/form-block.min.js',
	},
	module: {
		loaders: [
			{
				test: /.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/,
			},
		],
	},
};
