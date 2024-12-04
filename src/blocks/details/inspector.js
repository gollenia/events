import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Inspector = ( props ) => {
	const { attributes, setAttributes } = props;

	const { dividers } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Appearance', 'events' ) } initialOpen={ true }>
				<PanelRow>
					<ToggleControl
						label={ __( 'Lines as separators', 'events' ) }
						checked={ dividers }
						onChange={ ( event ) => setAttributes( { dividers: event } ) }
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};

export default Inspector;
