import metadata from './block.json';
import { ContactForm } from './../../components/Icon';
import Edit from './Edit';
import Save from './Save';
export const name = metadata.name;
export const settings = {
	...metadata,
	icon:ContactForm,
	edit:Edit,
	save:Save,
};
