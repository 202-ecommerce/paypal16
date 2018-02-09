<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2018 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_3_2($module)
{
    if (!$module->registerHook('actionOrderStatusUpdate')
    || !$module->registerHook('actionObjectCurrencyAddAfter')) {
        return false;
    }

    $mode = Configuration::get('PAYPAL_SANDBOX') ? 'SANDBOX' : 'LIVE';
    switch (Configuration::get('PAYPAL_PAYMENT_METHOD')) {
        case '1':
        case '4':
            if (Configuration::get('PAYPAL_API_USER')
                && Configuration::get('PAYPAL_API_PASSWORD')
                && Configuration::get('PAYPAL_API_SIGNATURE')) {
                Configuration::updateValue('PAYPAL_METHOD', 'EC');
                Configuration::updateValue('PAYPAL_EXPRESS_CHECKOUT', 1);
                Configuration::updateValue('PAYPAL_USERNAME_'.$mode, Configuration::get('PAYPAL_API_USER'));
                Configuration::updateValue('PAYPAL_PSWD_'.$mode, Configuration::get('PAYPAL_API_PASSWORD'));
                Configuration::updateValue('PAYPAL_SIGNATURE_'.$mode, Configuration::get('PAYPAL_API_SIGNATURE'));
                Configuration::updateValue('PAYPAL_'.$mode.'_ACCESS', 1);
                if (Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT_M_ID')) {
                    Configuration::updateValue('PAYPAL_MERCHANT_ID_'.$mode, Configuration::get('PAYPAL_IN_CONTEXT_CHECKOUT_M_ID'));
                    Configuration::updateValue('PAYPAL_EC_IN_CONTEXT', 1);
                }
            }
            break;
        case '2':
            break;
        case '5':
            if (Configuration::get('PAYPAL_PLUS_CLIENT_ID') && Configuration::get('PAYPAL_PLUS_SECRET')) {
                Configuration::updateValue('PAYPAL_'.$mode.'_CLIENTID', Configuration::get('PAYPAL_PLUS_CLIENT_ID'));
                Configuration::updateValue('PAYPAL_'.$mode.'_SECRET', Configuration::get('PAYPAL_PLUS_SECRET'));
                Configuration::updateValue('PAYPAL_METHOD', 'PPP');
                Configuration::updateValue('PAYPAL_PLUS_ENABLED', 1);
                $experience_web = $module->createWebExperience();
                if ($experience_web) {
                    Configuration::updateValue('PAYPAL_PLUS_EXPERIENCE', $experience_web->id);
                }
            }
            break;
        case '6':
            if (Configuration::get('PAYPAL_BRAINTREE_ACCESS_TOKEN')
                && Configuration::get('PAYPAL_BRAINTREE_EXPIRES_AT')
                && Configuration::get('PAYPAL_BRAINTREE_REFRESH_TOKEN')
                && Configuration::get('PAYPAL_BRAINTREE_MERCHANT_ID')) {
                Configuration::updateValue('PAYPAL_METHOD', 'BT');
                Configuration::updateValue('PAYPAL_BRAINTREE_ENABLED', 1);
                Configuration::updateValue('PAYPAL_' . $mode . '_BT_ACCESS_TOKEN', Configuration::get('PAYPAL_BRAINTREE_ACCESS_TOKEN'));
                Configuration::updateValue('PAYPAL_' . $mode . '_BT_EXPIRES_AT', Configuration::get('PAYPAL_BRAINTREE_EXPIRES_AT'));
                Configuration::updateValue('PAYPAL_' . $mode . '_BT_REFRESH_TOKEN', Configuration::get('PAYPAL_BRAINTREE_REFRESH_TOKEN'));
                Configuration::updateValue('PAYPAL_' . $mode . '_BT_MERCHANT_ID', Configuration::get('PAYPAL_BRAINTREE_MERCHANT_ID'));
                Configuration::updateValue('PAYPAL_BY_BRAINTREE', 1);
            }
            break;
    }

    return true;
}
