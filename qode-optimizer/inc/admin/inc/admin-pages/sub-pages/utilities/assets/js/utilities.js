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
			qodefUtilities.init();
		}
	);

	var qodefUtilities = {
		init: function () {
			this.mainForm        = $( '#qodef-utility-start' );
			this.mainFormActions = [];
			this.mainFormSubmit  = '';
			this.loading         = '';
			this.message         = '';

			if ( this.mainForm.length ) {
				this.fetchAllFormSubmitActions( this.mainForm );
				this.initAction( this.mainForm );
			}
		},

		fetchAllFormSubmitActions: function ( $form ) {
			if ( $form.length ) {
				var $allFormSubmitButtons = $form.find( 'input[type="submit"]' );
				if ( $allFormSubmitButtons.length ) {
					$allFormSubmitButtons.each(
						function () {
							qodefUtilities.mainFormActions.push( $( this ).attr( 'name' ) );
						}
					);
				}
			}
		},

		initAction: function ( $form ) {
			if ( $form.length ) {

				$form.submit(
					function (e) {
						e.preventDefault();

						var formSubmitTrigger  = document.activeElement.name,
							$formSubmitButton  = $( document.activeElement ),
							$formSubmitSection = $formSubmitButton.closest( '.qodef-utility-form-action' ),
							utilityParams      = qodefUtilities.getUtilityParams( formSubmitTrigger );

						qodefUtilities.mainFormSubmit = $formSubmitButton;
						qodefUtilities.loading        = $formSubmitSection.find( '.qodef-spinner-loading' );
						qodefUtilities.message        = $formSubmitSection.find( '.qodef-message' );

						$( '<div id="qodef-dialog"></div>' ).appendTo( 'body' )
							.html( '<div><h5>${utilityParams.confirmationMessage}<br />Are you sure?</h5></div>' )
							.dialog(
								{
									modal: true,
									title: utilityParams.confirmationTitle,
									zIndex: 10000,
									autoOpen: true,
									width: 'auto',
									resizable: false,
									buttons: {
										Yes: function () {
											qodefUtilities.mainFormSubmit.prop(
												'disabled',
												true
											);
											qodefUtilities.loading.removeClass( 'qodef-hidden' );
											qodefUtilities.message.addClass( 'qodef-hidden' );

											var qo_nonce = $form.data( 'qo-nonce' ),
												options  = {
													qo_nonce: qo_nonce,
											};

											if ( utilityParams.handler.length ) {
												qodefUtilities[utilityParams.handler]( options );
											}

											$( this ).dialog( 'close' );
										},
										No: function () {
											$( this ).dialog( 'close' );
										}
									},
									close: function ( event, ui ) {
										$( this ).remove();
									}
								}
							);
					}
				);
			}
		},

		getUtilityParams: function ( action ) {
			var params = {
				confirmationTitle: '',
				confirmationMessage: '',
				handler: '',
			};

			if ( qodefUtilities.mainFormActions.includes( action ) ) {

				if ( action === 'utility_resolve_optimization_history_issues' ) {
					params.confirmationTitle   = 'Resolve Optimization History Issues';
					params.confirmationMessage = 'Resolving optimization history issues process is about to start.';
					params.handler             = 'ajaxResolveOptimizationHistoryIssues';
				} else if ( action === 'utility_delete_optimization_history' ) {
					params.confirmationTitle   = 'Optimization History Removal';
					params.confirmationMessage = 'Complete optimization history removal process is about to start.';
					params.handler             = 'ajaxDeleteOptimizationHistory';
				} else if ( action === 'utility_clean_up_optimization_history' ) {
					params.confirmationTitle   = 'Optimization History Cleanup';
					params.confirmationMessage = 'Optimization history cleanup process is about to start.';
					params.handler             = 'ajaxCleanUpOptimizationHistory';
				} else if ( action === 'utility_delete_webp_images' ) {
					params.confirmationTitle   = 'WebP Images Removal';
					params.confirmationMessage = 'WebP images removal process is about to start.';
					params.handler             = 'ajaxDeleteWebpImages';
				} else if ( action === 'utility_delete_all_webp_images' ) {
					params.confirmationTitle   = 'Complete WebP Images Removal';
					params.confirmationMessage = 'Complete WebP images removal process is about to start.';
					params.handler             = 'ajaxDeleteAllWebpImages';
				}
			}

			return params;
		},

		ajaxResolveOptimizationHistoryIssues: function ( options ) {

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'utility_action_resolve_optimization_history_issues',
						options: options,
					},
					success: function ( data ) {
						qodefUtilities.loading.addClass( 'qodef-hidden' );
						console.log( 'Optimization history issues resolution finished!' );
					},
				}
			);
		},

		ajaxDeleteOptimizationHistory: function ( options ) {

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'utility_action_delete_optimization_history',
						options: options,
					},
					success: function ( data ) {
						qodefUtilities.loading.addClass( 'qodef-hidden' );
						console.log( 'Optimization history removal finished!' );
					},
				}
			);
		},

		ajaxCleanUpOptimizationHistory: function ( options ) {

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'utility_action_clean_up_optimization_history',
						options: options,
					},
					success: function ( data ) {
						qodefUtilities.loading.addClass( 'qodef-hidden' );
						console.log( 'Optimization history cleanup finished!' );
					},
				}
			);
		},

		ajaxDeleteWebpImages: function ( options ) {

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'utility_action_delete_webp_images',
						options: options,
					},
					success: function ( data ) {
						qodefUtilities.loading.addClass( 'qodef-hidden' );
						console.log( 'WebP images removal finished!' );
					},
				}
			);
		},

		ajaxDeleteAllWebpImages: function ( options ) {

			$.ajax(
				{
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'utility_action_delete_all_webp_images',
						options: options,
					},
					success: function ( data ) {
						qodefUtilities.loading.addClass( 'qodef-hidden' );
						console.log( 'Complete WebP images removal finished!' );
					},
				}
			);
		},
	};

})( jQuery );
