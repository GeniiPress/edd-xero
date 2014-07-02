/*
!* edd-xero scripts
*/

jQuery(document).ready( function($) {

	var invoice_number = $('#_edd_xero_invoice_number').val();
	var invoice_content = $('#edd_xero_invoice_details');
	var payment_id = $('input[name="edd_payment_id"]').val();

	// Common function for building a snapshot of an invoice to display in the metabox
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
				else {
					$('#edd_xero_invoice_details').empty().append(result.data.error_message);
				}

			}
		});

	}

	// Invoice generation button handler
	$('#edd-xero-generate-invoice').on('click', function(e) {

		var button = $(this);

		// Halt default browser event
		e.preventDefault();

		// Ensure user knows what they're doing
		if( confirm( 'Are you SURE you want to generate a NEW invoice in Xero?' ) ) {

			if( payment_id <= 0 ) {
				alert('There was a problem creating the invoice. Please refresh to check Payment Notes and try again.');
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
					else {

						alert(result.data.error_message);

						button.show();
						invoice_content.hide();

					}

				},
				error: function() {
					alert('There was a problem generating the invoice. Please check Payment Notes and try again.');
					button.show();
					invoice_content.hide();
				}
			});


		}

	});

	// Invoice disassociation button handler
	$('#edd-xero-disassociate-invoice').on('click', function(e) {

		var button = $(this);

		// Halt browser
		e.preventDefault();

		// Make sure the user wants to do this
		if( confirm( 'Are you SURE you want to disassociate this invoice from this payment? The invoice will NOT be changed in Xero' ) ) {

			// Disassociate the invoice
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'disassociate_invoice',
					payment_id: payment_id
				},
				beforeSend: function() {
					button.text('Loading...');
				},
				success: function(result) {

					if(result.success) {
						$('#edd_xero_invoice_details, #edd-xero .edd-invoice-actions').fadeOut(200, function() {
							$('#edd-xero h3.invoice-number').text('Invoice disassociated').addClass('text-center');
						});
					}
					else {
						button.text('Disassociate Invoice');
						alert('There was a problem disassociating the invoice. Please check Payment Notes and try again.');
					}

					button.remove();

				},
				error: function() {
					button.text('Disassociate Invoice');
					alert('There was a problem disassociating the invoice. Please check Payment Notes and try again.');
				}
			});

		}

	});

});
