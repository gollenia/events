const HtmlBlock = ( props ) => {
	const { value, width } = props;
	const classes = [ 'core-block', 'grid__column--span-' + width ].join( ' ' );

	return <div className={ classes } dangerouslySetInnerHTML={ { __html: value } } />;
};

export default HtmlBlock;
