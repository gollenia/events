/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import './editor.scss';
import icon from './icon';

/**
 * Wordpress dependencies
 */
import { __ } from '@wordpress/i18n';

const { name, title, description } = metadata;

const settings = {
	...metadata,
	title: __( title, 'events-manager' ),
	description: __( description, 'events-manager' ),
	icon,
	edit,
	save: () => {
		return null;
	},
};

export { name, settings };
