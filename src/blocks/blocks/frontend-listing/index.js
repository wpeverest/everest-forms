import metadata from './block.json';
import { FrontendListing } from "../../components/Icon";
import Edit from './Edit';
import Save from './Save';
export const name = metadata.name;
export const settings = {
	...metadata,
	icon:FrontendListing,
	edit:Edit,
	save:Save,
};
