/**
 *  External Dependencies
 */
import { __ } from "@wordpress/i18n";

/**
 *  Internal Dependencies
 */
import * as Icon from "../components/Icon/Icon";
import colormag from "../images/colormag.webp";
import ur from "../images/UR-logo.gif";
import magazineBlocks from "../images/magazine-blocks.webp";
import masteriyo from "../images/masteriyo.webp";
import blockart from "../images/blockart-blocks.webp";
import zakra from "../images/zakra.webp";

export const PLUGINS = [
	{
		label: "Masteriyo",
		slug: "learning-management-system/lms.php",
		description: __(
			"Revolutionize e-learning effortlessly with Masteriyo, a WordPress LMS plugin. Sell courses with quizzes, assignments, etc., for a dynamic learning experience.",
			"everest-forms"
		),
		type: "plugin",
		image: masteriyo,
		website: "https://masteriyo.com/",
		shortDescription: __(
			"WordPress LMS plugin with Quiz Builder",
			"everest-forms"
		),
		logo: Icon.Masteriyo,
		liveDemoURL: "https://masteriyo.demoswp.net/",
	},
	{
		label: "BlockArt Blocks",
		slug: "blockart-blocks/blockart.php",
		description: __(
			"Fuel your digital creativity with BlockArt Blocks, a dynamic collection of custom Gutenberg blocks for designing captivating WordPress sites.",
			"everest-forms"
		),
		type: "plugin",
		image: blockart,
		website: "https://wpblockart.com/blockart-blocks/",
		shortDescription: __(
			"Custom Gutenberg Blocks Plugin",
			"everest-forms"
		),
		logo: Icon.Blockart,
		liveDemoURL: "https://tastewp.com/template/blockartblocks",
	},
	{
		label: 'User Registration',
		slug: 'user-registration/user-registration.php',
		description: __(
			'The best Drag and drop user registration form and login form builder with a user profile page, email notification, user roles assignment, and more.',
			'everest-forms',
		),
		type: 'plugin',
		image: ur,
		website: 'https://wpuserregistration.com/',
		shortDescription: __(
			'User Forms, Profiles, Roles, Notifications.',
			'everest-forms',
		),
		logo: Icon.UR,
		liveDemoURL: "https://userregistration.demoswp.net/",
	},
	{
		label: "Magazine Blocks",
		slug: "magazine-blocks/magazine-blocks.php",
		description: __(
			"Experience advanced Gutenberg blocks with Magazine Blocks, designed for crafting stunning magazine and news websites.",
			"everest-forms"
		),
		type: "plugin",
		image: magazineBlocks,
		website: "https://wpblockart.com/magazine-blocks/",
		shortDescription: __(
			"Gutenberg Blocks for Magazine-style Websites",
			"everest-forms"
		),
		logo: Icon.MagazineBlocks,
		liveDemoURL: "https://tastewp.com/template/magazineblocks",
	},
];

export const THEMES = [
	{
		label: "Zakra",
		slug: "zakra",
		description: __(
			"Unlock boundless website possibilities with Zakra, a versatile multipurpose theme offering over 40 free starter sites for a tailored web experience.",
			"everest-forms"
		),
		type: "theme",
		image: zakra,
		website: "https://zakratheme.com/",
		liveDemoURL: "https://zakratheme.com/demos/#/",
	},
	{
		label: "ColorMag",
		slug: "colormag",
		description: __(
			"Elevate your website's style with Colormag, the go-to choice for news, blogs, and magazines. Embark on a digital spectacle of website-building excellence! ",
			"everest-forms"
		),
		type: "theme",
		image: colormag,
		website: "https://themegrill.com/themes/colormag/",
		liveDemoURL: "https://themegrilldemos.com/colormag-demos/#/",
	},
];

export const FreeModules = [
	'ai-contact-form',
	'everest-forms-oxygen-builder',
	'everest-forms-beaver-builder',
	'everest-forms-bricks-builder',
	'everest-forms-divi-builder',
	'everest-forms-wpbakery-builder',
	'everest-forms-style-customizer',
]
