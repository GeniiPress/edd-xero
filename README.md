# Easy Digital Downloads - Xero

[Love Xero](https://www.xero.com)? Love selling stuff on your WordPress site with [Easy Digital Downloads](http://easydigitaldownloads.com)? Combining the two seems like a no brainer.

Xero for Easy Digital Downloads will automatically create invoices and customers in Xero whenever a purchase is made on your store.

After installation, whenever a payment is marked as complete, order and customer details will be securely sent to Xero, keeping your accounts up to date and everything in sync.

Do beautiful business with Xero and Easy Digital Downloads, automatically and in the background.

## Developer Information ##

We have built (and continue to build) EDD Xero to be fully extensible by developers. This means hooks and configuration options galore.

### Filter Hooks ###

  * `edd_xero_invoice_number` Xero automatically creates invoice numbers, however, leveraging this filter will allow you to specify your own numbering. Accepts two arguments, $payment_id and $payment.

### Action Hooks ###

On top of the available filters, EDD Xero also exposes and leverages the following action hooks.

  * `edd_xero_payment_success` Fires when a Xero payment is successfully applied to an invoice. Variables passed through: $xero_payment, $response, $payment_id.
  * `edd_xero_payment_fail` Fires when an attempt to apply a payment to a Xero invoice fails. Variables passed through: $xero_payment, $response, $payment_id.
  * `edd_xero_invoice_creation_fail` Fires whenever an attempt was made to create a Xero invoice, but failed. Variables passed through: $invoice, $payment_id, $error_obj = null, $custom_message = null.
  * `edd_xero_invoice_creation_success` Fires whenever a Xero invoice has been successfully created. Variables passed through: $invoice, $invoice_number, $invoice_id, $payment_id.
