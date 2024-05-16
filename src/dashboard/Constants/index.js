import { __ } from "@wordpress/i18n";

const { isPro, utmCampaign } =
	typeof _EVF_DASHBOARD_ !== "undefined" && _EVF_DASHBOARD_;

let ROUTES = [
	{
		route: "/",
		label: __("Dashboard", "everest-forms"),
	},
	{
		route: "/features",
		label: __("All Features", "everest-forms"),
	},
	{
		route: "/settings",
		label: __("Settings", "everest-forms"),
	},

	{
		route: "/help",
		label: __("Help", "everest-forms"),
	},
	{
		route: "/products",
		label: __("Other Products", "everest-forms"),
	},
];

if (!isPro) {
	ROUTES = [
		...ROUTES.slice(0, 4),
		{
			route: "/free-vs-pro",
			label: __("Free vs Pro", "everest-forms"),
		},
		...ROUTES.slice(4),
	];
}
export default ROUTES;

export const CHANGELOG_TAG_COLORS = {
	fix: {
		color: "primary.500",
		bgColor: "primary.100",
		scheme: "primary",
	},
	feature: {
		color: "green.500",
		bgColor: "green.50",
		scheme: "green",
	},
	enhance: {
		color: "teal.500",
		bgColor: "teal.50",
		scheme: "teal",
	},
	refactor: {
		color: "pink.500",
		bgColor: "pink.50",
		scheme: "pink",
	},
	dev: {
		color: "orange.500",
		bgColor: "orange.50",
		scheme: "orange",
	},
	tweak: {
		color: "purple.500",
		bgColor: "purple.50",
		scheme: "purple",
	},
};
export const facebookUrl = "https://www.facebook.com/groups/everestforms";
export const youtubeChannelUrl = "https://www.youtube.com/@EverestForms";
export const twitterUrl = "https://twitter.com/everestforms";
export const reviewUrl =
	"https://wordpress.org/support/plugin/everest-forms/reviews/?rate=5#new-post";
