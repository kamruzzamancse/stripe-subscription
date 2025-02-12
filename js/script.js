jQuery(document).ready(function($) {
    // Ensure Stripe is loaded
    if (typeof Stripe === "undefined") {
        console.error("Stripe.js not loaded!");
        return;
    }

    var stripe = Stripe(stripe_ajax.stripe_pk); // Get Publishable Key from localized script
    var elements = stripe.elements();

    // Create Card Element
    var cardElement = elements.create("card", {
        hidePostalCode: true,
        style: {
            base: {
                fontSize: "16px",
                color: "#32325d",
                fontFamily: "Arial, sans-serif",
                "::placeholder": { color: "#aab7c4" }
            }
        }
    });

    // Mount Card Element
    cardElement.mount("#card-element");

    // Handle Form Submission
    $("#stripe-subscription-form").on("submit", function(event) {
        event.preventDefault();

        stripe.createToken(cardElement).then(function(result) {
            if (result.error) {
                $("#card-errors").text(result.error.message);
            } else {
                var token = result.token.id;
                var email = $("#email").val();

                $.ajax({
                    url: stripe_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "stripe_subscription_handle_form",
                        stripeToken: token,
                        email: email,
                        nonce: stripe_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert("Subscription successful!");
                        } else {
                            alert("Error: " + response.data);
                        }
                    }
                });
            }
        });
    });
});
