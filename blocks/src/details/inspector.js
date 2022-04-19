import { InspectorControls } from '@wordpress/block-editor';
import { CheckboxControl, TextControl, ToggleControl, RangeControl, PanelBody, PanelRow, SelectControl, FormTokenField, Icon, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n'; 



const Inspector = (props) => {
	
	const {
		attributes: {
			showLocation,
			showAudience,
			showDate,
			showTime,
			showSpeaker,
			showPrice,
			audienceDescription,
			audienceIcon,
			locationLink,
			speakerDescription,
			priceOverwrite,
			speakerIcon,
			speakerLink
		},
		setAttributes,
		
	} = props;

  	return (
		<InspectorControls>
		<PanelBody
			title={__('Audience', 'events')}
			initialOpen={true}
		>
			<ToggleControl
				label={ __("Show Audience", 'events')}
				checked={ showAudience }
				onChange={ (value) => setAttributes({ showAudience: value }) }
			/>
			<TextControl
				disabled={!showAudience}
				label={__("Description", "events")}
				help={__("If empty, default description is shown")}
				value={ audienceDescription }
				onChange={ (value) => setAttributes({ audienceDescription: value })}
			/>
			<SelectControl
				disabled={!showAudience}
				label={ __("Icon", 'events')}
				value={ audienceIcon }
				options={ [
					{ value: 'users', label: 'People' },
					{ value: 'male', label: 'Male' },
					{ value: 'female', label: 'Female' }
				] }
				onChange={ (value) => setAttributes({ audienceIcon: value }) }
			/>
		</PanelBody>
		<PanelBody
			title={__('Location', 'events')}
			initialOpen={true}
		>
			<ToggleControl
				label={ __("Show Location", 'events')}
				checked={ showLocation }
				onChange={ (value) => setAttributes({ showLocation: value }) }
			/>
			<CheckboxControl
				disabled={!showLocation}
				label={ __("Add Link", 'events')}
				checked={ locationLink }
				onChange={ (value) => setAttributes({ locationLink: value }) }
			/>
		</PanelBody>
		<PanelBody
			title={__('Date and Time', 'events')}
			initialOpen={true}
		>
			<ToggleControl
				label={ __("Show Date", 'events')} 
				checked={ showDate }
				onChange={ (value) => setAttributes({ showDate: value }) }
			/>
			<ToggleControl
				label={ __("Show Time", 'events')}
				checked={ showTime }
				onChange={ (value) => setAttributes({ showTime: value }) }
			/>
		</PanelBody>
		<PanelBody
			title={__('Speaker', 'events')}
			initialOpen={true}
		>
			<ToggleControl
				label={ __("Show Speaker", 'events')}
				checked={ showSpeaker }
				onChange={ (value) => setAttributes({ showSpeaker: value }) }
			/>
			<TextControl
				disabled={!showSpeaker}
				label={__("Description", "events")}
				help={__("If empty, default description is shown")}
				value={ speakerDescription }
				onChange={ (value) => setAttributes({ speakerDescription: value })}
			/>
			<SelectControl
				disabled={!showSpeaker}
				label={ __("Icon", 'events')}
				value={ speakerIcon }
				options={ [
					{ value: '', label: 'Photo of speaker' },
					{ value: 'male', label: 'Male' },
					{ value: 'female', label: 'Female' }
				] }
				onChange={ (value) => setAttributes({ speakerIcon: value }) }
			/>
			<CheckboxControl
				disabled={!showSpeaker}
				label={ __("Add email link", 'events')}
				checked={ speakerLink }
				onChange={ (value) => setAttributes({ speakerLink: value }) }
			/>
		</PanelBody>
		<PanelBody
			title={__('Price', 'events')}
			initialOpen={true}
		>
			<ToggleControl
				label={ __("Show Price", 'events')}
				checked={ showPrice }
				onChange={ (value) => setAttributes({ showPrice: value }) }
			/>
			<TextControl
				disabled={!showPrice}
				label={__("Overwrite Price", "events")}
				help={__("If empty, the first ticket's price is used")}
				value={ priceOverwrite }
				onChange={ (value) => setAttributes({ priceOverwrite: value })}
			/>
		</PanelBody>
		</InspectorControls>
  	);
};

export default Inspector;