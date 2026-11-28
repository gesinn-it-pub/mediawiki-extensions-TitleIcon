'use strict';

const SCRIPT = require.resolve( '../../resources/TitleIcon.js' );

/**
 * Runs resources/TitleIcon.js against stand-ins for mw.config and jQuery.
 *
 * @param {Object} configVars the JavaScript configuration variables of the page
 * @return {Object} what the script did: whether it waited for the document and which icons it
 *  inserted
 */
function runScript( configVars ) {
	const result = { waitedForDocument: false, inserted: [] };

	global.mw = {
		config: {
			exists: ( name ) => Object.prototype.hasOwnProperty.call( configVars, name ),
			get: ( name ) => configVars[ name ]
		}
	};
	global.$ = ( argument ) => {
		if ( typeof argument === 'function' ) {
			// the document is ready as soon as the callback is registered
			result.waitedForDocument = true;
			argument();
			return undefined;
		}
		return {
			prepend: ( html ) => {
				result.inserted.push( { selector: argument, html: html } );
			}
		};
	};

	delete require.cache[ SCRIPT ];
	require( SCRIPT );
	return result;
}

QUnit.module( 'TitleIcon' );

QUnit.test( 'inserts the icons in front of the element of the configured selector', ( assert ) => {
	const result = runScript( {
		TitleIconSelector: '#firstHeading',
		TitleIconHTML: '<a href="/wiki/Icon"><img src="/icon.png"></a> '
	} );

	assert.deepEqual( result.inserted, [
		{ selector: '#firstHeading', html: '<a href="/wiki/Icon"><img src="/icon.png"></a> ' }
	] );
} );

QUnit.test( 'waits for the document to be ready', ( assert ) => {
	const result = runScript( { TitleIconSelector: 'h1', TitleIconHTML: 'x' } );

	assert.true( result.waitedForDocument );
} );

QUnit.test( 'uses the selector of the page, not a fixed one', ( assert ) => {
	const result = runScript( { TitleIconSelector: '.custom-title', TitleIconHTML: 'x' } );

	assert.strictEqual( result.inserted[ 0 ].selector, '.custom-title' );
} );

QUnit.test( 'does nothing if the page has no icons', ( assert ) => {
	const result = runScript( { TitleIconSelector: '#firstHeading' } );

	assert.deepEqual( result.inserted, [] );
} );

QUnit.test( 'does nothing if the page has no selector', ( assert ) => {
	const result = runScript( { TitleIconHTML: 'x' } );

	assert.deepEqual( result.inserted, [] );
} );
