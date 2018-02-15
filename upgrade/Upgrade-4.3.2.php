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
    if (Configuration::get('PAYPAL_PAYMENT_METHOD') == 5) {
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
    } elseif (Configuration::get('PAYPAL_BRAINTREE_ENABLED')) {
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
            $method_bt = AbstractMethodPaypal::load('BT');
            $existing_merchant_accounts = $method_bt->getAllCurrency();
            $new_merchant_accounts = $method_bt->createForCurrency();
            $all_merchant_accounts = array_merge((array)$existing_merchant_accounts, (array)$new_merchant_accounts);
            unset($all_merchant_accounts[0]);
            if ($all_merchant_accounts) {
                Configuration::updateValue('PAYPAL_'.$mode.'_BT_ACCOUNT_ID', Tools::jsonEncode($all_merchant_accounts));
            }
        }
    } elseif (Configuration::get('PAYPAL_PAYMENT_METHOD') == 1 || Configuration::get('PAYPAL_PAYMENT_METHOD') == 4) {
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
    }

    /** @var DbPDOCore $db */
    $db = Db::getInstance();

    $sql = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."paypal_order_new` (
			`id_paypal_order` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
			`id_order` INT(11),
			`id_cart` INT(11),
			`id_transaction` VARCHAR(55),
			`id_payment` VARCHAR(55),
			`client_token` VARCHAR(255),
			`payment_method` VARCHAR(255),
			`currency` VARCHAR(21),
			`total_paid` FLOAT(11),
			`payment_status` VARCHAR(255),
			`total_prestashop` FLOAT(11),
			`method` VARCHAR(255),
			`payment_tool` VARCHAR(255),
			`date_add` DATETIME,
			`date_upd` DATETIME
		) ENGINE = "._MYSQL_ENGINE_;
    $db->Execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "paypal_capture_new` (
              `id_paypal_capture` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              `id_capture` VARCHAR(55),
              `id_paypal_order` INT(11),
              `capture_amount` FLOAT(11),
              `result` VARCHAR(255),
              `date_add` DATETIME,
              `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_ ;
    $db->Execute($sql);

    /** @var DbQueryCore $query */
    $query = new DbQuery();
    $query->select('*, po.id_order as id_order_ps, po.id_cart as id_cart_ps');
    $query->from('orders','po');
    $query->innerJoin('paypal_order','ppo','ppo.id_order = po.id_order');
    $query->leftJoin('paypal_braintree','ppb','ppo.id_order = ppb.id_order');
    $query->leftJoin('paypal_plus_pui','ppp','ppo.id_order = ppp.id_order');

    $results = $db->query($query);

    $methods = array(
        '1' => 'EC',
        '2' => 'EC',
        '3' => 'EC',
        '4' => 'PPP',
        '5' => 'BT',
    );

    /** @var FileLoggerCore $logger */
    $logger = new FileLogger();
    $logger->setFilename(_PS_MODULE_DIR_,'/paypal/log/paypal_mig.log');
    while ($order = $db->nextRow($results)) {
        try {
            $db->insert('paypal_order_new', array(
                'id_order' => $order['id_order_ps'],
                'id_cart' => $order['id_cart_ps'],
                'id_transaction' => $order['id_transaction'],
                'id_payment' => $order['nonce_payment_token'],
                'client_token' => $order['client_token'],
                'payment_method' => $order['payment_method'] == 2 ? 'HSS' : '',
                'currency' => $order['currency'],
                'total_paid' => $order['total_paid'],
                'payment_status' => $order['payment_status'],
                'total_prestashop' => $order['total_paid'],
                'method' => $methods[$order['payment_method']],
                'payment_tool' => $order['pui_informations'] ? 'PAY_UPON_INVOICE' : '',
                'date_add' => $order['payment_date'],
                'date_upd' => $order['payment_date'],
            ));
            $last_id = $db->Insert_ID();
            if ($order['capture']) {
                $db->insert('paypal_capture_new', array(
                    'id_capture' => '',
                    'id_paypal_order' => $last_id,
                    'capture_amount' => $order['total_paid'],
                    'result' => '',
                    'date_add' => $order['payment_date'],
                    'date_upd' => $order['payment_date'],
                ));
            }
        } catch (Exception $e) {
            $logger->logError($e->getCode().' - '.$e->getMessage());
        }
    }

    $db->Execute('RENAME TABLE '._DB_PREFIX_.'paypal_order TO '._DB_PREFIX_.'paypal_order_old');
    $db->Execute('RENAME TABLE '._DB_PREFIX_.'paypal_order_new TO '._DB_PREFIX_.'paypal_order');

    $query = new DbQuery();
    $query->select('*');
    $query->from('paypal_capture','pc');
    $query->leftJoin('paypal_order','po','po.id_order = pc.id_order');
    $results = $db->query($query);

    while ($capture = $db->nextRow($results)) {
        try {
            $db->insert('paypal_capture_new', array(
                'id_capture' => '',
                'id_paypal_order' => $capture['id_paypal_order'],
                'capture_amount' => $capture['capture_amount'],
                'result' => 'completed_before_upgrade',
                'date_add' => $capture['date_add'],
                'date_upd' => $capture['date_upd'],
            ));
        } catch (Exception $e) {
            $logger->logError($e->getCode().' - '.$e->getMessage());
        }
    }

    $db->Execute('RENAME TABLE '._DB_PREFIX_.'paypal_capture TO '._DB_PREFIX_.'paypal_capture_old');
    $db->Execute('RENAME TABLE '._DB_PREFIX_.'paypal_capture_new TO '._DB_PREFIX_.'paypal_capture');

    return true;
}
