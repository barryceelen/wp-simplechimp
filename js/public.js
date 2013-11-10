(function ($) {
	"use strict";
	$(function () {
		var SimpleChimp = {
			form: '',
			init: function() {
				var _this = this;
				$(document.body).on( 'submit', 'form.simplechimp', function(e){
					e.preventDefault();
					_this.form = $(e.target);
					_this.toggleSubmitButton();
					_this.form.toggleClass('simplechimp-loading');
					$.post( simplechimpVars.ajaxurl,
						{
							nonce: simplechimpVars.nonce,
							email: _this.form.find('input.simplechimp-email').val(),
							action: simplechimpVars.action
						},
						function( response ) {
							_this.toggleSubmitButton();
							_this.form.toggleClass('simplechimp-loading');
							_this.displayResponse(response);
						}
					);
				});
			},
			toggleSubmitButton: function() {
				var button = this.form.find('.simplechimp-submit');
				if ( button.attr('disabled') == 'disabled' ) {
					button.removeAttr( 'disabled' );
				} else {
					button.attr( 'disabled','disabled' );
				}
			},
			displayResponse: function(response) {
				this.form.find( '.simplechimp-feedback' ).html(response).show();
			}
		}
		SimpleChimp.init();
	});
}(jQuery));
