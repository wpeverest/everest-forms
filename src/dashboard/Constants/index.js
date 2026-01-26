import { __ } from '@wordpress/i18n';

const { isPro, adminURL, showAnalyticsTab } =
	typeof _EVF_DASHBOARD_ !== 'undefined' && _EVF_DASHBOARD_;

const normalizeAdminURL = (url) => {
	if (!url) return '';

	let cleanURL = url.endsWith('/') ? url.slice(0, -1) : url;

	if (cleanURL.endsWith('/admin.php')) {
		cleanURL = cleanURL.slice(0, -10);
	}

	return cleanURL;
};

const cleanAdminURL = normalizeAdminURL(adminURL);

let ROUTES = [
	{
		route: '/',
		label: __('Site Assistant', 'everest-forms'),
		external: false,
	},
	{
		route: `${cleanAdminURL}/admin.php?page=evf-entries`,
		label: __('Entries', 'everest-forms'),
		external: true,
	},
	{
		route: `${cleanAdminURL}/admin.php?page=evf-settings`,
		label: __('Settings', 'everest-forms'),
		external: true,
	},
	{
		route: '/features',
		label: __('All Features', 'everest-forms'),
		external: false,
	},
	{
		route: '/help',
		label: __('Help', 'everest-forms'),
		external: false,
	},
];

if (isPro && showAnalyticsTab) {
	ROUTES = [
		ROUTES[0],
		{
			route: `${cleanAdminURL}/admin.php?page=everest-forms-analytics`,
			label: __('Analytics', 'everest-forms'),
			external: true,
		},
		...ROUTES.slice(1),
	];
}

if (!isPro) {
	ROUTES = [
		...ROUTES.slice(0, 4),
		{
			route: 'https://everestforms.net/free-vs-pro/',
			label: __('Free vs Pro', 'everest-forms'),
			external: true,
		},
		...ROUTES.slice(4),
	];
}

export default ROUTES;

export const CHANGELOG_TAG_COLORS = {
	fix: {
		color: 'primary.500',
		bgColor: 'primary.100',
		scheme: 'primary',
	},
	feature: {
		color: 'green.500',
		bgColor: 'green.50',
		scheme: 'green',
	},
	enhance: {
		color: 'teal.500',
		bgColor: 'teal.50',
		scheme: 'teal',
	},
	refactor: {
		color: 'pink.500',
		bgColor: 'pink.50',
		scheme: 'pink',
	},
	dev: {
		color: 'orange.500',
		bgColor: 'orange.50',
		scheme: 'orange',
	},
	tweak: {
		color: 'purple.500',
		bgColor: 'purple.50',
		scheme: 'purple',
	},
};

export const facebookUrl = 'https://www.facebook.com/groups/everestforms';
export const youtubeChannelUrl = 'https://www.youtube.com/@EverestForms';
export const twitterUrl = 'https://twitter.com/everestforms';
export const reviewUrl =
	'https://wordpress.org/support/plugin/everest-forms/reviews/?rate=5#new-post';

/**
 * Normalize admin URL - remove trailing slashes and admin.php if present
 * @param {string} url - WordPress admin URL
 * @returns {string} - Normalized URL
 */
const normalizeURL = (url) => {
	if (!url) return '';

	let cleanURL = url.endsWith('/') ? url.slice(0, -1) : url;

	if (cleanURL.endsWith('/admin.php')) {
		cleanURL = cleanURL.slice(0, -10);
	}

	return cleanURL;
};

/**
 * Convert internal routes to full admin URLs when needed
 * @param {string} route - The route path
 * @param {boolean} isNonDashboardPage - Whether we're on a non-dashboard page (settings, entries, etc.)
 * @param {string} adminURL - WordPress admin URL
 * @returns {string} - Converted route
 */
export const convertRoute = (route, isNonDashboardPage, adminURL) => {
	if (route.includes('admin.php') || route.includes('?page=')) {
		return route;
	}

	if (isNonDashboardPage) {
		const cleanURL = normalizeURL(adminURL);

		if (route === '/') {
			return `${cleanURL}/admin.php?page=evf-dashboard`;
		}
		return `${cleanURL}/admin.php?page=evf-dashboard#${route}`;
	}

	return route;
};

/**
 * Check if route is external (WordPress admin link)
 * @param {string} route - The route path
 * @returns {boolean}
 */
export const isExternalRoute = (route) => {
	return route.includes('admin.php') || route.includes('?page=');
};

/**
 * Check if route is active
 * @param {string} route - The route path
 * @param {string} currentPath - Current location path
 * @param {boolean} isSettingsPage - Whether we're on settings page
 * @param {string} pageType - Current page type (settings, entries, analytics, etc.)
 * @returns {boolean}
 */
export const isRouteActive = (route, currentPath, isSettingsPage, pageType) => {
	// Check for analytics page
	if (pageType === 'analytics' && route.includes('everest-forms-analytics')) {
		return true;
	}
	// Check for entries page
	if (pageType === 'entries' && route.includes('evf-entries')) {
		return true;
	}
	// Check for settings page
	if (isSettingsPage && route.includes('evf-settings')) {
		return true;
	}
	if (!isSettingsPage && !isExternalRoute(route)) {
		return currentPath === route;
	}
	return false;
};
