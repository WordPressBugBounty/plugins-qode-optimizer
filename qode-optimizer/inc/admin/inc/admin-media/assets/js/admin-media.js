(function ( $ ) {
	'use strict';

	if ( typeof qodefFramework !== 'object' ) {
		window.qodefFramework = {};
	}

	$( document ).ready(
		function () {
			qodefMedia.init();

			if (
				typeof wp != 'undefined' &&
				typeof wp.media != 'undefined' &&
				typeof wp.media.frame != 'undefined'
			) {

				wp.media.frame.on(
					'edit:attachment',
					function ( e ) {

						var $html   = '',
							options = {
								id: e.id,
						};

						qodefMedia.ajaxInitButtonsAndInfoHtml( options )
						.then(
							function ( data ) {
								var response = $.parseJSON( data );

								$html = response.data;

								if ( $html !== '' ) {
									wp.media.frame.$el
										.find( '.settings' )
										.append(
											'<div class="setting qodef-plugin-info" data-setting="qode-optimizer">' +
												'<div class="name">Qode Optimizer</div>' +
												'<div class="value">' + $html + '</div>' +
											'</div>'
										);

									qodefMedia.initAction( wp.media.frame.$el.find( '.qodef-media-action-holder' ) );
								}
							}
						);
					}
				);
			}
		}
	);

	/**
	 * Init Media
	 */
	var qodefMedia = {
		init: function () {
			this.qodefMediaMainTable    = $( '.wp-list-table.media' );
			this.qodefMediaActionHolder = $( '.qodef-media-action-holder' );

			if ( this.qodefMediaActionHolder.length ) {
				this.qodefMediaActionHolder.each(
					function () {
						var $currentActionHolder = $( this );
						qodefMedia.initAction( $currentActionHolder );
					}
				);
			}
		},
		initAction: function ( $actionHolder ) {
			if ( $actionHolder.length ) {
				var $allLinksHolder = $actionHolder.find( '.qodef-media-action-links' ),
					$originalHolder = $actionHolder.find( '.qodef-media-original-results' ),
					$resultsHolder  = $actionHolder.find( '.qodef-media-action-results' ),
					$buttons        = '';

				$actionHolder.on(
					'click',
					'.qodef-media-action-link.qodef-optimize-manual',
					function ( e ) {
						e.preventDefault();
						e.stopPropagation();

						var $currentLink    = $( this ),
							$currentSpinner = $actionHolder.find( '.qodef-spinner-loading' ),
							id              = $currentLink.data( 'id' ),
							qo_nonce        = $currentLink.data( 'qo-nonce' ),
							options         = {
								id: id,
								qo_nonce: qo_nonce,
						};

						$resultsHolder.html( '' );
						$allLinksHolder.find( '.qodef-media-action-link' ).remove();
						$currentSpinner.removeClass( 'qodef-hidden' );

						qodefMedia.ajaxOptimizeProcess( options, $resultsHolder, $currentLink, $currentSpinner )
						.then(
							function () {
								$currentSpinner
									.addClass( 'qodef-hidden' )
									.find( '.qodef-action-label' ).text( '' );

								setTimeout(
									function () {
										$resultsHolder.addClass( 'qodef-init' );
									},
									100
								);

								$buttons = qodefMedia.ajaxButtons(
									options,
									$allLinksHolder
								);
							}
						);
					}
				);

				$actionHolder.on(
					'click',
					'.qodef-media-action-link.qodef-restore-manual',
					function ( e ) {
						e.preventDefault();
						e.stopPropagation();

						var $currentLink    = $( this ),
							$currentSpinner = $actionHolder.find( '.qodef-spinner-loading' ),
							id              = $currentLink.data( 'id' ),
							qo_nonce        = $currentLink.data( 'qo-nonce' ),
							options         = {
								id: id,
								qo_nonce: qo_nonce,
						};

						$resultsHolder.html( '' );
						$allLinksHolder.find( '.qodef-media-action-link' ).remove();
						$currentSpinner.removeClass( 'qodef-hidden' );

						qodefMedia.ajaxRestore( options, $resultsHolder, $currentLink, $currentSpinner )
						.then(
							function () {
								$currentSpinner
									.addClass( 'qodef-hidden' )
									.find( '.qodef-action-label' ).text( '' );

								$buttons = qodefMedia.ajaxButtons( options, $allLinksHolder );
							}
						);
					}
				);

				$actionHolder.on(
					'click',
					'.qodef-media-action-link.qodef-recover-manual',
					function ( e ) {
						e.preventDefault();
						e.stopPropagation();

						var $currentLink    = $( this ),
							$currentSpinner = $actionHolder.find( '.qodef-spinner-loading' ),
							id              = $currentLink.data( 'id' ),
							qo_nonce        = $currentLink.data( 'qo-nonce' ),
							options         = {
								id: id,
								qo_nonce: qo_nonce,
						};

						$resultsHolder.html( '' );
						$allLinksHolder.find( '.qodef-media-action-link' ).remove();
						$currentSpinner.removeClass( 'qodef-hidden' );

						qodefMedia.ajaxRecover( options, $resultsHolder, $currentLink, $currentSpinner )
						.then(
							function () {
								$currentSpinner
									.addClass( 'qodef-hidden' )
									.find( '.qodef-action-label' ).text( '' );

								$buttons = qodefMedia.ajaxButtons( options, $allLinksHolder );
							}
						);
					}
				);

				$actionHolder.on(
					'click',
					'.qodef-media-action-link.qodef-regenerate-manual',
					function ( e ) {
						e.preventDefault();
						e.stopPropagation();

						var $currentLink    = $( this ),
							$currentSpinner = $actionHolder.find( '.qodef-spinner-loading' ),
							id              = $currentLink.data( 'id' ),
							qo_nonce        = $currentLink.data( 'qo-nonce' ),
							options         = {
								id: id,
								qo_nonce: qo_nonce,
						};

						$resultsHolder.html( '' );
						$allLinksHolder.find( '.qodef-media-action-link' ).remove();
						$currentSpinner.removeClass( 'qodef-hidden' );

						qodefMedia.ajaxRegenerate( options, $originalHolder, $resultsHolder, $currentLink, $currentSpinner )
						.then(
							function () {
								$currentSpinner
									.addClass( 'qodef-hidden' )
									.find( '.qodef-action-label' ).text( '' );

								$buttons = qodefMedia.ajaxButtons( options, $allLinksHolder );
							}
						);
					}
				);

				$actionHolder.on(
					'click',
					'.qodef-media-action-link.qodef-add-watermark-manual',
					function ( e ) {
						e.preventDefault();
						e.stopPropagation();

						var $currentLink    = $( this ),
							$currentSpinner = $actionHolder.find( '.qodef-spinner-loading' ),
							id              = $currentLink.data( 'id' ),
							qo_nonce        = $currentLink.data( 'qo-nonce' ),
							options         = {
								id: id,
								qo_nonce: qo_nonce,
						};

						$resultsHolder.html( '' );
						$allLinksHolder.find( '.qodef-media-action-link' ).remove();
						$currentSpinner.removeClass( 'qodef-hidden' );

						qodefMedia.ajaxAddWatermark( options, $originalHolder, $resultsHolder, $currentLink, $currentSpinner )
						.then(
							function () {
								$currentSpinner
									.addClass( 'qodef-hidden' )
									.find( '.qodef-action-label' ).text( '' );

								$buttons = qodefMedia.ajaxButtons( options, $allLinksHolder );
							}
						);
					}
				);
			}
		},

		ajaxShouldBeConverted: function ( options ) {

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_should_be_converted',
						options: options,
					},
				}
			);
		},

		ajaxOptimizeProcess: function ( options, $resultsHolder, $currentLink, $currentSpinner ) {

			$resultsHolder.removeClass( 'qodef-init' );

			$currentSpinner
				.find( '.qodef-action-label' ).text( 'Optimizing...' );

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_optimize_process',
						options: options,
					},
					success: function ( data ) {
						var response                 = $.parseJSON( data ),
							watermarkedIndex         = false,
							watermarkedScaledIndex   = false,
							optimizationIndex        = false,
							optimizationScaledIndex  = false,
							conversionIndex          = false,
							conversionScaledIndex    = false,
							optimization2Index       = false,
							optimization2ScaledIndex = false,
							webpIndex                = false,
							webpScaledIndex          = false;

						if ( response.data.params.watermarked_files.length ) {
							watermarkedIndex       = response.data.params.watermarked_files.map( element => element.params.media_size ).indexOf( 'original' );
							watermarkedScaledIndex = response.data.params.watermarked_files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( watermarkedScaledIndex !== -1 ) {
								watermarkedIndex = watermarkedScaledIndex;
							}
						}

						if ( response.data.params.optimization_files.length ) {
							optimizationIndex       = response.data.params.optimization_files.map( element => element.params.media_size ).indexOf( 'original' );
							optimizationScaledIndex = response.data.params.optimization_files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( optimizationScaledIndex !== -1 ) {
								optimizationIndex = optimizationScaledIndex;
							}
						}

						if ( response.data.params.conversion_files.length ) {
							conversionIndex       = response.data.params.conversion_files.map( element => element.params.media_size ).indexOf( 'original' );
							conversionScaledIndex = response.data.params.conversion_files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( conversionScaledIndex !== -1 ) {
								conversionIndex = conversionScaledIndex;
							}
						}

						if ( response.data.params.optimization2_files.length ) {
							optimization2Index       = response.data.params.optimization2_files.map( element => element.params.media_size ).indexOf( 'original' );
							optimization2ScaledIndex = response.data.params.optimization2_files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( optimization2ScaledIndex !== -1 ) {
								optimization2Index = optimization2ScaledIndex;
							}
						}

						if ( response.data.params.webp_files.length ) {
							webpIndex       = response.data.params.webp_files.map( element => element.params.media_size ).indexOf( 'original' );
							webpScaledIndex = response.data.params.webp_files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( webpScaledIndex !== -1 ) {
								webpIndex = webpScaledIndex;
							}
						}

						if ( response.status === 'success' ) {

							if ( response.data.params.restoration_success ) {
								$resultsHolder.append( '<div><span class="qodef-title">Restoration:</span> <span class="qodef-value">' + response.data.params.restoration_result + '</span></div>' );
							}

							if (
								(
									response.data.params.watermarked_success &&
									watermarkedIndex !== false
								) ||
								(
									response.data.params.optimization_success &&
									optimizationIndex !== false
								) ||
								(
									response.data.params.conversion_success &&
									conversionIndex !== false
								) ||
								(
									response.data.params.optimization2_success &&
									optimization2Index !== false
								)
							) {
								// Watermark.
								if ( response.data.params.watermarked_skipped ) {
									// Just skip.
								} else if (
									response.data.params.watermarked_success &&
									watermarkedIndex !== false
								) {
									$resultsHolder.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_files[watermarkedIndex].params.filesize + ', ' + response.data.params.watermarked_files[watermarkedIndex].params.result + '</span></div>' );
								} else {
									$resultsHolder.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_result + '</span></div>' );
								}

								// Optimization.
								if (
									response.data.params.optimization_success &&
									optimizationIndex !== false
								) {
									$resultsHolder.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_files[optimizationIndex].params.filesize + ', ' + response.data.params.optimization_files[optimizationIndex].params.result + '</span></div>' );
								} else {
									$resultsHolder.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
								}

								// Conversion.
								if ( response.data.params.conversion_skipped ) {
									// Just skip.
								} else if (
									response.data.params.conversion_success &&
									conversionIndex !== false
								) {
									var mainFileNameSpan = qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename .screen-reader-text' ).html();

									qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename' ).html( '<span class="screen-reader-text">' + mainFileNameSpan + '</span>' + response.data.params.conversion_files[conversionIndex].params.file_basename );
									qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .copy button' ).attr(
										'data-clipboard-text',
										response.data.params.conversion_files[conversionIndex].params.url
									);
									qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .download a' ).attr(
										'href',
										response.data.params.conversion_files[conversionIndex].params.url
									);

									$resultsHolder.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_files[conversionIndex].params.filesize + ', ' + response.data.params.conversion_files[conversionIndex].params.result + '</span></div>' );
								} else {
									$resultsHolder.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_result + '</span></div>' );
								}

								// Optimization after Conversion.
								if ( response.data.params.optimization2_skipped ) {
									// Just skip.
								} else if (
									response.data.params.optimization2_success &&
									optimization2Index !== false
								) {
									$resultsHolder.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_files[optimization2Index].params.filesize + ', ' + response.data.params.optimization2_files[optimization2Index].params.result + '</span></div>' );
								} else {
									$resultsHolder.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_result + '</span></div>' );
								}
							}

							if ( response.data.params.webp_skipped ) {
								// Just skip.
							} else if (
								response.data.params.webp_success &&
								webpIndex !== false
							) {
								$resultsHolder.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_files[webpIndex].params.filesize + ', ' + response.data.params.webp_files[webpIndex].params.result + '</span></div>' );
							} else {
								$resultsHolder.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_result + '</span></div>' );
							}
						} else {
							$resultsHolder.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							$resultsHolder.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}
					},
					complete: function () {
						console.log( 'Optimization finished!' );
					}
				}
			);
		},

		ajaxRestore: function ( options, $resultsHolder, $currentLink, $currentSpinner ) {

			$currentSpinner
				.find( '.qodef-action-label' ).text( 'Restoring...' );

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_restore',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {
							$resultsHolder.append( '<div><span class="qodef-title">Restoration:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );

							var mainFileNameSpan = qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename .screen-reader-text' ).html();

							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename' ).html( '<span class="screen-reader-text">' + mainFileNameSpan + '</span>' + response.data.params.data.file_basename );
							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .copy button' ).attr(
								'data-clipboard-text',
								response.data.params.data.url
							);
							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .download a' ).attr(
								'href',
								response.data.params.data.url
							);
						} else {
							$resultsHolder.append( '<div><span class="qodef-title">Restoration:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							$resultsHolder.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}
					},
					complete: function () {
						console.log( 'Restoration Finished!' );
					}
				}
			);
		},

		ajaxRegenerate: function ( options, $originalHolder, $resultsHolder, $currentLink, $currentSpinner ) {

			$currentSpinner
				.find( '.qodef-action-label' ).text( 'Regenerating...' );

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_regenerate',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {
							$originalHolder.find( '.qodef-value' ).html( response.data.params.filesize );
							$resultsHolder.append( '<div><span class="qodef-title">Regeneration:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
						} else {
							$resultsHolder.append( '<div><span class="qodef-title">Regeneration:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							$resultsHolder.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}
					},
					complete: function () {
						console.log( 'Regeneration Finished!' );
					}
				}
			);
		},

		ajaxAddWatermark: function ( options, $originalHolder, $resultsHolder, $currentLink, $currentSpinner ) {

			$currentSpinner
				.find( '.qodef-action-label' ).text( 'Adding Watermark...' );

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_add_watermark',
						options: options,
					},
					success: function ( data ) {
						var response    = $.parseJSON( data ),
							index       = false,
							scaledIndex = false;

						if ( response.data.params.files.length ) {
							index       = response.data.params.files.map( element => element.params.media_size ).indexOf( 'original' );
							scaledIndex = response.data.params.files.map( element => element.params.media_size ).indexOf( 'scaled' );

							if ( scaledIndex !== -1 ) {
								index = scaledIndex;
							}
						}

						if ( response.status === 'success' ) {
							if (
								response.data.params.success &&
								index !== false
							) {
								$resultsHolder.append( '<div><span class="qodef-title">Watermarking:</span> <span class="qodef-value">' + response.data.params.files[index].params.filesize + ', ' + response.data.params.files[index].params.result + '</span></div>' );
							} else {
								$resultsHolder.append( '<div><span class="qodef-title">Watermarking:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
							}
						} else {
							$resultsHolder.append( '<div><span class="qodef-title">Watermarking:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							$resultsHolder.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}
					},
					complete: function () {
						console.log( 'Adding Watermark Finished!' );
					}
				}
			);
		},

		ajaxRecover: function ( options, $resultsHolder, $currentLink, $currentSpinner ) {

			$currentSpinner
				.find( '.qodef-action-label' ).text( 'Trying to recover...' );

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_action_recover',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {
							$resultsHolder.append( '<div><span class="qodef-title">Recover:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );

							var mainFileNameSpan = qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename .screen-reader-text' ).html();

							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .filename' ).html( '<span class="screen-reader-text">' + mainFileNameSpan + '</span>' + response.data.params.data.file_basename );
							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .copy button' ).attr(
								'data-clipboard-text',
								response.data.params.data.url
							);
							qodefMedia.qodefMediaMainTable.find( '#post-' + options.id + ' .column-title .row-actions .download a' ).attr(
								'href',
								response.data.params.data.url
							);
						} else {
							$resultsHolder.append( '<div><span class="qodef-title">Recover:</span> <span class="qodef-value">' + response.data.params.result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							$resultsHolder.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}
					},
					complete: function () {
						console.log( 'Recover Finished!' );
					}
				}
			);
		},

		ajaxInitButtonsAndInfoHtml: function ( options ) {
			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_init_action_buttons_and_info',
						options: options,
					},
				}
			);
		},

		ajaxButtons: function ( options, $allLinksHolder ) {

			return $.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'media_include_action_buttons',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {
							$allLinksHolder.prepend( response.data );
						}
					}
				}
			);
		},
	};

	qodefFramework.qodefMedia = qodefMedia;

})( jQuery );
