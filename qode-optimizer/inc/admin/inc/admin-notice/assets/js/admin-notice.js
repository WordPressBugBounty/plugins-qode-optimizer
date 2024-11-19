(function ( $ ) {
	'use strict';

	$( document ).ready(
		function () {
			qodefDeactivationModal.init();
		}
	);

	var qodefDeactivationModal = {
		init: function () {
			this.deactivationLink  = $( '#the-list' ).find( '[data-slug="qode-optimizer"] span.deactivate a' );
			this.deactivationModal = $( '#qode-optimizer-deactivation-modal' );

			if ( this.deactivationLink.length && this.deactivationModal.length ) {
				this.initModal();
			}
		},
		initModal: function () {
			this.deactivationLink.on(
				'click',
				function ( e ) {
					e.preventDefault();
					qodefDeactivationModal.deactivationModal.fadeIn( 'fast' );
				}
			);

			this.enableModalCloseFunctionality();
			this.initSubmitFunctionality();
			this.initSkipFunctionality();
		},
		enableSubmitButton: function () {
			var radioButtons = this.deactivationModal.find( 'input[name="reason_key"]' ),
				submitButton = this.deactivationModal.find( '.qodef-deactivation-modal-button-submit' );

			radioButtons.on(
				'change',
				function () {
					submitButton.removeClass( 'qodef--disabled' );
				}
			);
		},
		initSubmitFunctionality: function () {
			var submitButton = this.deactivationModal.find( '.qodef-deactivation-modal-button-submit' ),
				skipButton   = this.deactivationModal.find( '.qodef-deactivation-modal-button-skip' ),
				nonceHolder  = this.deactivationModal.find( '#qode-optimizer-deactivation-nonce' );

			if ( submitButton.length ) {
				submitButton.on(
					'click',
					function ( e ) {
						e.preventDefault();
						submitButton.addClass( 'qodef--processing' );
						skipButton.addClass( 'qodef--disabled' );

						var reason = qodefDeactivationModal.deactivationModal.find( 'input[name="reason_key"]:checked' ).val();

						$.ajax(
							{
								type: 'POST',
								data: {
									action: 'qode_optimizer_deactivation',
									reason: reason,
									additionalInfo: qodefDeactivationModal.getAdditionalInfo( reason ),
									nonce: nonceHolder.val()
								},
								url: ajaxurl,
								success: function () {
									qodefDeactivationModal.deactivatePlugin();
								}
							}
						);
					}
				);
			}
		},
		getAdditionalInfo: function ( reason ) {
			var additionalInfo = '';

			switch (reason) {
				case 'found_a_better_plugin':
					additionalInfo = qodefDeactivationModal.deactivationModal.find( 'input[name="reason_found_a_better_plugin"]' ).val();
					break;
				case 'other':
					additionalInfo = qodefDeactivationModal.deactivationModal.find( 'input[name="reason_other"]' ).val();
					break;
			}

			return additionalInfo;
		},
		initSkipFunctionality: function () {
			var submitButton = this.deactivationModal.find( '.qodef-deactivation-modal-button-submit' ),
				skipButton   = this.deactivationModal.find( '.qodef-deactivation-modal-button-skip' );

			if ( skipButton.length ) {
				skipButton.on(
					'click',
					function ( e ) {
						e.preventDefault();
						skipButton.addClass( 'qodef--processing' );
						submitButton.addClass( 'qodef--disabled' );
						qodefDeactivationModal.deactivatePlugin();
					}
				);
			}
		},
		deactivatePlugin: function () {
			location.href = this.deactivationLink.attr( 'href' );
		},
		enableModalCloseFunctionality: function () {
			var closeButton = this.deactivationModal.find( '.qodef-deactivation-modal-close' );

			if ( closeButton.length ) {
				closeButton.on(
					'click',
					function ( e ) {
						e.preventDefault();
						qodefDeactivationModal.deactivationModal.fadeOut( 'fast' );
					}
				);
			}
		}
	};

})( jQuery );
