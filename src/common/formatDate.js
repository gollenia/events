/**
 * Formats two dates to a date range
 * @param {Date} start
 * @param {Date} end
 * @returns string formatted date
 */
function formatDateRange( start, end = false ) {
	const locale = window.eventBlocksLocalization?.locale;

	if ( ! start ) return '';
	if ( start == end ) end = false;
	start = new Date( start );
	end = end ? new Date( end ) : start;

	const sameDay =
		start.getFullYear() === end.getFullYear() &&
		start.getMonth() === end.getMonth() &&
		start.getDate() === end.getDate();

	let dateFormat = {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	};

	if ( sameDay ) {
		dateFormat = {
			year: 'numeric',
			month: 'long',
			day: 'numeric',
		};
	}

	const dateFormatObject = new Intl.DateTimeFormat( locale, dateFormat );

	return dateFormatObject.formatRange( start, end );
}

/**
 * format date by goiven format object
 * @param {Date} date
 * @param {object} format
 * @returns string formated date
 */
function formatDate( date, format = false ) {
	if ( ! format ) format = { year: 'numeric', month: 'long', day: 'numeric' };

	const dateObject = new Date( date );

	const locale = window.eventBlocksLocalization?.locale;
	const dateFormatObject = new Intl.DateTimeFormat( locale, format );
	return dateFormatObject.format( dateObject );
}

function formatTime( start, end = false ) {
	if ( ! start ) return;
	if ( start == end ) end = false;
	const locale = window.eventBlocksLocalization?.locale;

	const timeFormat = {
		hour: 'numeric',
		minute: 'numeric',
	};

	const startDate = new Date( start );

	const timeFormatObject = new Intl.DateTimeFormat( locale, timeFormat );

	return timeFormatObject.format( startDate );
}

export { formatDate, formatDateRange, formatTime };
