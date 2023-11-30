import {
	__experimentalColorGradientSettingsDropdown as ColorGradientSettingsDropdown,
	InspectorControls,
	__experimentalUseMultipleOriginColorsAndGradients as useMultipleOriginColorsAndGradients,
} from '@wordpress/block-editor';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Inspector = (props) => {
	const {
		attributes,
		setAttributes,
		iconColor,
		iconBackgroundColor,
		setIconColor,
		setIconBackgroundColor,
		clientId,
	} = props;

	const colorGradientSettings = useMultipleOriginColorsAndGradients();

	const {
		image,
		roundImage,
		url,
		urlIcon,
		icon,
		customIconBackgroundColor,
		customIconColor,
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={__('Behaviour', 'ctx-blocks')}
					initialOpen={true}
				>
					<TextControl
						label={__('Link', 'ctx-blocks')}
						value={url}
						onChange={(value) => setAttributes({ url: value })}
					/>
					<TextControl
						label={__('Icon for Link', 'ctx-blocks')}
						value={urlIcon}
						onChange={(value) => setAttributes({ urlIcon: value })}
					/>
				</PanelBody>

				<PanelBody title={__('Image', 'ctx-blocks')} initialOpen={true}>
					<PanelRow>
						<TextControl
							label={__('Icon', 'ctx-blocks')}
							value={icon}
							onChange={(value) => setAttributes({ icon: value })}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<InspectorControls group="color">
				<ColorGradientSettingsDropdown
					settings={[
						{
							label: __('Icon Background', 'ctx-blocks'),
							colorValue:
								iconBackgroundColor.color ||
								customIconBackgroundColor,
							onColorChange: (value) => {
								setIconBackgroundColor(value);

								setAttributes({
									customIconBackgroundColor: value,
								});
							},
						},
					]}
					panelId={clientId}
					hasColorsOrGradients={false}
					disableCustomColors={false}
					__experimentalIsRenderedInSidebar
					{...colorGradientSettings}
				/>
				<ColorGradientSettingsDropdown
					settings={[
						{
							label: __('Icon Color', 'ctx-blocks'),
							colorValue: iconColor.color || customIconColor,
							onColorChange: (value) => {
								setIconColor(value);

								setAttributes({
									customIconColor: value,
								});
							},
						},
					]}
					panelId={clientId}
					hasColorsOrGradients={false}
					disableCustomColors={false}
					__experimentalIsRenderedInSidebar
					{...colorGradientSettings}
				/>
			</InspectorControls>
		</>
	);
};

export default Inspector;
