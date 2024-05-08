import { registerBlockType } from "@wordpress/blocks";
import { applyFilters } from "@wordpress/hooks";
import * as formSelector from "./form-selector";
import * as frontendListing from "./frontend-listing";
import * as UserLogin from "./user-login";

/* global _EVF_BLOCKS_ */
const { isFrontendListingActive, isUserRegistrationActive, isPro } =
	typeof _EVF_BLOCKS_ !== "undefined" && _EVF_BLOCKS_;

let blocks = [formSelector];

if (isPro && isFrontendListingActive) {
	blocks.push(frontendListing);
}
if (isPro && isUserRegistrationActive) {
	blocks.push(UserLogin);
}
blocks = applyFilters("everest-forms.blocks", blocks);

/**
 * The function "registerBlocks" iterates over an array of blocks and calls the
 * "register" method on each block.
 */

export const registerBlocks = () => {
	for (const block of blocks) {
		const settings = applyFilters(
			"everest-forms.blocks.metadata",
			block.settings,
		);
		settings.edit = applyFilters(
			"everest-forms.blocks.edit",
			settings.edit,
			settings,
		);
		//Register the blocks.
		registerBlockType(block.name, settings);
	}
};

export default registerBlocks;
