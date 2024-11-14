import { CheckboxControl, PanelBody } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React from 'react';

const SetExtras = ( props ) => {
	const { meta, setMeta } = props;
	return (
		<PanelBody title={ __( 'Extras', 'events-manager' ) } initialOpen={ true }>
			<CheckboxControl
				label={ __( 'Allow Donation', 'events-manager' ) }
				help={ __( 'Allow attendees to donate for other attendees when booking.', 'events-manager' ) }
				checked={ meta._event_rsvp_donation }
				onChange={ ( value ) => {
					setMeta( { _event_rsvp_donation: value } );
				} }
				disabled={ ! meta._event_rsvp }
			/>
		</PanelBody>
	);
};

export default SetExtras;
