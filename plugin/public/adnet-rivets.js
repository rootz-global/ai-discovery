/**
 * Adnet Rivets — browser+domain identity and event signing for verified advertising.
 *
 * Loaded only when Adnet is enabled and a publisher wallet is configured (server-gated).
 * The cryptographic key is unique per browser+domain pair — cross-site tracking is
 * structurally impossible because no shared identifier exists across domains.
 *
 * @package Rootz_AI_Discovery
 * @license GPL-2.0-or-later
 */
( function () {
	'use strict';

	if ( ! window.rootzAdnet || ! rootzAdnet.enabled ) {
		return;
	}
	if ( ! window.crypto || ! window.crypto.subtle ) {
		return;
	}
	if ( ! window.indexedDB ) {
		return;
	}
	if ( ! window.IntersectionObserver ) {
		return; // Older browsers — graceful no-op.
	}

	var DB_NAME    = 'rootz-adnet';
	var STORE_NAME = 'keys';
	var KEY_ID     = 'rivets-v1';
	var API_URL    = rootzAdnet.apiUrl;
	var WALLET     = rootzAdnet.publisherWallet;

	/**
	 * Open (or create) the IndexedDB for this origin.
	 *
	 * @param {Function} cb Node-style callback (err, db).
	 */
	function openDb( cb ) {
		var req = indexedDB.open( DB_NAME, 1 );
		req.onupgradeneeded = function ( e ) {
			e.target.result.createObjectStore( STORE_NAME );
		};
		req.onsuccess = function ( e ) {
			cb( null, e.target.result );
		};
		req.onerror = function ( e ) {
			cb( e );
		};
	}

	/**
	 * Retrieve the stored key pair or generate a new one.
	 * Private key is non-extractable — it never leaves the browser.
	 *
	 * @param {IDBDatabase} db
	 * @param {Function}    cb Node-style callback (err, CryptoKeyPair).
	 */
	function getOrCreateKey( db, cb ) {
		var tx    = db.transaction( STORE_NAME, 'readonly' );
		var store = tx.objectStore( STORE_NAME );
		var get   = store.get( KEY_ID );

		get.onsuccess = function ( e ) {
			if ( e.target.result ) {
				cb( null, e.target.result );
				return;
			}
			crypto.subtle.generateKey(
				{ name: 'ECDSA', namedCurve: 'P-256' },
				false, // Non-extractable.
				[ 'sign', 'verify' ]
			).then( function ( kp ) {
				var tx2    = db.transaction( STORE_NAME, 'readwrite' );
				var store2 = tx2.objectStore( STORE_NAME );
				store2.put( kp, KEY_ID );
				tx2.oncomplete = function () {
					cb( null, kp );
				};
				tx2.onerror = function ( ev ) {
					cb( ev );
				};
			} ).catch( cb );
		};

		get.onerror = function ( e ) {
			cb( e );
		};
	}

	/**
	 * Export the public key as a 0x-prefixed hex string (wallet-style address).
	 *
	 * @param {CryptoKeyPair} kp
	 * @param {Function}      cb Node-style callback (err, hexString).
	 */
	function exportPublicKeyHex( kp, cb ) {
		crypto.subtle.exportKey( 'raw', kp.publicKey ).then( function ( raw ) {
			var bytes = new Uint8Array( raw );
			var hex   = Array.from( bytes )
				.map( function ( b ) {
					return b.toString( 16 ).padStart( 2, '0' );
				} )
				.join( '' );
			cb( null, '0x' + hex );
		} ).catch( cb );
	}

	/**
	 * Sign an event payload. Returns a base64url-encoded ECDSA signature.
	 *
	 * @param {CryptoKeyPair} kp
	 * @param {Object}        data   Plain object — JSON-stringified before signing.
	 * @param {Function}      cb     Node-style callback (err, base64urlString).
	 */
	function signEvent( kp, data, cb ) {
		var enc     = new TextEncoder();
		var payload = enc.encode( JSON.stringify( data ) );

		crypto.subtle.sign(
			{ name: 'ECDSA', hash: 'SHA-256' },
			kp.privateKey,
			payload
		).then( function ( sig ) {
			var b64 = btoa( String.fromCharCode.apply( null, new Uint8Array( sig ) ) );
			// Convert standard base64 to base64url.
			cb( null, b64.replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' ) );
		} ).catch( cb );
	}

	/**
	 * IAB MRC frequency cap: max 3 views per campaign per browser per calendar day.
	 * Uses localStorage keyed by campaign so it persists across page loads.
	 *
	 * @param  {string}  campaignId
	 * @return {boolean} true if the view should be counted, false if capped.
	 */
	function checkFrequencyCap( campaignId ) {
		var key     = 'rootz-adnet-fc-' + campaignId;
		var today   = new Date().toISOString().slice( 0, 10 );
		var stored;

		try {
			stored = JSON.parse( localStorage.getItem( key ) || '{}' );
		} catch ( e ) {
			stored = {};
		}

		if ( stored.date !== today ) {
			stored = { date: today, count: 0 };
		}
		if ( stored.count >= 3 ) {
			return false;
		}
		stored.count++;

		try {
			localStorage.setItem( key, JSON.stringify( stored ) );
		} catch ( e ) {
			// Storage full — allow the view anyway.
		}
		return true;
	}

	/**
	 * POST a signed event to the Adnet publisher agent.
	 * Failures are silent — ad events must never break page load.
	 *
	 * @param {string} type        'view' or 'click'.
	 * @param {string} campaignId
	 * @param {string} publicKeyHex
	 * @param {string} signature   base64url ECDSA signature.
	 * @param {Object} payload     The signed payload object.
	 */
	function submitEvent( type, campaignId, publicKeyHex, signature, payload ) {
		fetch( API_URL + '/api/event', {
			method:  'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( {
				type:       type,
				campaignId: campaignId,
				publisher:  WALLET,
				browserKey: publicKeyHex,
				signature:  signature,
				payload:    payload,
				url:        window.location.href,
				timestamp:  Date.now(),
			} ),
		} ).catch( function () {
			// Silent fail.
		} );
	}

	/**
	 * Wire up IntersectionObserver and click tracking for all ad slots on the page.
	 *
	 * @param {CryptoKeyPair} kp
	 * @param {string}        pkHex Public key hex.
	 */
	function wireAdSlots( kp, pkHex ) {
		var slots = document.querySelectorAll( '.rootz-adnet-slot' );
		if ( ! slots.length ) {
			return;
		}

		// IAB MRC viewability: >= 50% in viewport, tab visible, >= 1 second continuous.
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}
					var el         = entry.target;
					var campaignId = el.dataset.campaign;

					if ( ! campaignId || el.dataset.viewed ) {
						return;
					}
					if ( document.visibilityState !== 'visible' ) {
						return;
					}
					if ( entry.intersectionRatio < 0.5 ) {
						return;
					}

					// Wait 1 second, then re-verify conditions before crediting.
					setTimeout( function () {
						if ( document.visibilityState !== 'visible' ) {
							return;
						}
						var rect  = el.getBoundingClientRect();
						var inView = rect.top < window.innerHeight && rect.bottom > 0;
						if ( ! inView ) {
							return;
						}
						if ( ! checkFrequencyCap( campaignId ) ) {
							return;
						}

						el.dataset.viewed = '1';
						observer.unobserve( el );

						var eventData = {
							campaignId: campaignId,
							browserKey: pkHex,
							t:          Date.now(),
						};

						signEvent( kp, eventData, function ( err, sig ) {
							if ( err ) {
								return;
							}
							submitEvent( 'view', campaignId, pkHex, sig, eventData );
						} );
					}, 1000 );
				} );
			},
			{ threshold: 0.5 }
		);

		slots.forEach( function ( slot ) {
			observer.observe( slot );

			// Click tracking — only fires on the anchor inside the slot.
			var link = slot.querySelector( 'a[data-adnet-click]' );
			if ( link ) {
				link.addEventListener( 'click', function () {
					var campaignId = slot.dataset.campaign;
					if ( ! campaignId ) {
						return;
					}
					var clickData = {
						campaignId: campaignId,
						browserKey: pkHex,
						t:          Date.now(),
						href:       link.href,
					};
					signEvent( kp, clickData, function ( err, sig ) {
						if ( err ) {
							return;
						}
						submitEvent( 'click', campaignId, pkHex, sig, clickData );
					} );
				} );
			}
		} );
	}

	// Boot sequence: open DB → get/create key → export public key → wire slots.
	openDb( function ( err, db ) {
		if ( err ) {
			return;
		}
		getOrCreateKey( db, function ( err, kp ) {
			if ( err ) {
				return;
			}
			exportPublicKeyHex( kp, function ( err, pkHex ) {
				if ( err ) {
					return;
				}
				wireAdSlots( kp, pkHex );
			} );
		} );
	} );
} )();
