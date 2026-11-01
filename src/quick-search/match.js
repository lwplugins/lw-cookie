/**
 * Search matching: accent- and case-insensitive, every typed word must match.
 */

/**
 * Lowercase without accents ("Hozzájárulás" → "hozzajarulas"). Accents are
 * single combining marks after NFD, so string offsets stay aligned with the
 * original text for highlighting.
 *
 * @param {string} text Text.
 * @return {string} Normalized text.
 */
export const normalize = ( text ) =>
	String( text )
		.normalize( 'NFD' )
		.replace( /[\u0300-\u036f]/g, '' )
		.toLowerCase();

/**
 * Typed words.
 *
 * @param {string} query Raw query.
 * @return {string[]} Normalized terms.
 */
export const terms = ( query ) =>
	normalize( query ).split( /\s+/ ).filter( Boolean );

/**
 * Relevance of an entry, or -1 when a term is missing. The title counts
 * most, then the path (tab, card), then the help text.
 *
 * @param {Object}   entry Index entry ({ title, path, help, kind }).
 * @param {string[]} words Terms.
 * @return {number} Score.
 */
export function score( entry, words ) {
	const title = normalize( entry.title );
	const path = normalize( entry.path.join( ' ' ) );
	const help = normalize( entry.help || '' );
	let total = entry.kind === 'tab' ? 6 : 0;

	for ( const word of words ) {
		const at = title.indexOf( word );
		if ( at === 0 ) {
			total += 30;
		} else if ( at > 0 ) {
			total += /\s/.test( title.charAt( at - 1 ) ) ? 20 : 12;
		} else if ( path.includes( word ) ) {
			total += 8;
		} else if ( help.includes( word ) ) {
			total += 2;
		} else {
			return -1;
		}
	}

	return total;
}

/**
 * Text split into plain and matched parts, for <mark> highlighting.
 *
 * @param {string}   text  Display text.
 * @param {string[]} words Terms.
 * @return {Array<{text: string, hit: boolean}>} Parts.
 */
export function highlight( text, words ) {
	const plain = normalize( text );
	const hits = [];

	// A single letter matches nearly everything; marking it is only noise.
	words
		.filter( ( word ) => word.length > 1 )
		.forEach( ( word ) => {
			let from = plain.indexOf( word );
			while ( from > -1 ) {
				hits.push( [ from, from + word.length ] );
				from = plain.indexOf( word, from + word.length );
			}
		} );
	hits.sort( ( a, b ) => a[ 0 ] - b[ 0 ] );

	const parts = [];
	let pos = 0;
	hits.forEach( ( [ start, end ] ) => {
		if ( start < pos ) {
			return;
		}
		if ( start > pos ) {
			parts.push( { text: text.slice( pos, start ), hit: false } );
		}
		parts.push( { text: text.slice( start, end ), hit: true } );
		pos = end;
	} );
	if ( pos < text.length ) {
		parts.push( { text: text.slice( pos ), hit: false } );
	}

	return parts;
}
