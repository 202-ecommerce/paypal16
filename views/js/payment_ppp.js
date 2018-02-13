/**
 * 2007-2018 PrestaShop
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2018 PrestaShop SA
 * @license   http://addons.prestashop.com/en/content/12-terms-and-conditions-of-use
 * International Registered Trademark & Property of PrestaShop SA
 */

$(document).ready(function(){
    if (advancedEU) {
        $(document).on('click', '.payment_module', function(e) {
            var ppp_el = $(this).parent().find('#paypal-plus-payment');
            if (ppp_el.length) {               // Clicked element has a #paypal-plus-payment child.
                ppp_el.parent().stop().show(); // Display paypal options.
            } else {                           // Clicked element doesn't contain #ppplusDiv child,
                $(document).find('#paypal-plus-payment').parent().stop().hide(); // thus we can hide paypal options.
            }
        });
    }
});


    exec_ppp_payment = true;
    function doPatchPPP() {
        if (exec_ppp_payment) {
            exec_ppp_payment = false;
            $.fancybox.open({
                content : '<div id="popup-ppp-waiting"><p>'+waiting_redirection+'</p></div>',
                closeClick : false,
                height : "auto",
                helpers : {
                    overlay : {
                        closeClick: false
                    }
                },
            });
            $.ajax({
                type    : 'POST',
                url     : ajax_patch_url,
                dataType: 'json',
                success : function (json) {
                    if (json && json.success) {
                        ppp.doCheckout();
                    }
                },
                error   : function (xhr, ajaxOptions, thrownError) {

                }
            });
        }
    }



