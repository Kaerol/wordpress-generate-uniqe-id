'use strict';
jQuery(document).ready(function ($) {
	$(document).on('click', '.woo-generate_uniqe_id', function () {
		let $button = $(this);
		$('.woo-generate_uniqe_id').val("Generuję.....");
		$('.woo-generate_uniqe_id_error').html('');	

		$.ajax({
			url: generate_uniqe_id.ajax_url,
			type: 'POST',
			data: {
				action: 'save_generated_uniqe_id_next_to_order',
				order_id: $button.data('order_id'),
			},
			success: function (response) {
				var json = $.parseJSON(response);
				$('.woo-generate_uniqe_id').val(json.uniqeId);
				$('.woo-generate_uniqe_id_link').html("Pobierz bilet .pdf");
				$('.woo-generate_uniqe_id_link').attr('href', json.url);
				$('.woo-generate_uniqe_id_error').html('href', json.errorl);

				if (json.error) {
					$('.woo-generate_uniqe_id_error').html(json.error);	
				}else{
					$('.woo-generate_uniqe_id_error').html('');	
				}
			},
			error: function (response) {
				console.log(response);
			}
		});
	});
});