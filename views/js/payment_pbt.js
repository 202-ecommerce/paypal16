/**
 * 2007-2017 PrestaShop
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use
 * International Registered Trademark & Property of PrestaShop SA
 */

/*$(document).ready(function(){
    if ($('.payment_module').length > 0) {
        initPaypalBraintree();
    }
});*/

function initPaypalBraintree() {
    var paypal_bt_form = document.querySelector('#paypal-braintree-form');

    braintree.client.create({
        authorization: authorization
    }, function (clientErr, clientInstance) {

        // Stop if there was a problem creating the client.
        // This could happen if there is a network error or if the authorization
        // is invalid.
        if (clientErr) {
            console.error('Error creating client:', clientErr);
            return;
        }

        // Create a PayPal Checkout component.
        braintree.paypalCheckout.create({
            client: clientInstance
        }, function (paypalCheckoutErr, paypalCheckoutInstance) {

            // Stop if there was a problem creating PayPal Checkout.
            // This could happen if there was a network error or if it's incorrectly
            // configured.
            if (paypalCheckoutErr) {
                $('#bt-paypal-error-msg').show().text(paypalCheckoutErr);
                return;
            }

            paypal.Button.render({
                env: mode, // 'production' or 'sandbox'

                payment: function () {
                    return paypalCheckoutInstance.createPayment({
                        flow: 'vault',
                        billingAgreementDescription: 'Your agreement description',
                        enableShippingAddress: false,
                        shippingAddressEditable: false,
                    });
                },

                onAuthorize: function (data, actions) {
                    return paypalCheckoutInstance.tokenizePayment(data)
                        .then(function (payload) {
                            // Submit `payload.nonce` to your server.
                            document.querySelector('input#paypal_payment_method_nonce').value = payload.nonce;
                            paypal_bt_form.submit();
                        });
                },

                onCancel: function (data) {
                   // $('#bt-paypal-error-msg').show().text('checkout.js payment cancelled'+JSON.stringify(data, 0, 2)+'');
                },

                onError: function (err) {
                    $('#bt-paypal-error-msg').show().text(err);
                }
            }, '#paypal-button').then(function (e) {

            });


        });

    });
}
