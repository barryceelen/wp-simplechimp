(function ($) {
	"use strict";
	$(function () {
		var SimpleChimp = {
			form: '',
			init: function() {
				var _this = this;
				$( document.body ).on( 'submit', 'form.simplechimp', function(e){
					e.preventDefault();
					_this.form = $( e.target );
					_this.toggleSubmitButton();
					_this.form.toggleClass( 'simplechimp-loading' );
					$.post( simplechimpVars.ajaxurl,
						{
							action: simplechimpVars.action,
							simplechimp_subscribe: _this.form.find( 'input[name="simplechimp_subscribe"]' ).val(),
							simplechimp_email: _this.form.find( 'input[name="simplechimp_email"]' ).val()
						},
						function( response ) {
							_this.toggleSubmitButton();
							_this.form.toggleClass( 'simplechimp-loading' );
							_this.displayResponse( response );
						}
					);
				});
			},
			toggleSubmitButton: function() {
				var button = this.form.find( 'button[type="submit"]' );
				if ( button.attr( 'disabled' ) == 'disabled' ) {
					button.removeAttr( 'disabled' );
				} else {
					button.attr( 'disabled', 'disabled' );
				}
			},
			displayResponse: function( response ) {
				var $el = this.form.find( '.simplechimp-feedback' );
				$el.html( response.data ).show();
				if ( false == response.success ) {
					$el.addClass( 'simplechimp-error' );
				} else {
					$el.removeClass( 'simplechimp-error' );
				}
			}
		}
		SimpleChimp.init();
	});
}(jQuery));
