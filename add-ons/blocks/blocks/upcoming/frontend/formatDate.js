/**
 * Formats two dates to a date range
 * @param {Date} start 
 * @param {Date} end 
 * @returns string formatted date
 */
function formatDateRange(start, end, locale = false) {

	if(!locale) {
		window.eventBlockLocale.lang;
	}

	start = new Date(start);
	end = new Date(end);

	const sameDay = start.getFullYear() === end.getFullYear() &&
		start.getMonth() === end.getMonth() &&
		start.getDate() === end.getDate();
	

	let dateFormat = {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
		
	};

	if(sameDay) {
		dateFormat = {
			year: 'numeric',
			month: 'long',
			day: 'numeric',
			hour: 'numeric',
    		minute: 'numeric'
		};
	}

	const dateFormatObject  = new Intl.DateTimeFormat(locale, dateFormat);
	return dateFormatObject.formatRange(start, end);
	
}

/**
 * format date by goiven format object
 * @param {Date} date 
 * @param {object} format 
 * @returns string formated date
 */
function formatDate(date, format) {
	const locale = window.eventBlockLocale.lang;
	const dateFormatObject  = new Intl.DateTimeFormat(locale, format);
	return dateFormatObject.format(date);
}

export { formatDateRange, formatDate };