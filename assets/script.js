jQuery(document).ready(function($) {

    // Handle Checkout Form
    $('#stripe-checkout-form').submit(function(e) {
            e.preventDefault();
            let plan = $('input[name="plan"]:checked').val();
            
            if (!plan) {
                alert('Please select a plan.');
                return;
            }

            $.post(ajaxurl, {
                action: 'create_checkout',
                plan: plan
            }, function(response) {
                if (response.success) {
                    window.location.href = response.data.checkout_url;
                } else {
                    alert('Error: ' + response.data);
                }
            });
        });
    });

    // Handle Subscription Cancellation
    $('#cancel-subscription-form').submit(function(e) {
        e.preventDefault();
        let subId = $('input[name="subscription_id"]').val();

        if (confirm('Are you sure you want to cancel your subscription?')) {
            $.post(ajaxurl, {
                action: 'cancel_subscription',
                subscription_id: subId
            }, function(response) {
                if (response.success) {
                    alert('Subscription canceled successfully.');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }
    });

    // Fetch Invoices
    function fetchInvoices() {
        $.post(ajaxurl, { action: 'fetch_invoices' }, function(response) {
            if (response.success) {
                let invoices = response.data;
                let invoiceList = $('#invoice-list');
                invoiceList.empty();

                if (invoices.length === 0) {
                    invoiceList.append('<p>No invoices found.</p>');
                } else {
                    invoices.forEach(function(invoice) {
                        invoiceList.append(`<p>
                            Date: ${invoice.date} | Amount: ${invoice.total} 
                            <a href="${invoice.pdf}" target="_blank">Download</a>
                        </p>`);
                    });
                }
            } else {
                $('#invoice-list').html('<p>Error fetching invoices.</p>');
            }
        });
    }
    fetchInvoices();
});
