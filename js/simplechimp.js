(function ($) {
	var SimpleChimp = {
		form: '',
		init: function() {
			_this = this;
			$(document.body).on('submit', 'form.simplechimp', function(e){
				_this.form = $(e.target);
				e.preventDefault();
				_this.toggleSubmitButton();
				_this.form.toggleClass('simplechimp-loading');
				$.post( SimpleChimpData.ajaxurl,
					{
						nonce: SimpleChimpData.nonce,
						email: _this.form.find('input.simplechimp-email').val(),
						list_id: _this.form.find('input.simplechimp-list-id').val(),
						action: SimpleChimpData.action
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
			if (button.attr('disabled') == 'disabled') {
				button.removeAttr('disabled');
			} else {
				button.attr('disabled','disabled');
			}
		},
		displayResponse: function(response) {
			console.log(response);
			this.form.find('.simplechimp-feedback').html(response).show();
		}
	}
	SimpleChimp.init();
}(jQuery));
