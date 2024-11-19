(function ( $ ) {
	'use strict';

	if ( typeof qodefFramework !== 'object' ) {
		window.qodefFramework = {};
	}

	qodefFramework.scroll       = 0;
	qodefFramework.windowWidth  = $( window ).width();
	qodefFramework.windowHeight = $( window ).height();

	$( document ).ready(
		function () {
			qodefOptimization.init();
		}
	);

	var qodefOptimization = {
		init: function () {
			this.mainForm                 = $( '#qodef-bulk-start' );
			this.mainFormParams           = typeof this.mainForm.data( 'params' ) !== 'undefined' ? this.mainForm.data( 'params' ) : {};
			this.allOptions               = $( 'input[type="radio"][name="bulk_option[]"], input[type="radio"][name="bulk_folders_option[]"]' );
			this.option                   = $( 'input[type="radio"][name="bulk_option[]"]' );
			this.checkedOption            = $( 'input[type="radio"][name="bulk_option[]"]:checked' );
			this.optionValue              = this.checkedOption.length ? this.checkedOption.val() : 'none';
			this.forceOptimization        = $( 'input[type="checkbox"][name="bulk_force_optimization"]' );
			this.ids                      = this.mainFormParams['media'][this.optionValue]['ids'];
			this.idsCount                 = this.mainFormParams['media'][this.optionValue]['count'];
			this.foldersOption            = $( 'input[type="radio"][name="bulk_folders_option[]"]' );
			this.checkedFoldersOption     = $( 'input[type="radio"][name="bulk_folders_option[]"]:checked' );
			this.foldersOptionValue       = this.checkedFoldersOption.length ? this.checkedFoldersOption.val() : 'none';
			this.foldersForceOptimization = $( 'input[type="checkbox"][name="bulk_folders_force_optimization"]' );
			this.paths                    = this.mainFormParams['folders'][this.foldersOptionValue]['paths'];
			this.pathsCount               = this.mainFormParams['folders'][this.foldersOptionValue]['count'];
			this.mainFormSubmit           = $( '#qodef-bulk-start' ).find( 'input[type="submit"]' );
			this.loading                  = $( '#qodef-bulk-loading .qodef-spinner-loading' );
			this.message                  = $( '#qodef-bulk-loading .qodef-message' );
			this.progressBar              = $( '#qodef-bulk-progressbar' );
			this.progressBarMaxCount      = this.progressBar.length && typeof this.progressBar.data( 'max' ) !== 'undefined'
				? parseInt(
					this.progressBar.data( 'max' ),
					10
				) : 0;
			this.prograssBarCounter       = 0;
			this.isProgressBarActive      = false;
			this.counter                  = $( '#qodef-bulk-counter' );
			this.resultsHolder            = $( '#qodef-bulk-results' );

			if ( this.mainForm.length ) {
				qodefOptimization.initAction( this.mainForm );
			}
		},
		initAction: function ( $form ) {
			if ( $form.length ) {

				qodefOptimization.allOptions.on(
					'change',
					function () {
						var option        = $( 'input[type="radio"][name="bulk_option[]"]:checked' ).val(),
							foldersOption = $( 'input[type="radio"][name="bulk_folders_option[]"]:checked' ).val();

						qodefOptimization.ids      = qodefOptimization.mainFormParams['media'][option]['ids'];
						qodefOptimization.idsCount = parseInt( qodefOptimization.mainFormParams['media'][option]['count'], 10 );
						if ( isNaN( qodefOptimization.idsCount ) ) {
							qodefOptimization.idsCount = 0;
						}

						qodefOptimization.paths      = qodefOptimization.mainFormParams['folders'][foldersOption]['paths'];
						qodefOptimization.pathsCount = parseInt( qodefOptimization.mainFormParams['folders'][foldersOption]['count'], 10 );
						if ( isNaN( qodefOptimization.pathsCount ) ) {
							qodefOptimization.pathsCount = 0;
						}

						qodefOptimization.progressBarMaxCount = qodefOptimization.idsCount + qodefOptimization.pathsCount;

						qodefOptimization.progressBar.prop( 'data-max', qodefOptimization.progressBarMaxCount );
						qodefOptimization.counter.find( 'span.qodef-max' ).text( qodefOptimization.progressBarMaxCount );
						if ( qodefOptimization.progressBarMaxCount > 0 ) {
							qodefOptimization.mainFormSubmit.prop(
								'disabled',
								false
							);
						} else {
							qodefOptimization.mainFormSubmit.prop(
								'disabled',
								true
							);
						}
					}
				);

				$form.submit(
					function (e) {
						e.preventDefault();

						var formSubmitTrigger = document.activeElement.name;

						if ( qodefOptimization.progressBarMaxCount > 0 ) {

							qodefOptimization.mainFormSubmit.prop(
								'disabled',
								true
							);
							qodefOptimization.loading.removeClass( 'qodef-hidden' );
							qodefOptimization.message.addClass( 'qodef-hidden' );
							qodefOptimization.resultsHolder
							.html( '' )
							.removeClass( 'qodef-hidden' );

							if ( qodefOptimization.progressBar.length ) {
								if ( isNaN( qodefOptimization.progressBarMaxCount ) ) {
									qodefOptimization.progressBarMaxCount = 0;
								}

								if ( qodefOptimization.isProgressBarActive ) {
									qodefOptimization.progressBar.progressbar( 'destroy' );
									qodefOptimization.prograssBarCounter = 0;
									qodefOptimization.counter.find( 'span.qodef-current' ).text( qodefOptimization.prograssBarCounter );
									qodefOptimization.isProgressBarActive = false;
								}

								qodefOptimization.progressBar
								.removeClass( 'qodef-hidden' )
								.progressbar(
									{
										max: qodefOptimization.progressBarMaxCount,
										value: qodefOptimization.prograssBarCounter,
										create: function ( event, ui ) {
											qodefOptimization.isProgressBarActive = true;
										}
									}
								);
								qodefOptimization.counter
								.removeClass( 'qodef-hidden' );

								$( document ).on(
									'ajaxStop',
									function () {
										qodefOptimization.mainFormSubmit.prop(
											'disabled',
											false
										);
										qodefOptimization.loading.addClass( 'qodef-hidden' );
										qodefOptimization.message.removeClass( 'qodef-hidden' );
									}
								);
							}
							var ids      = qodefOptimization.ids,
								paths	 = qodefOptimization.paths,
								qo_nonce = $form.data( 'qo-nonce' ),
								options  = {
									qo_nonce: qo_nonce,
									force_optimization: qodefOptimization.forceOptimization.length && qodefOptimization.forceOptimization.is( ':checked' )
										? 'yes'
										: 'no',
							};

							Object.values( ids ).map(
								function ( element ) {
									element = parseInt( element, 10 );

									if ( ! isNaN( element ) ) {
										options.id = element;

										if ( formSubmitTrigger === 'bulk_optimization' ) {
											qodefOptimization.ajaxOptimizeAndWebp( options );
										}
									}
								}
							);

							Object.values( paths ).map(
								function ( element ) {
									if ( '' !== element ) {
										options.path = element;

										if ( formSubmitTrigger === 'bulk_optimization' ) {
											qodefOptimization.ajaxFoldersOptimizeAndWebp( options );
										}
									}
								}
							);
						}
					}
				);
			}
		},

		randomNumberGenerator: function ( min = 10000, max = 99999 ) {
			min = parseInt( min, 10 );
			max = parseInt( max, 10 );
			if ( isNaN( min ) ) {
				min = 10000;
			}
			if ( isNaN( max ) ) {
				max = 99999;
			}

			return Math.floor( Math.random() * (max - min + 1) ) + min;
		},

		ajaxConvertOptions: function ( options ) {
			var convertOptions = {};

			$.ajax(
				{
					type: 'POST',
					async: false,
					url: ajaxurl,
					data: {
						action: 'options_action_get_convert_options',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {
							convertOptions = response.data;
						}
					}
				}
			);

			return convertOptions;
		},

		ajaxOptimizeAndWebp: function ( options ) {

			var randomNumber = qodefOptimization.randomNumberGenerator(),
				currentResultItem;

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'bulk_action_optimize_and_webp',
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

						qodefOptimization.resultsHolder.prepend( '<div class="qodef-result qodef-item-' + randomNumber + ' qodef-hidden"></div>' );
						currentResultItem = qodefOptimization.resultsHolder.find( '.qodef-result.qodef-item-' + randomNumber );

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

							if ( response.data.params.optimization_skipped ) {
								currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
								currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Media Library</span></div>' );
								currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
							} else {

								if ( response.data.params.restoration_success ) {
									currentResultItem.append( '<div><span class="qodef-title">Restoration:</span> <span class="qodef-value">' + response.data.params.restoration_result + '</span></div>' );
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
									if (
										response.data.params.conversion_success &&
										conversionIndex !== false
									) {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.conversion_files[conversionIndex].params.file_basename + '</span></div>' );
									} else if (
										response.data.params.optimization_success &&
										optimizationIndex !== false
									) {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.optimization_files[optimizationIndex].params.file_basename + '</span></div>' );
									} else {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
									}

									currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Media Library</span></div>' );

									if (
										response.data.params.watermarked_success &&
										watermarkedIndex !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.watermarked_files[watermarkedIndex].params.initial_size + '</span></div>' );
									} else if (
										response.data.params.optimization_success &&
										optimizationIndex !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.optimization_files[optimizationIndex].params.initial_size + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.initial_size + '</span></div>' );
									}

									// Watermark.
									if ( response.data.params.watermarked_skipped ) {
										// Just skip.
									} else if (
										response.data.params.watermarked_success &&
										watermarkedIndex !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_files[watermarkedIndex].params.filesize + ', ' + response.data.params.watermarked_files[watermarkedIndex].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_result + '</span></div>' );
									}

									// Optimization.
									if (
										response.data.params.optimization_success &&
										optimizationIndex !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_files[optimizationIndex].params.filesize + ', ' + response.data.params.optimization_files[optimizationIndex].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
									}

									// Conversion.
									if ( response.data.params.conversion_skipped ) {
										// Just skip.
									} else if (
										response.data.params.conversion_success &&
										conversionIndex !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_files[conversionIndex].params.filesize + ', ' + response.data.params.conversion_files[conversionIndex].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_result + '</span></div>' );
									}

									// Optimization after Conversion.
									if ( response.data.params.optimization2_skipped ) {
										// Just skip.
									} else if (
										response.data.params.optimization2_success &&
										optimization2Index !== false
									) {
										currentResultItem.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_files[optimization2Index].params.filesize + ', ' + response.data.params.optimization2_files[optimization2Index].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_result + '</span></div>' );
									}
								}

								if ( response.data.params.webp_skipped ) {
									// Just skip.
								} else if (
									response.data.params.webp_success &&
									webpIndex !== false
								) {
									currentResultItem.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_files[webpIndex].params.filesize + ', ' + response.data.params.webp_files[webpIndex].params.result + '</span></div>' );
								} else {
									currentResultItem.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_result + '</span></div>' );
								}
							}

							qodefOptimization.prograssBarCounter++;
							qodefOptimization.progressBar.progressbar( { value: qodefOptimization.prograssBarCounter } );
							qodefOptimization.counter.find( 'span.qodef-current' ).text( qodefOptimization.prograssBarCounter );
						} else {
							currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
							currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Media Library</span></div>' );
							currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							currentResultItem.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}

						currentResultItem.removeClass( 'qodef-hidden' );
					},
				}
			);
		},

		ajaxFoldersOptimizeAndWebp: function ( options ) {

			var randomNumber = qodefOptimization.randomNumberGenerator(),
				currentResultItem;

			qodefOptimization.resultsHolder.prepend( '<div class="qodef-result qodef-item-' + randomNumber + ' qodef-hidden"></div>' );

			currentResultItem = qodefOptimization.resultsHolder.find( '.qodef-result.qodef-item-' + randomNumber );

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'bulk_action_folders_optimize_and_webp',
						options: options,
					},
					success: function ( data ) {
						var response = $.parseJSON( data );

						if ( response.status === 'success' ) {

							if ( response.data.params.optimization_skipped ) {
								currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
								currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Folders</span></div>' );
								currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
							} else {

								if ( response.data.params.restoration_success ) {
									currentResultItem.append( '<div><span class="qodef-title">Restoration:</span> <span class="qodef-value">' + response.data.params.restoration_result + '</span></div>' );
								}

								if (
									( response.data.params.watermarked_success ) ||
									( response.data.params.optimization_success ) ||
									( response.data.params.conversion_success ) ||
									( response.data.params.optimization2_success )
								) {
									if ( response.data.params.conversion_success ) {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.conversion_files[0].params.file_basename + '</span></div>' );
									} else if ( response.data.params.optimization_success ) {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.optimization_files[0].params.file_basename + '</span></div>' );
									} else {
										currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
									}

									currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Folders</span></div>' );

									if ( response.data.params.watermarked_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.watermarked_files[0].params.initial_size + '</span></div>' );
									} else if ( response.data.params.optimization_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.optimization_files[0].params.initial_size + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Original:</span> <span class="qodef-value">' + response.data.params.initial_size + '</span></div>' );
									}

									// Watermark.
									if ( response.data.params.watermarked_skipped ) {
										// Just skip.
									} else if ( response.data.params.watermarked_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_files[0].params.filesize + ', ' + response.data.params.watermarked_files[0].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Watermarked:</span> <span class="qodef-value">' + response.data.params.watermarked_result + '</span></div>' );
									}

									// Optimization.
									if ( response.data.params.optimization_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_files[0].params.filesize + ', ' + response.data.params.optimization_files[0].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
									}

									// Conversion.
									if ( response.data.params.conversion_skipped ) {
										// Just skip.
									} else if ( response.data.params.conversion_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_files[0].params.filesize + ', ' + response.data.params.conversion_files[0].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Conversion:</span> <span class="qodef-value">' + response.data.params.conversion_result + '</span></div>' );
									}

									// Optimization after Conversion.
									if ( response.data.params.optimization2_skipped ) {
										// Just skip.
									} else if ( response.data.params.optimization2_success ) {
										currentResultItem.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_files[0].params.filesize + ', ' + response.data.params.optimization2_files[0].params.result + '</span></div>' );
									} else {
										currentResultItem.append( '<div><span class="qodef-title">Compression:</span> <span class="qodef-value">' + response.data.params.optimization2_result + '</span></div>' );
									}
								}

								if ( response.data.params.webp_skipped ) {
									// Just skip.
								} else if ( response.data.params.webp_success ) {
									currentResultItem.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_files[0].params.filesize + ', ' + response.data.params.webp_files[0].params.result + '</span></div>' );
								} else {
									currentResultItem.append( '<div><span class="qodef-title">WebP:</span> <span class="qodef-value">' + response.data.params.webp_result + '</span></div>' );
								}
							}

							qodefOptimization.prograssBarCounter++;
							qodefOptimization.progressBar.progressbar( { value: qodefOptimization.prograssBarCounter } );
							qodefOptimization.counter.find( 'span.qodef-current' ).text( qodefOptimization.prograssBarCounter );
						} else {
							currentResultItem.append( '<div class="qodef-file-name"><span class="qodef-title">File:</span> <span class="qodef-value">' + response.data.params.original_file + '</span></div>' );
							currentResultItem.append( '<div><span class="qodef-title">Image Source:</span> <span class="qodef-value">Folders</span></div>' );
							currentResultItem.append( '<div><span class="qodef-title">Optimization:</span> <span class="qodef-value">' + response.data.params.optimization_result + '</span></div>' );
						}

						if ( response.data.params.elapsed_time ) {
							currentResultItem.append( '<div><span class="qodef-title">Elapsed time:</span> <span class="qodef-value">' + response.data.params.elapsed_time + '</span></div>' );
						}

						currentResultItem.removeClass( 'qodef-hidden' );
					},
				}
			);
		},
	};

})( jQuery );
