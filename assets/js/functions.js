/*
!* edd-xero scripts
*/

jQuery( function($) {

	var invoice_number = $('#_edd_xero_invoice_number').val();
	var invoice_content = $('#edd_xero_invoice_details');

	get_invoice_excerpt = function (data) {

		var markup = '<p>';

		markup += '<span class="label">Status:</span> <span class="right">' + data.Status + '</span><br />';
		markup += '<span class="label">Total:</span> <span class="right">' + data.Total + ' ' + data.CurrencyCode + '</span><br />';
		markup += '<span class="label">Tax:</span> <span class="right">' + data.TotalTax + ' ' + data.CurrencyCode + '</span><br />';
		markup += '<span class="label">Contact:</span> <span class="right">' + data.Contact.Name + '</span><br />';
		markup += '<span class="label">Email:</span> <span class="right">' + data.Contact.Email + '</span>';

		markup += '</p>';

		return markup;

	}

	// If this payment has an invoice, display some deets
	if(invoice_number !== '') {

		// Show loading indicator and placeholder for content
		invoice_content.show();

		// Throw an AJAX request back to lookup invoice from Xero API
		$.ajax({
			url: ajaxurl, // Piggyback off EDD var
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'invoice_lookup',
				invoice_number: invoice_number
			},
			success: function(result) {

				if(result.success) {

					var excerpt = get_invoice_excerpt(result.data);

					$('#edd_xero_invoice_details').empty().hide().append(excerpt);
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

			// Get payment id
			var payment_id = $('input[name="edd_payment_id"]').val();

			if( payment_id <= 0 ) {
				alert('There was a problem creating the invoice. Please refresh and try again.');
				return false;
			}

			// Show loading indicator and placeholder for content
			invoice_content.show();

			// Hide the generate invoice button
			button.hide();

			// Generate the invoice
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'generate_invoice',
					payment_id: payment_id
				},
				success: function(result) {

					if(result.success) {

						var excerpt = get_invoice_excerpt(result.data);

						$('#edd_xero_invoice_details').empty().hide().append(excerpt);
						$('#edd-view-invoice-in-xero').attr('href', 'https://go.xero.com/AccountsReceivable/Edit.aspx?InvoiceID=' + result.data.ID);
						$('#edd-xero h3.invoice-number').text(result.data.InvoiceNumber).removeClass('text-center');
						$('#edd_xero_invoice_details, #edd-xero .edd-invoice-actions').fadeIn(400);

					}

				},
				error: function() {
					alert('There was a problem generating the invoice. Please try again.');
					button.show();
					invoice_content.hide();
				}
			});


		}

	});

});
