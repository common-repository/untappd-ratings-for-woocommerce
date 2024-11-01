/*!
 * Untappd Ratings for WooCommerce
 * https://github.com/ChillCode/untappd-ratings-for-woocommerce
 *
 *
 * Copyright (C) 2024 ChillCode
 *
 * @license Released under the General Public License v3.0 https://www.gnu.org/licenses/gpl-3.0.html
 *
 */
(function ($) {
	/**
	 * Append an untappd map.
	 *
	 * @param {Array} options
	 */
	$.fn.UntappdMap = function (options) {
		const mapFeedInfowindows = [];
		const mapFeedPluginName = 'Untappd Ratings for WooCommerce';
		const mapFeedErrorHead = '[' + mapFeedPluginName + '] ';

		let map = null;

		const mapFeedOptions = $.extend(
			{
				mapZoom: '4',
				mapHeight: '500',
				mapWidth: '640',
				mapCentermap: 'yes',
				mapCenterlat: '34.2346598',
				mapCenterlng: '-77.9482096',
				mapUseicon: 1,
				mapUseurlicon: '',
				mapType: 'interactive',
			},
			$(this).data()
		);

		/**
		 * Check required settings.
		 *
		 * @return {boolean} True on success, false otherwise.
		 */
		function checkconfig() {
			if (typeof google !== 'object' || typeof google.maps !== 'object') {
				showErrorMessage(
					'Google Maps Dynamic Library loader not present.'
				);
				return false;
			}

			if (!options.map_ajax_url) {
				showErrorMessage(
					'Endpoint URL is required to initialize the map, check configuration.'
				);
				return false;
			}

			if (!options.map_api_key) {
				showErrorMessage(
					'Google Javascript API key is required to initialize the map, check configuration.'
				);
				return false;
			}

			if (!options.map_nonce) {
				showErrorMessage(
					'Invalid security nonce, ajax calls will not load data.'
				);
				return false;
			}

			mapFeedOptions.mapApikey = options.map_api_key;
			mapFeedOptions.mapLang = options.map_lang;
			mapFeedOptions.mapAjaxurl = options.map_ajax_url;
			mapFeedOptions.mapNonce = options.map_nonce;

			return true;
		}

		/**
		 * Show overlay.
		 */
		function toggleOverlay() {
			$('.urwc-map-loading-overlay-' + mapFeedOptions.mapUniqid).toggle();
			$('.urwc-map-loading-content-' + mapFeedOptions.mapUniqid).toggle();
		}

		/**
		 * Show error message on map.
		 *
		 * @param {string} message
		 */
		function showErrorMessage(message) {
			$('.urwc-map-loading-overlay-' + mapFeedOptions.mapUniqid).css({
				display: 'block',
			});
			$('.urwc-map-loading-content-' + mapFeedOptions.mapUniqid)
				.css({ display: 'block', background: 'none' })
				.text(mapFeedErrorHead + message);
		}

		/**
		 * Load remote data.
		 */
		function loadData() {
			toggleOverlay();

			const request = $.ajax({
				url: mapFeedOptions.mapAjaxurl,
				dataType: 'json',
				type: 'GET',
				data: {
					action: 'urwc_map_feed',
					security: mapFeedOptions.mapNonce,
					scid: mapFeedOptions.mapScid,
				},
			});

			request.done(function (data) {
				if (data.error) {
					showErrorMessage(data.error);
				} else {
					toggleOverlay();
					if (mapFeedOptions.mapType === 'static') {
						loadStaticMap(data);
					} else if (mapFeedOptions.mapType === 'interactive') {
						loadInteractiveMap(data);
					} else {
						showErrorMessage(
							'Invalid map type, valid options are static or interactive (default).'
						);
					}
				}
			});

			request.fail(function (jqXHR, textStatus) {
				showErrorMessage(textStatus);
			});
		}

		/**
		 * Load an interactive map using The Google Maps JavaScript API.
		 *
		 * Multiple interactive maps can be added to same page but all maps will share the same language and API key
		 * since we can only add one Google Map library per page.
		 *
		 * The first shortcode API key and language detected will be used.
		 * @param {*} checkins
		 */
		async function loadInteractiveMap(checkins) {
			const { Map } = await google.maps.importLibrary('maps');

			const myLatLng = new google.maps.LatLng(
				mapFeedOptions.mapCenterlat,
				mapFeedOptions.mapCenterlng
			);

			const mapOptions = {
				zoom: mapFeedOptions.mapZoom,
				center: myLatLng,
				scrollwheel: false,
				scaleControl: true,
				zoomControl: true,
				zoomControlOptions: {
					position: google.maps.ControlPosition.RIGHT_CENTER,
				},
				streetViewControl: true,
				mapTypeControl: true,
			};

			const mapElement = document.getElementById(mapFeedOptions.mapId);

			if (mapElement) {
				map = new Map(mapElement, mapOptions);

				if (mapFeedOptions.center_map === 'yes') {
					map.setCenter(myLatLng);
				}

				for (const checkin in checkins) {
					if (
						Object.prototype.hasOwnProperty.call(checkins, checkin)
					) {
						addMarker(checkins[checkin]);
					}
				}
			} else {
				showErrorMessage(
					'Element is not present in the current DOM, map could not be initialized.'
				);
			}
		}

		/**
		 * Add marker and infoWindow to map.
		 *
		 * @param {Object} checkin
		 */
		function addMarker(checkin) {
			let markerLatLng = {};
			let markerTitle = '';
			const markerCheckins = [];

			/**
			 * Prepare checkins by venue for infoWindow pagination.
			 */
			for (const property in checkin) {
				if (Object.prototype.hasOwnProperty.call(checkin, property)) {
					if (!checkin[property].lat || !checkin[property].lng) {
						return false;
					}

					/**
					 * Set global lat|lng.
					 */
					markerLatLng = {
						lat: checkin[property].lat,
						lng: checkin[property].lng,
					};

					/**
					 * Set global title.
					 */
					markerTitle = checkin[property].venue_name;

					/**
					 * Add checkin to pagination array.
					 */
					markerCheckins.push({
						data: checkin[property],
						html: getHtmlInfoWindow(checkin[property]),
					});
				}
			}

			/**
			 * Set marker icon.
			 *
			 * If use_icon is set google map mark will use an Untappd icon.
			 * If use_url_icon is set google map mark will use an icon located at the url provided.
			 * If none above are set google map mark will use default mark icon.
			 */
			let markerIcon;

			if (mapFeedOptions.mapUseicon) {
				markerIcon = {
					path: 'm24.145 3.2668c-4.9298 9.8904-5.2263 9.421-5.4302 10.7l-0.32123 2.0263c-0.11747 0.74134-0.40772 1.4518-0.84633 2.0634l-9.1986 12.837c-0.4695 0.65483-1.2602 1.0008-2.0633 0.90194-2.4896-0.30888-4.8062-1.9892-5.8873-4.2317-0.35213-0.72897-0.27799-1.5938 0.1915-2.2487l9.1986-12.843c0.43861-0.61161 1.0131-1.112 1.6803-1.4641l1.8101-0.95752c1.1429-0.60543 0.59925-0.73515 8.3769-8.5808 0.06193-0.29653 0.06193-0.45096 0.22231-0.49422 0.18544-0.043224 0.40774-0.061934 0.38919-0.28415l-0.02534-0.2842c-0.01267-0.11747 0.08029-0.22232 0.19782-0.22232 0.278-0.004737 0.81546 0.074013 1.5815 0.61778 0.75985 0.54981 1.0131 1.0379 1.0934 1.3035 0.03718 0.1112-0.03718 0.22864-0.14829 0.25946l-0.27802 0.067974c-0.20993 0.05554-0.15435 0.27181-0.14198 0.45714 0.0047 0.17306-0.14199 0.22232-0.40154 0.37684zm-10.576-0.83398c0.20994 0.05554 0.15436 0.27181 0.14198 0.45714-0.01267 0.16675 0.1296 0.21626 0.39538 0.37067 0.48804 0.98225 0.94518 1.8842 1.3714 2.7182 0.04322 0.08029 0.14198 0.092724 0.20387 0.03079 0.69189-0.74134 1.5197-1.6186 2.502-2.6317 0.08029-0.086448 0.08645-0.21626 0.0047-0.30271-0.49416-0.50658-1.0193-1.044-1.5814-1.6124-0.061934-0.29036-0.061934-0.45098-0.22231-0.49423-0.18544-0.0495-0.40774-0.061934-0.38919-0.28416 0.017644-0.20389 0.086447-0.5004-0.17305-0.50657-0.27801-0.0047368-0.81546 0.067974-1.5815 0.61778-0.75985 0.54981-1.0131 1.0378-1.0934 1.3035-0.08645 0.25946 0.22232 0.28416 0.42009 0.3336zm24.087 22.876-9.1924-12.843c-0.81546-1.1429-1.6433-1.4456-3.4842-2.4154-0.69191-0.36451-0.87724-0.67338-1.8842-1.7854-0.06193-0.067974-0.17912-0.05554-0.22232 0.03079-2.8603 5.4858-2.9097 5.1151-3.0271 5.8565-0.10516 0.66099-0.08029 1.2355 0.01764 1.8409 0.11747 0.74134 0.40772 1.4518 0.84634 2.0634l9.1986 12.843c0.46953 0.65483 1.2479 1.0008 2.0448 0.9081 2.4896-0.30268 4.8186-1.9769 5.912-4.2379 0.3336-0.73515 0.26564-1.6-0.20995-2.261z',
					fillColor: '#2ad2c5',
					fillOpacity: 1,
					strokeWeight: 0.5,
					strokeColor: '#000000',
					size: new google.maps.Size(38, 32),
					origin: new google.maps.Point(0, 0),
					anchor: new google.maps.Point(19, 0),
				};
			} else if (mapFeedOptions.mapUseUrlIcon) {
				markerIcon = { url: mapFeedOptions.mapUseUrlIcon };
			}

			/**
			 * Create the marker.
			 *
			 * TODO: Migrate to AdvancedMarkerElement and PinElement.
			 *
			 * https://developers.google.com/maps/documentation/javascript/advanced-markers/graphic-markers#place-icon-glyph
			 *
			 */
			const marker = new google.maps.Marker({
				position: markerLatLng,
				map,
				title: markerTitle,
				icon: markerIcon,
			});

			let markerInfoWindowDisclaimer = '';

			if (options.map_show_infowindow_disclaimer === 'yes') {
				markerInfoWindowDisclaimer = $('<div>')
					.attr({ class: 'urwc-map-infowindow-disclaimer' })
					.append(
						$('<a>')
							.attr({
								rel: 'noopener noreferer nofollow',
								target: '_blank',
								href: 'https://help.untappd.com/hc/en-us/articles/6166329931540-How-to-report-a-checkin-account-photo-or-comment',
							})
							.text(options.map_i18n[7])
					);
			}

			const markerInfoWindowPaginationContainer = $('<div>').attr({
				class:
					'urwc-map-infowindow-pagination-container-' +
					mapFeedOptions.mapUniqid,
			});

			const markerInfoWindowContainer = $('<div>').attr({
				class:
					'urwc-map-infowindow-container-' + mapFeedOptions.mapUniqid,
			});

			/**
			 * Create the InfoWindow to handle the checkin data.
			 */
			const markerInfowindow = new google.maps.InfoWindow({
				content: $('<div>').append(
					markerInfoWindowContainer,
					markerInfoWindowPaginationContainer,
					markerInfoWindowDisclaimer
				)[0].innerHTML,
				maxWidth: 380,
				ariaLabel: markerTitle,
			});

			/**
			 * When InfoWindow is ready add marker checkins.
			 */
			google.maps.event.addListener(
				markerInfowindow,
				'domready',
				function () {
					/**
					 * If more than one chekin per marker is found add pagination to InfoWindow.
					 */
					if (markerCheckins.length > 1) {
						$(
							'.urwc-map-infowindow-pagination-container-' +
								mapFeedOptions.mapUniqid
						).pagination({
							dataSource: markerCheckins,
							pageSize: 1,
							autoHidePrevious: false,
							autoHideNext: false,
							showPageNumbers: false,
							showNavigator: true,
							showGoButton: false,
							showGoInput: true,
							formatNavigator:
								'<%= currentPage %>/<%= totalPage %>',
							callback: function (data) {
								$(
									'.urwc-map-infowindow-container-' +
										mapFeedOptions.mapUniqid
								).html(data[0].html);
							},
						});
					} else {
						/**
						 * Add single chekin to InfoWindow.
						 */
						$(
							'.urwc-map-infowindow-container-' +
								mapFeedOptions.mapUniqid
						).html($(markerCheckins[0].html));
					}
				}
			);

			/**
			 * When marker is clicked show a single InfoWindow.
			 */
			google.maps.event.addListener(
				marker,
				'click',
				(function (theMarker) {
					return function () {
						for (
							let i = 0, len = mapFeedInfowindows.length;
							i < len;
							i++
						) {
							mapFeedInfowindows[i].close();
						}

						if (
							mapFeedInfowindows.indexOf(markerInfowindow) === -1
						) {
							mapFeedInfowindows.push(markerInfowindow);
						}

						markerInfowindow.open(map, theMarker);
					};
				})(marker)
			);
		}

		/**
		 * Parse checkin into infowindow HTML.
		 *
		 * All HTML data is securely generated by jQuery, and external data is only set using .text().
		 *
		 * @param {Object} infoWindowCheckin Checkin data.
		 * @return {Object} jQuery object.
		 */
		function getHtmlInfoWindow(infoWindowCheckin) {
			/**
			 * Set location data.
			 */
			let infoWindowCheckinLocationData = '';

			if (infoWindowCheckin.location) {
				infoWindowCheckin.location =
					'(' + infoWindowCheckin.location + ')';

				infoWindowCheckinLocationData = $('<a>')
					.attr({
						rel: 'noopener noreferer nofollow',
						target: '_blank',
						href:
							'https://www.google.com/maps/search/?api=1&query=' +
							infoWindowCheckin.location,
					})
					.text(infoWindowCheckin.location);
			}

			/**
			 * Set rating score.
			 */
			let infoWindowCheckinRatingScore = '';

			if (infoWindowCheckin.rating_score) {
				infoWindowCheckinRatingScore = $('<div>')
					.attr({ class: 'urwc-map-infowindow-checkin-rating' })
					.append($('<h5>').text(options.map_i18n[2] + ':'))
					.append(
						$('<p>').html(
							$('<b>').text(
								infoWindowCheckin.rating_score +
									'/' +
									options.map_i18n[6]
							)
						)
					);
			}

			/**
			 * Set comment.
			 */
			let infoWindowCheckinComment = '';

			if (infoWindowCheckin.comment) {
				infoWindowCheckinComment = $('<div>')
					.attr({ class: 'urwc-map-infowindow-checkin-comment' })
					.append($('<h5>').text(options.map_i18n[1] + ':'))
					.append(
						$('<p>').html($('<b>').text(infoWindowCheckin.comment))
					);
			}

			/**
			 * Set venue data.
			 */
			let infoWindowCheckinDataVenue = '';

			if (infoWindowCheckin.venue_name) {
				if (
					infoWindowCheckin.foursquare_url &&
					infoWindowCheckin.venue_name !== 'Untappd at Home'
				) {
					infoWindowCheckinDataVenue = $('<a>')
						.attr({
							style: 'text-decoration:underline',
							rel: 'noopener noreferer nofollow',
							target: '_blank',
							href: infoWindowCheckin.foursquare_url,
						})
						.text(infoWindowCheckin.venue_name);
				} else {
					infoWindowCheckinDataVenue = $('<b>').text(
						infoWindowCheckin.venue_name
					);
				}
			}

			/**
			 * Set product link.
			 */
			let infoWindowCheckinProductLink = '';

			if (infoWindowCheckin.permalink) {
				infoWindowCheckinProductLink = $('<a>')
					.attr({
						style: 'display:block;width:100%;',
						target: '_blank',
						rel: 'noopener noreferer nofollow',
						href: infoWindowCheckin.permalink,
					})
					.text(options.map_i18n[5]);

				infoWindowCheckinProductLink = $('<div>').append(
					$('<p>')
						.attr({
							class: 'button product_type_simple add_to_cart_button ajax_add_to_cart urwc-map-infowindow-checkin-productlink',
						})
						.html(infoWindowCheckinProductLink)
				);
			}

			/**
			 * Prepare title.
			 */
			const infoWindowTitle = $('<div>')
				.attr({ class: 'urwc-map-infowindow-checkin-title' })
				.append($('<h5>').text(infoWindowCheckin.beer_name));

			/**
			 * Prepare image.
			 */
			const infoWindowImage = $('<div>')
				.attr({ class: 'urwc-map-infowindow-checkin-img' })
				.append(
					$('<img>').attr({
						loading: 'lazy',
						alt: infoWindowCheckin.beer_name,
						src: infoWindowCheckin.beer_label,
					})
				);

			/**
			 * Prepare description.
			 */
			const infoWindowDesc = $('<div>')
				.attr({ class: 'urwc-map-infoWindow-checkin-info' })
				.append($('<b>').text(infoWindowCheckin.user_name + ' '))
				.append(
					$('<b>').html(function () {
						return infoWindowCheckinLocationData;
					})
				)
				.append($('<span>').text(' ' + options.map_i18n[3] + ' '))
				.append($('<span>').text(infoWindowCheckin.beer_name))
				.append($('<span>').text(' ' + options.map_i18n[4] + ' '))
				.append(infoWindowCheckinDataVenue)
				.append(
					$('<span>').text(
						' ' +
							options.map_i18n[0] +
							' ' +
							infoWindowCheckin.checkin_date
					)
				)
				.append(infoWindowCheckinProductLink)
				.append(infoWindowCheckinRatingScore)
				.append(infoWindowCheckinComment);

			/**
			 * Prepare output.
			 */
			const infoWindowData = $('<div>')
				.attr({ class: 'urwc-map-infowindow-content' })
				.append(infoWindowTitle)
				.append(infoWindowImage)
				.append(infoWindowDesc);

			return infoWindowData;
		}

		/**
		 * Load static map with markers.
		 *
		 * Multiple static maps can be added with multiple api keys
		 * since we only add an img with no javascript involved.
		 *
		 * @param {Array<Object>} checkins Checkins to load on to the map.
		 * @return {void}
		 */
		function loadStaticMap(checkins) {
			const mapElementContainer = document.getElementById(
				mapFeedOptions.mapId
			);

			if (!mapElementContainer) {
				showErrorMessage(
					'Element is not present in the current DOM, static map could not be initialized.'
				);
				return false;
			}

			/**
			 * Center map to given coordinates.
			 */
			let mapCenterLatLng = [];

			if (mapFeedOptions.mapCentermap === 'yes') {
				mapCenterLatLng = [
					mapFeedOptions.mapCenterlat,
					mapFeedOptions.mapCenterlng,
				];
			}

			/**
			 * Icon options.
			 */
			let mapMarkers = 'size:small|color:0x2ad2c5|';

			if (mapFeedOptions.mapUseurlicon) {
				mapMarkers = 'icon:' + mapFeedOptions.mapUseurlicon + '|';
			}

			/**
			 * Concatenate string with all markers lat,long|lat,long.
			 */
			for (const checkin in checkins) {
				if (Object.prototype.hasOwnProperty.call(checkins, checkin)) {
					for (const property in checkins[checkin]) {
						if (
							Object.prototype.hasOwnProperty.call(
								checkins[checkin],
								property
							)
						) {
							if (
								!checkins[checkin][property].lat ||
								!checkins[checkin][property].lng
							) {
								return false;
							}

							// Center map to latest checkin lat/lng to prevent centering to a coordinates not showing markers.
							if (mapFeedOptions.mapCentermap !== 'yes') {
								mapCenterLatLng = [
									checkins[checkin][property].lat,
									checkins[checkin][property].lng,
								];
							}

							mapMarkers =
								mapMarkers +
								checkins[checkin][property].lat +
								',' +
								checkins[checkin][property].lng +
								'|'; // Using URLSearchParams so no need to escape %7C. https://developers.google.com/maps/documentation/maps-static/static-web-api-best-practices

							break;
						}
					}
				}
			}

			/**
			 * Prepare Urls params to generate static map.
			 */
			const mapImgSrcParams = new URLSearchParams();

			mapImgSrcParams.set('center', mapCenterLatLng.toString());
			if (mapFeedOptions.mapLang) {
				mapImgSrcParams.set('language', mapFeedOptions.mapLang);
			}

			mapImgSrcParams.set('zoom', mapFeedOptions.mapZoom);
			mapImgSrcParams.set(
				'size',
				parseInt(mapFeedOptions.mapWidth) +
					'x' +
					parseInt(mapFeedOptions.mapHeight)
			);
			mapImgSrcParams.set('markers', mapMarkers);
			mapImgSrcParams.set('scale', 1);
			mapImgSrcParams.set('key', mapFeedOptions.mapApikey);

			const mapImgSrc =
				'<img src="https://maps.googleapis.com/maps/api/staticmap?' +
				mapImgSrcParams +
				'">';

			mapElementContainer.innerHTML = mapImgSrc;

			return true;
		}

		if (checkconfig()) {
			return this.each(function () {
				loadData();
			});
		}
		return false;
	};
})(jQuery);
