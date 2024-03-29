/**
 * Internal dependencies
 */
import Inspector from './inspector';

/**
 * WordPress dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';

export default function Edit( { ...props } ) {
	const allowedBlocks = [
		'events-manager/details-audience',
		'events-manager/details-time',
		'events-manager/details-speaker',
		'events-manager/details-spaces',
		'events-manager/details-shutdown',
		'events-manager/details-date',
		'events-manager/details-item',
		'events-manager/details-price',
		'events-manager/details-audience',
		'events-manager/details-location',
	];

	const {
		attributes: { dividers },
		className,
	} = props;

	const classes = [ 'event-details', className, dividers ? 'has-divider' : false ].filter( Boolean ).join( ' ' );

	const template = [
		[ 'events-manager/details-audience' ],
		[ 'events-manager/details-date' ],
		[ 'events-manager/details-time' ],
		[ 'events-manager/details-speaker' ],
		[ 'events-manager/details-location' ],
		[ 'events-manager/details-price' ],
		[ 'events-manager/details-spaces' ],
		[ 'events-manager/details-shutdown' ],
	];

	const blockProps = useBlockProps( { className: classes } );

	const innerBlocksProps = useInnerBlocksProps( {}, { allowedBlocks, template, templateLock: false } );

	return (
		<div { ...blockProps }>
			<Inspector { ...props } />
			<div { ...innerBlocksProps }></div>
		</div>
	);
}
