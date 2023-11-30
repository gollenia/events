/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './editor.scss';
import icon from './icon';
import save from './save';
import './style.scss';

/**
 * Wordpress dependencies
 */

const { name, title, description } = metadata;

const settings = {
	...metadata,
	icon,
	edit,
	save,
};

export { name, settings };
