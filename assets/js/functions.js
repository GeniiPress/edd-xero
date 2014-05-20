/*
!* edd-xero scripts
*/

jQuery( function($) {

	var invoice_number = $('#_edd_xero_invoice_number').val();
	var invoice_content = $('#edd_xero_invoice_details');

	// If this payment has an invoice, display some deets
	if(invoice_number !== '') {

		// Show loading indicator and placeholder for content
		invoice_content.show();

		// Throw an AJAX request back to lookup invoice from Xero API
		$.ajax({
			url: ajaxurl, // Piggyback off EDD var
			dataType: 'json',
			data: {
				action: 'invoice_lookup',
				invoice_number: invoice_number
			},
			success: function(result) {

				if(result.success) {

					var markup = '<p>';

					markup += '<span class="label">Status:</span> <span class="right">' + result.data.Status + '</span><br />';
					markup += '<span class="label">Total:</span> <span class="right">' + result.data.Total + ' ' + result.data.CurrencyCode + '</span><br />';
					markup += '<span class="label">Tax:</span> <span class="right">' + result.data.TotalTax + ' ' + result.data.CurrencyCode + '</span><br />';
					markup += '<span class="label">Contact:</span> <span class="right">' + result.data.Contact.Name + '</span><br />';
					markup += '<span class="label">Email:</span> <span class="right">' + result.data.Contact.Email + '</span>';

					markup += '</p>';

					$('#edd_xero_invoice_details').empty().hide().append(markup);
					$('#edd_xero_invoice_details, #edd-xero .edd-invoice-actions').fadeIn(400);

				}

			}
		});

	}

	// Invoice generation button
	$('#edd-xero-generate-invoice').on('click', function(e) {

		var button = $(this);

		// Halt default browser event
		e.preventDefault();

		// Ensure user knows what they're doing
		if( confirm( 'Are you SURE you want to generate a NEW invoice in Xero?' ) ) {

			// Show loading indicator and placeholder for content
			invoice_content.show();

			// Hide the generate invoice button
			button.hide();

		}

	});

});
