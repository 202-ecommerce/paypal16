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

    $('#paypal_params').hide();
    $('a[href=#paypal_conf]').parents('li').addClass('active');
    $('a[href=#paypal_params]').parents('li').removeClass('active');

    $('a[href=#paypal_params]').on('click', function() {
        $('#paypal_params').show();
        $('#paypal_conf').hide();
        $('a[href=#paypal_params]').parents('li').addClass('active');
        $('a[href=#paypal_conf]').parents('li').removeClass('active');
    });

    $('a[href=#paypal_conf]').on('click', function() {
        $('#paypal_conf').show();
        $('#paypal_params').hide();
        $('a[href=#paypal_conf]').parents('li').addClass('active');
        $('a[href=#paypal_params]').parents('li').removeClass('active');
    });

    elms = document.querySelectorAll("#configuration_form");
    for(var i = 0; i < elms.length; i++)
        $('#paypal_params').append(elms[i])



});


