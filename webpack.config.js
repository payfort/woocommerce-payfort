const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const WooCommerceDependencyExtractionWebpackPlugin = require('@woocommerce/dependency-extraction-webpack-plugin');
const path = require('path');

const wcDepMap = {
	'@woocommerce/blocks-registry': ['wc', 'wcBlocksRegistry'],
	'@woocommerce/settings'       : ['wc', 'wcSettings']
};

const wcHandleMap = {
	'@woocommerce/blocks-registry': 'wc-blocks-registry',
	'@woocommerce/settings'       : 'wc-settings'
};

const requestToExternal = (request) => {
	if (wcDepMap[request]) {
		return wcDepMap[request];
	}
};

const requestToHandle = (request) => {
	if (wcHandleMap[request]) {
		return wcHandleMap[request];
	}
};

// Export configuration.
module.exports = {
	...defaultConfig,
	entry: {
		'frontend/blocks-aps_cc': '/resources/js/frontend/aps_cc.js',
		'frontend/blocks-aps_knet': '/resources/js/frontend/aps_knet.js',
		'frontend/blocks-aps_naps': '/resources/js/frontend/aps_naps.js',
		'frontend/blocks-aps_benefit': '/resources/js/frontend/aps_benefit.js',
		'frontend/blocks-aps_installment': '/resources/js/frontend/aps_installment.js',
		'frontend/blocks-aps_omannet': '/resources/js/frontend/aps_omannet.js',
		'frontend/blocks-aps_stc_pay': '/resources/js/frontend/aps_stc_pay.js',
		'frontend/blocks-aps_tabby': '/resources/js/frontend/aps_tabby.js',
		'frontend/blocks-aps_apple_pay': '/resources/js/frontend/aps_apple_pay.js',
		'frontend/blocks-aps_valu': '/resources/js/frontend/aps_valu.js',
	},
	output: {
		path: path.resolve( __dirname, 'assets/js' ),
		filename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
		new WooCommerceDependencyExtractionWebpackPlugin({
			requestToExternal,
			requestToHandle
		})
	]
};
