<?php
/**
 * 2007-2017 PrestaShop
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
 *  @copyright 2007-2017 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
include_once(_PS_MODULE_DIR_.'paypal/sdk/PaypalSDK.php');
include_once(_PS_MODULE_DIR_.'paypal/sdk/BraintreeSiSdk.php');
include_once 'classes/AbstractMethodPaypal.php';
include_once 'classes/PaypalCapture.php';
include_once 'classes/PaypalOrder.php';


// EC = express checkout
// ECS = express checkout sortcut
// BT = Braintree
// PPP = PayPal Plus

class PayPal extends PaymentModule
{
    public static $dev = true;
    public $express_checkout;
    public $message;
    public $amount_paid_paypal;
    public $module_link;
    public $errors;

    public function __construct()
    {
        $this->name = 'paypal';
        $this->tab = 'payments_gateways';
        $this->version = '4.3.2';
        $this->author = 'PrestaShop';
        $this->display = 'view';
        $this->module_key = '336225a5988ad434b782f2d868d7bfcd';
        $this->is_eu_compatible = 1;
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.6');
        $this->controllers = array('payment', 'validation');
        $this->bootstrap = true;

        $this->currencies = true;
        $this->currencies_mode = 'radio';

        parent::__construct();

        $this->displayName = $this->l('PayPal');
        $this->description = $this->l('Benefit from PayPal’s complete payments platform and grow your business online, on mobile and internationally. Accept credit cards, debit cards and PayPal payments.');
        $this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
        $this->express_checkout = $this->l('PayPal Express Checkout ');
        $this->module_link = $this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
    }

    public function install()
    {
        // Install default
        if (!parent::install()) {
            return false;
        }
        // install DataBase
        if (!$this->installSQL()) {
            return false;
        }
        // Registration order status
        if (!$this->installOrderState()) {
            return false;
        }
        // Registration hook
        if (!$this->registrationHook()) {
            return false;
        }

        if (!Configuration::updateValue('PAYPAL_MERCHANT_ID_SANDBOX', '')
            || !Configuration::updateValue('PAYPAL_MERCHANT_ID_LIVE', '')
            || !Configuration::updateValue('PAYPAL_USERNAME_SANDBOX', '')
            || !Configuration::updateValue('PAYPAL_PSWD_SANDBOX', '')
            || !Configuration::updateValue('PAYPAL_SIGNATURE_SANDBOX', '')
            || !Configuration::updateValue('PAYPAL_SANDBOX_ACCESS', 0)
            || !Configuration::updateValue('PAYPAL_USERNAME_LIVE', '')
            || !Configuration::updateValue('PAYPAL_PSWD_LIVE', '')
            || !Configuration::updateValue('PAYPAL_SIGNATURE_LIVE', '')
            || !Configuration::updateValue('PAYPAL_LIVE_ACCESS', 0)
            || !Configuration::updateValue('PAYPAL_SANDBOX', 0)
            || !Configuration::updateValue('PAYPAL_API_INTENT', 'sale')
            || !Configuration::updateValue('PAYPAL_API_ADVANTAGES', 1)
            || !Configuration::updateValue('PAYPAL_API_CARD', 0)
            || !Configuration::updateValue('PAYPAL_METHOD', '')
            || !Configuration::updateValue('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT', 0)
            || !Configuration::updateValue('PAYPAL_CRON_TIME', date('Y-m-d H:m:s'))
            || !Configuration::updateValue('PAYPAL_BY_BRAINTREE', 0)
            || !Configuration::updateValue('PAYPAL_EC_IN_CONTEXT', 0)
        ) {
            return false;
        }

        return true;
    }
    
    /**
     * Install DataBase table
     * @return boolean if install was successfull
     */
    private function installSQL()
    {
        $sql = array();

        $sql[] = "CREATE TABLE IF NOT EXISTS `"._DB_PREFIX_."paypal_order` (
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

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "paypal_capture` (
              `id_paypal_capture` INT(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
              `id_capture` VARCHAR(55),
              `id_paypal_order` INT(11),
              `capture_amount` FLOAT(11),
              `result` VARCHAR(255),
              `date_add` DATETIME,
              `date_upd` DATETIME
        ) ENGINE = " . _MYSQL_ENGINE_ ;


        foreach ($sql as $q) {
            if (!DB::getInstance()->execute($q)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create order state
     * @return boolean
     */
    public function installOrderState()
    {
        if (!Configuration::get('PAYPAL_OS_WAITING')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('PAYPAL_OS_WAITING')))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de paiement PayPal';
                } else {
                    $order_state->name[$language['id_lang']] = 'Awaiting for PayPal payment';
                }
            }
            $order_state->send_email = false;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_.'paypal/views/img/os_paypal.png';
                $destination = _PS_ROOT_DIR_.'/img/os/'.(int) $order_state->id.'.gif';
                copy($source, $destination);
            }
            Configuration::updateValue('PAYPAL_OS_WAITING', (int) $order_state->id);
        }
        if (!Configuration::get('PAYPAL_BRAINTREE_OS_AWAITING')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('PAYPAL_BRAINTREE_OS_AWAITING')))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de paiement Braintree';
                } else {
                    $order_state->name[$language['id_lang']] = 'Awaiting for Braintree payment';
                }
            }
            $order_state->send_email = false;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->add();
            Configuration::updateValue('PAYPAL_BRAINTREE_OS_AWAITING', (int) $order_state->id);
        }
        if (!Configuration::get('PAYPAL_BRAINTREE_OS_WAIT_VALID')
            || !Validate::isLoadedObject(new OrderState(Configuration::get('PAYPAL_BRAINTREE_OS_WAIT_VALID')))) {
            $order_state = new OrderState();
            $order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                if (Tools::strtolower($language['iso_code']) == 'fr') {
                    $order_state->name[$language['id_lang']] = 'En attente de validation Braintree';
                } else {
                    $order_state->name[$language['id_lang']] = 'Awaiting for Braintree validation';
                }
            }
            $order_state->send_email = false;
            $order_state->color = '#4169E1';
            $order_state->hidden = false;
            $order_state->delivery = false;
            $order_state->logable = false;
            $order_state->invoice = false;
            $order_state->add();
            Configuration::updateValue('PAYPAL_BRAINTREE_OS_WAIT_VALID', (int) $order_state->id);
        }
        return true;
    }

    /**
     * [registrationHook description]
     * @return [type] [description]
     */
    private function registrationHook()
    {
        if (!$this->registerHook('payment')
            || !$this->registerHook('paymentReturn')
            || !$this->registerHook('displayOrderConfirmation')
            || !$this->registerHook('displayAdminOrder')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('actionOrderStatusUpdate')
            || !$this->registerHook('header')
            || !$this->registerHook('actionObjectCurrencyAddAfter')
            || !$this->registerHook('displayBackOfficeHeader')
            || !$this->registerHook('displayFooterProduct')
            || !$this->registerHook('actionBeforeCartUpdateQty')
            || !$this->registerHook('displayPDFInvoice')
            || !$this->registerHook('displayPaymentEU')
        ) {
            return false;
        }


        return true;
    }



    public function uninstall()
    {
        $config = array(
            'PAYPAL_SANDBOX',
            'PAYPAL_API_INTENT',
            'PAYPAL_API_ADVANTAGES',
            'PAYPAL_API_CARD',
            'PAYPAL_USERNAME_SANDBOX',
            'PAYPAL_PSWD_SANDBOX',
            'PAYPAL_SIGNATURE_SANDBOX',
            'PAYPAL_SANDBOX_ACCESS',
            'PAYPAL_USERNAME_LIVE',
            'PAYPAL_PSWD_LIVE',
            'PAYPAL_SIGNATURE_LIVE',
            'PAYPAL_LIVE_ACCESS',
            'PAYPAL_METHOD',
            'PAYPAL_MERCHANT_ID',
            'PAYPAL_LIVE_BT_ACCESS_TOKEN',
            'PAYPAL_LIVE_BT_EXPIRES_AT',
            'PAYPAL_LIVE_BT_REFRESH_TOKEN',
            'PAYPAL_LIVE_BT_MERCHANT_ID',
            'PAYPAL_BRAINTREE_ENABLED',
            'PAYPAL_SANDBOX_BT_ACCESS_TOKEN',
            'PAYPAL_SANDBOX_BT_EXPIRES_AT',
            'PAYPAL_SANDBOX_BT_REFRESH_TOKEN',
            'PAYPAL_SANDBOX_BT_MERCHANT_ID',
            'PAYPAL_BY_BRAINTREE',
            'PAYPAL_CRON_TIME',
            'PAYPAL_EXPRESS_CHECKOUT',
            'PAYPAL_EC_IN_CONTEXT'
        );

        foreach ($config as $var) {
            Configuration::deleteByName($var);
        }

        //Uninstall DataBase
        if (!$this->uninstallSQL()) {
            return false;
        }

        // Uninstall default
        if (!parent::uninstall()) {
            return false;
        }
        return true;
    }

    /**
     * Uninstall DataBase table
     * @return boolean if install was successfull
     */
    private function uninstallSQL()
    {
        $sql = array();

        $sql[] = "DROP TABLE IF EXISTS `"._DB_PREFIX_."paypal_capture`";

        $sql[] = "DROP TABLE IF EXISTS `"._DB_PREFIX_."paypal_order`";

        foreach ($sql as $q) {
            if (!DB::getInstance()->execute($q)) {
                return false;
            }
        }

        return true;
    }

    public function getUrl()
    {
        if (Configuration::get('PAYPAL_SANDBOX')) {
            return 'https://www.sandbox.paypal.com/';
        } else {
            return 'https://www.paypal.com/';
        }
    }

    public function getUrlBt()
    {
        if (Configuration::get('PAYPAL_SANDBOX')) {
            return 'https://sandbox.pp-ps-auth.com/';
        } else {
            return 'https://pp-ps-auth.com/';
        }
    }

    public function getContent()
    {
        if (Configuration::get('PAYPAL_UPDATE_MSG')) {
            $this->message .= $this->displayConfirmation(Configuration::get('PAYPAL_UPDATE_MSG'));
            Configuration::updateValue('PAYPAL_UPDATE_MSG', false);
        }
       /* Configuration::updateValue('PAYPAL_METHOD', 'EC');
        Configuration::updateValue('PAYPAL_EXPRESS_CHECKOUT', 1);
        Configuration::updateValue('PAYPAL_USERNAME_SANDBOX', "claloum-facilitator_api1.202-ecommerce.com");
        Configuration::updateValue('PAYPAL_PSWD_SANDBOX', "2NRPZ3FZQXN9LY2N");
        Configuration::updateValue('PAYPAL_SIGNATURE_SANDBOX', "AFcWxV21C7fd0v3bYYYRCpSSRl31Am6xsFqhy1VTTuSmPwEstqKmFDaX");
        Configuration::updateValue('PAYPAL_SANDBOX_ACCESS', 1);*/
        $this->_postProcess();
        $country_default = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));


        $lang = $this->context->country->iso_code;
        $img_esc = $this->_path."/views/img/ECShortcut/".Tools::strtolower($lang)."/checkout.png";
        if (!file_exists(_PS_ROOT_DIR_.$img_esc)) {
            $img_esc = "/modules/paypal/views/img/ECShortcut/us/checkout.png";
        }

        $this->context->smarty->assign(array(
            'path' => $this->_path,
            'active_products' => $this->express_checkout,
            'return_url' => $this->module_link,
            'country' => Country::getNameById($this->context->language->id, $this->context->country->id),
            'localization' => $this->context->link->getAdminLink('AdminLocalization', true),
            'preference' => $this->context->link->getAdminLink('AdminPreferences', true),
            'paypal_card' => Configuration::get('PAYPAL_API_CARD'),
            'iso_code' => $lang,
            'img_checkout' => $img_esc,
            'PAYPAL_SANDBOX_CLIENTID' => Configuration::get('PAYPAL_SANDBOX_CLIENTID'),
            'PAYPAL_SANDBOX_SECRET' => Configuration::get('PAYPAL_SANDBOX_SECRET'),
            'PAYPAL_LIVE_CLIENTID' => Configuration::get('PAYPAL_LIVE_CLIENTID'),
            'PAYPAL_LIVE_SECRET' => Configuration::get('PAYPAL_LIVE_SECRET'),
        ));

        if ($country_default == "FR" || $country_default == "GB" || $country_default == "IT" || $country_default == "ES") {
            $this->context->smarty->assign(array(
                'braintree_available' => true,
            ));
        } elseif ($country_default == "DE") {
            $this->context->smarty->assign(array(
                'ppp_available' => true,
            ));
        }


        $fields_form = array();
        $btn_mode = version_compare(_PS_VERSION_, '1.6', '<') ? 'radio' : 'switch';
        $inputs = array(
            array(
                'type' => $btn_mode,
                'label' => $this->l('Activate sandbox'),
                'name' => 'paypal_sandbox',
                'is_bool' => true,
                'hint' => $this->l('Set up a test environment in your PayPal account (only if you are a developer)'),
                'values' => array(
                    array(
                        'id' => 'paypal_sandbox_on',
                        'value' => 1,
                        'label' => $this->l('Enabled'),
                    ),
                    array(
                        'id' => 'paypal_sandbox_off',
                        'value' => 0,
                        'label' => $this->l('Disabled'),
                    )
                ),
            ),
        );
        $fields_value = array(
            'paypal_sandbox' => Configuration::get('PAYPAL_SANDBOX'),
        );

        $method_name = Configuration::get('PAYPAL_METHOD');
        $config = '';
        if ($method_name) {
            $method = AbstractMethodPaypal::load($method_name);

            $config = $method->getConfig($this);
            $inputs = array_merge($inputs, $config['inputs']);
            $fields_value = array_merge($fields_value, $config['fields_value']);
        }

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('MODULE SETTINGS'),
                'icon' => 'icon-cogs',
            ),
            'input' => $inputs,
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right button',
            ),
        );
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'paypal_config';
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->tpl_vars = array(
            'fields_value' => $fields_value,
            'id_language' => $this->context->language->id,
            'back_url' => $this->module_link.'#paypal_params'
        );
        $form = $helper->generateForm($fields_form);


        if (count($this->errors)) {
            $this->message .= $this->errors;
        } elseif (Configuration::get('PAYPAL_METHOD') && Configuration::get('PAYPAL_SANDBOX') == 1) {
            if (Configuration::get('PAYPAL_METHOD') == 'BT') {
                $this->message .= $this->displayWarning($this->l('Your Braintree account is currently configured to accept payments on the Sandbox (test environment). Any transaction will be fictitious. Disable the option, to accept actual payments (production environment) and log in with your Braintree credentials'));
            } else {
                $this->message .= $this->displayWarning($this->l('Your PayPal account is currently configured to accept payments on the Sandbox (test environment). Any transaction will be fictitious. Disable the option, to accept actual payments (production environment) and log in with your PayPal credentials'));
            }
        } elseif (Configuration::get('PAYPAL_METHOD') && Configuration::get('PAYPAL_SANDBOX') == 0) {
            if (Configuration::get('PAYPAL_METHOD') == 'BT') {
                $this->message .= $this->displayConfirmation($this->l('Your Braintree account is properly connected, you can now receive payments'));
            } else {
                $this->message .= $this->displayConfirmation($this->l('Your PayPal account is properly connected, you can now receive payments'));
            }
        }

        $this->context->controller->addCSS($this->_path.'views/css/paypal-bo.css', 'all');
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $this->context->controller->addJS($this->_path.'views/js/paypal_bo.js', 'all');
            $this->context->controller->addCSS($this->_path.'views/css/paypal-bo15.css', 'all');
        }

        $result = $this->message;
        if (isset($config['block_info'])) {
            $result .= $config['block_info'];
        }
        $result .= $this->display(__FILE__, 'views/templates/admin/configuration.tpl').$form;
        if (isset($config['form'])) {
            $result .= $config['form'];
        }

        return $result;
    }


    private function _postProcess()
    {
        if (Tools::isSubmit('paypal_config')) {
            Configuration::updateValue('PAYPAL_SANDBOX', Tools::getValue('paypal_sandbox'));
        }

        if (Tools::getValue('method')) {
            $method_name = Tools::getValue('method');
        } elseif (Tools::getValue('active_method')) {
            $method_name = Tools::getValue('active_method');
        } else {
            $method_name = Configuration::get('PAYPAL_METHOD');
        }

        if ($method_name) {
            $method = AbstractMethodPaypal::load($method_name);
            $method->setConfig($_GET + $_POST);
        }
    }

    public function hookPayment($params)
    {
        $this->context->smarty->assign(array(
            'advancedEU'=> false,
        ));
        $method = AbstractMethodPaypal::load(Configuration::get('PAYPAL_METHOD'));
        return $method->renderPayment($params,$this);
    }

    public function hookDisplayPaymentEU($params)
    {
        if (!$this->active) {
            return;
        }

        if ($this->hookPayment($params) == null) {
            return null;
        }


        $this->context->smarty->assign(array(
            'advancedEU'=> true,
        ));

        $method = AbstractMethodPaypal::load(Configuration::get('PAYPAL_METHOD'));
        return $method->renderPaymentEU($this);
    }

    public function hookHeader()
    {
        if (Tools::getValue('controller') == "order" || Tools::getValue('controller') == "orderopc") {
            if (Configuration::get('PAYPAL_METHOD') == 'BT') {
                if (Configuration::get('PAYPAL_BRAINTREE_ENABLED')) {
                    $this->context->controller->addJqueryPlugin('fancybox');
                    $this->context->controller->addJS('https://js.braintreegateway.com/web/3.24.0/js/client.min.js');
                    $this->context->controller->addJS('https://js.braintreegateway.com/web/3.24.0/js/hosted-fields.min.js');
                    $this->context->controller->addJS('https://js.braintreegateway.com/web/3.24.0/js/data-collector.min.js');
                    $this->context->controller->addJS('https://js.braintreegateway.com/web/3.24.0/js/three-d-secure.min.js');
                    $this->context->controller->addCSS($this->_path.'views/css/braintree.css', 'all');
                    $this->context->controller->addJS($this->_path.'views/js/payment_bt.js', 'all');
                }
                if (Configuration::get('PAYPAL_BY_BRAINTREE')) {
                    $this->context->controller->addJS('https://www.paypalobjects.com/api/checkout.js');
                    $this->context->controller->addJS('https://js.braintreegateway.com/web/3.24.0/js/paypal-checkout.min.js');
                    $this->context->controller->addJS($this->_path.'views/js/payment_pbt.js', 'all');
                }
            }
            if (Configuration::get('PAYPAL_METHOD') == 'EC') {
                $this->context->controller->addCSS($this->_path.'views/css/paypal-ec.css', 'all');
                if (Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT') && isset($this->context->cookie->paypal_ecs)) {
                    $this->context->controller->addJS($this->_path.'views/js/ec_shortcut_payment.js');
                }
                if (Configuration::get('PAYPAL_EC_IN_CONTEXT')) {
                    $this->context->controller->addJS('https://www.paypalobjects.com/api/checkout.js');
                    $this->context->controller->addJS($this->_path.'views/js/ec_in_context.js');
                }
            }
            if (Configuration::get('PAYPAL_METHOD') == 'PPP') {
                $this->context->controller->addCSS($this->_path.'views/css/paypal-plus.css', 'all');
                if (Configuration::get('PAYPAL_PLUS_ENABLED')) {
                    $this->context->controller->addJS('https://www.paypalobjects.com/webstatic/ppplus/ppplus.min.js');
                    $this->context->controller->addJS($this->_path.'views/js/payment_ppp.js');
                    $this->context->controller->addJqueryPlugin('fancybox');
                }
            }
        }
    }

    public function hookDisplayBackOfficeHeader()
    {
        if (Configuration::get('PAYPAL_METHOD') == 'BT')
        {
            $diff_cron_time = date_diff(date_create('now'), date_create(Configuration::get('PAYPAL_CRON_TIME')));
            if ($diff_cron_time->d > 0 || $diff_cron_time->h > 4) {
                $bt_orders = PaypalOrder::getPaypalBtOrdersIds();
                if (!$bt_orders) {
                    return true;
                }

                $method = AbstractMethodPaypal::load('BT');
                $transactions = $method->searchTransactions($bt_orders);

                foreach ($transactions as $transaction) {
                    $paypal_order_id = PaypalOrder::getIdOrderByTransactionId($transaction->id);
                    $paypal_order = PaypalOrder::loadByOrderId($paypal_order_id);
                    $ps_order = new Order($paypal_order_id);
                    switch ($transaction->status) {
                        case 'declined':
                            $paypal_order->payment_status = $transaction->status;
                            $ps_order->setCurrentState(Configuration::get('PS_OS_ERROR'));
                            break;
                        case 'settled':
                            $paypal_order->payment_status = $transaction->status;
                            $ps_order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
                            break;
                        case 'settling': // waiting
                        case 'submit_for_settlement': //waiting
                        default:
                            // do nothing and check later one more time
                            break;
                    }
                    $paypal_order->update();
                }
                Configuration::updateValue('PAYPAL_CRON_TIME', date('Y-m-d H:i:s'));
            }
        }
    }

    public function hookActionObjectCurrencyAddAfter($params)
    {
        if (Configuration::get('PAYPAL_METHOD') == 'BT') {
            $mode = Configuration::get('PAYPAL_SANDBOX') ? 'SANDBOX' : 'LIVE';
            $merchant_accounts = (array)Tools::jsonDecode(Configuration::get('PAYPAL_' . $mode . '_BT_ACCOUNT_ID'));
            $method_bt = AbstractMethodPaypal::load('BT');
            $merchant_account = $method_bt->createForCurrency($params['object']->iso_code);

            if ($merchant_account) {
                $merchant_accounts[$params['object']->iso_code] = $merchant_account[$params['object']->iso_code];
                Configuration::updateValue('PAYPAL_' . $mode . '_BT_ACCOUNT_ID', Tools::jsonEncode($merchant_accounts));
            }
        }
    }

    protected function assignInfoPaypalPlus()
    {
        $ppplus = AbstractMethodPaypal::load('PPP');
        try {
            $result = $ppplus->init(true);
            $this->context->cookie->__set('paypal_plus_payment', $result['payment_id']);
        } catch (Exception $e) {
            return false;
        }
        $address_invoice = new Address($this->context->cart->id_address_invoice);
        $country_invoice = new Country($address_invoice->id_country);

        $this->context->smarty->assign(array(
            'pppSubmitUrl'=> $this->context->link->getModuleLink('paypal', 'pppValidation', array(), true),
            'approval_url_ppp'=> $result['approval_url'],
            'baseDir' => $this->context->link->getBaseLink($this->context->shop->id, true),
            'path' => $this->_path,
            'mode' => Configuration::get('PAYPAL_SANDBOX')  ? 'sandbox' : 'live',
            'ppp_language_iso_code' => $this->context->language->iso_code,
            'ppp_country_iso_code' => $country_invoice->iso_code,
            'ajax_patch_url' => $this->context->link->getModuleLink('paypal', 'pppPatch', array(), true),
        ));
        return true;
    }

    protected function generateFormPaypalBt()
    {
        $amount = $this->context->cart->getOrderTotal();

        $braintree = AbstractMethodPaypal::load('BT');
        $clientToken = $braintree->init(true);
        $this->context->smarty->assign(array(
            'braintreeToken'=> $clientToken,
            'braintreeSubmitUrl'=> $this->context->link->getModuleLink('paypal', 'btValidation', array(), true),
            'braintreeAmount'=> $amount,
            'baseDir' => $this->context->link->getBaseLink($this->context->shop->id, true),
            'path' => $this->_path,
            'mode' => $braintree->mode == 'SANDBOX' ? Tools::strtolower($braintree->mode) : 'production',
        ));

        return $this->context->smarty->fetch('module:paypal/views/templates/front/payment_pb.tpl');
    }


    protected function generateFormBt()
    {

        $amount = $this->context->cart->getOrderTotal();

        $braintree = AbstractMethodPaypal::load('BT');
        $clientToken = $braintree->init(true);
        $check3DS = 0;
        $required_3ds_amount = Tools::convertPrice(Configuration::get('PAYPAL_3D_SECURE_AMOUNT'), Currency::getCurrencyInstance((int)$this->context->currency->id));
        if (Configuration::get('PAYPAL_USE_3D_SECURE') && $amount > $required_3ds_amount) {
            $check3DS = 1;
        }
        $this->context->smarty->assign(array(
            'error_msg'=> Tools::getValue('bt_error_msg'),
            'braintreeToken'=> $clientToken,
            'braintreeSubmitUrl'=> $this->context->link->getModuleLink('paypal', 'btValidation', array(), true),
            'braintreeAmount'=> $amount,
            'check3Dsecure'=> $check3DS,
            'baseDir' => $this->context->link->getBaseLink($this->context->shop->id, true),
        ));


        return $this->context->smarty->fetch('module:paypal/views/templates/front/payment_bt.tpl');
    }

    public function hookDisplayOrderConfirmation($params)
    {
        $paypal_order = PaypalOrder::loadByOrderId((int) Tools::getValue('id_order'));

        if (!Validate::isLoadedObject($paypal_order)) {
            return;
        }
        $this->context->smarty->assign(array(
            'transaction_id' => $paypal_order->id_transaction,
            'method' => $paypal_order->method,
            'reference' => $params['objOrder']->reference,
            'total_paid' => Tools::displayPrice((float)$paypal_order->total_paid, $this->context->currency),
        ));
        if($paypal_order->method == 'PPP' && $paypal_order->payment_tool == 'PAY_UPON_INVOICE')
        {
            $method = AbstractMethodPaypal::load('PPP');
            try{
                $this->context->smarty->assign('ppp_information',$method->getInstructionInfo($paypal_order->id_payment));
            } catch (Exception $e) {
                $this->context->smarty->assign('error_msg',$this->l('We are not able to verify if payment was successful. Please check if you have received confirmation from PayPal.'));
            }

        }
        $this->context->controller->addJS($this->_path.'/views/js/order_confirmation.js');

        return $this->display(__FILE__, 'views/templates/hook/order_confirmation.tpl');
    }


    public function hookDisplayFooterProduct()
    {
        if ('product' !== $this->context->controller->php_self || Configuration::get('PAYPAL_METHOD') != 'EC' || !Configuration::get('PAYPAL_EXPRESS_CHECKOUT_SHORTCUT')) {
            return false;
        }
        $method = AbstractMethodPaypal::load('EC');
        return $method->renderExpressCheckoutShortCut($this->context, 'EC');
    }

    public function validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown', $message = null, $transaction = array(), $currency_special = null, $dont_touch_amount = false, $secure_key = false, Shop $shop = null)
    {
        $this->amount_paid_paypal = (float)$amount_paid;

        $cart = new Cart((int) $id_cart);
        $total_ps = (float)$cart->getOrderTotal(true, Cart::BOTH);
        if ($amount_paid > $total_ps+0.10 || $amount_paid < $total_ps-0.10) {
            $total_ps = $amount_paid;
        }
        parent::validateOrder(
            (int) $id_cart,
            (int) $id_order_state,
            (float) $total_ps,
            $payment_method,
            $message,
            array('transaction_id' => $transaction['transaction_id']),
            $currency_special,
            $dont_touch_amount,
            $secure_key,
            $shop
        );
        if (Tools::version_compare(_PS_VERSION_, '1.7.1.0', '>')) {
            $order = Order::getByCartId($id_cart);
        } else {
            $id_order = Order::getOrderByCartId($id_cart);
            $order = new Order($id_order);
        }
        if (isset($amount_paid) && $amount_paid != 0 && $order->total_paid != $amount_paid) {
            $order->total_paid = $amount_paid;
            $order->total_paid_real = $amount_paid;
            $order->total_paid_tax_incl = $amount_paid;
            $order->update();

            $sql = 'UPDATE `'._DB_PREFIX_.'order_payment`
		    SET `amount` = '.(float)$amount_paid.'
		    WHERE  `order_reference` = "'.pSQL($order->reference).'"';
            Db::getInstance()->execute($sql);
        }

        $paypal_order = new PaypalOrder();
        $paypal_order->id_order = $this->currentOrder;
        $paypal_order->id_cart = $id_cart;
        $paypal_order->id_transaction = $transaction['transaction_id'];
        $paypal_order->id_payment = $transaction['id_payment'];
        $paypal_order->client_token = $transaction['client_token'];
        $paypal_order->payment_method = $transaction['payment_method'];
        $paypal_order->currency = $transaction['currency'];
        $paypal_order->total_paid = (float) $amount_paid;
        $paypal_order->payment_status = $transaction['payment_status'];
        $paypal_order->total_prestashop = (float) $total_ps;
        $paypal_order->method = $transaction['method'];
        $paypal_order->payment_tool = isset($transaction['payment_tool']) ? $transaction['payment_tool'] : '';
        $paypal_order->save();

        if ($transaction['capture']) {
            $paypal_capture = new PaypalCapture();
            $paypal_capture->id_paypal_order = $paypal_order->id;
            $paypal_capture->save();
        }
    }


    public function hookDisplayAdminOrder($params)
    {
        $id_order = $params['id_order'];
        $order = new Order((int)$id_order);
        $paypal_msg = '';
        $paypal_order = PaypalOrder::loadByOrderId($id_order);
        $paypal_capture = PaypalCapture::loadByOrderPayPalId($paypal_order->id);

        if (!Validate::isLoadedObject($paypal_order)) {
            return false;
        }

        if (Tools::getValue('not_payed_capture')) {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('You couldn\'t refund order, it\'s not payed yet.').'</p>'
            );
        }
        if (Tools::getValue('error_refund')) {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('We have unexpected problem during refund operation. See massages for more details').'</p>'
            );
        }
        if (Tools::getValue('cancel_failed')) {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('We have unexpected problem during cancel operation. See massages for more details').'</p>'
            );
        }
        if ($order->current_state == Configuration::get('PS_OS_REFUND') &&  $paypal_order->payment_status == 'Refunded') {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('Your order is fully refunded by PayPal.').'</p>'
            );
        }
        if ($order->current_state == Configuration::get('PS_OS_PAYMENT') && Validate::isLoadedObject($paypal_capture) && $paypal_capture->id_capture) {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('Your order is fully captured by PayPal.').'</p>'
            );
        }
        if (Tools::getValue('error_capture')) {
            $paypal_msg .= $this->displayWarning(
                '<p class="paypal-warning">'.$this->l('We have unexpected problem during capture operation. See massages for more details').'</p>'
            );
        }

        if ($paypal_order->total_paid != $paypal_order->total_prestashop) {
            $preferences = $this->context->link->getAdminLink('AdminPreferences', true);
            $paypal_msg .= $this->displayWarning('<p class="paypal-warning">'.$this->l('Product pricing has been modified as your rounding settings aren\'t compliant with PayPal.').' '.
                $this->l('To avoid automatic rounding to customer for PayPal payments, please update your rounding settings.').' '.
                '<a target="_blank" href="'.$preferences.'">'.$this->l('Reed more.').'</a></p>'
            );
        }

        return $paypal_msg.$this->display(__FILE__, 'views/templates/hook/paypal_order.tpl');
    }

    public function hookActionBeforeCartUpdateQty($params)
    {
        if (isset($this->context->cookie->paypal_ecs) || isset($this->context->cookie->paypal_ecs_payerid)) {
            //unset cookie of payment init if it's no more same cart
            Context::getContext()->cookie->__unset('paypal_ecs');
            Context::getContext()->cookie->__unset('paypal_ecs_payerid');
        }
    }

    public function hookActionOrderStatusUpdate(&$params)
    {

        $paypal_order = PaypalOrder::loadByOrderId($params['id_order']);

        if (!Validate::isLoadedObject($paypal_order)) {
            return false;
        }
        $method = AbstractMethodPaypal::load($paypal_order->method);
        $orderMessage = new Message();
        $orderMessage->message = "";
        $ex_detailed_message = '';
        if ($params['newOrderStatus']->id == Configuration::get('PS_OS_CANCELED')) {
            if ($paypal_order->method == "PPP") {
                return;
            }
            $orderPayPal = PaypalOrder::loadByOrderId($params['id_order']);
            $paypalCapture = PaypalCapture::loadByOrderPayPalId($orderPayPal->id);

            try {
                $response_void = $method->void(array('authorization_id'=>$orderPayPal->id_transaction));
            } catch (PayPal\Exception\PPConnectionException $e) {
                $ex_detailed_message = $this->l('Error connecting to ') . $e->getUrl();
            } catch (PayPal\Exception\PPMissingCredentialException $e) {
                $ex_detailed_message = $e->errorMessage();
            } catch (PayPal\Exception\PPConfigurationException $e) {
                $ex_detailed_message = $this->l('Invalid configuration. Please check your configuration file');
            }
            if ($response_void['success']) {
                $paypalCapture->result = 'voided';
                $paypalCapture->save();
                $orderPayPal->payment_status = 'voided';
                $orderPayPal->save();
            } else {
                foreach ($response_void as $key => $msg) {
                    $orderMessage->message .= $key." : ".$msg.";\r";
                }
                $orderMessage->id_order = $params['id_order'];
                $orderMessage->id_customer = $this->context->customer->id;
                $orderMessage->private = 1;
                if ($orderMessage->message) {
                    $orderMessage->add();
                }
                Tools::redirect($_SERVER['HTTP_REFERER'].'&cancel_failed=1');
            }

            if ($ex_detailed_message) {
                $orderMessage->message = $ex_detailed_message;
            } else {
                foreach ($response_void as $key => $msg) {
                    $orderMessage->message .= $key." : ".$msg.";\r";
                }
            }
            $orderMessage->id_order = $params['id_order'];
            $orderMessage->id_customer = $this->context->customer->id;
            $orderMessage->private = 1;
            if ($orderMessage->message) {
                $orderMessage->add();
            }
        }

        if ($params['newOrderStatus']->id == Configuration::get('PS_OS_REFUND')) {
            $capture = PaypalCapture::loadByOrderPayPalId($paypal_order->id);
            if (Validate::isLoadedObject($capture) && !$capture->id_capture && $capture->result != "completed_before_upgrade") {
                $orderMessage = new Message();
                $orderMessage->message = $this->l('You couldn\'t refund order, it\'s not payed yet.');
                $orderMessage->id_order = $params['id_order'];
                $orderMessage->id_customer = $this->context->customer->id;
                $orderMessage->private = 1;
                $orderMessage->add();
                Tools::redirect($_SERVER['HTTP_REFERER'].'&not_payed_capture=1');
            }
            $status = '';
            if ($paypal_order->method == "BT") {
                $status = $method->getTransactionStatus($paypal_order->id_transaction);
            }

            if ($paypal_order->method == "BT" && $status == "submitted_for_settlement") {
                try {
                    $refund_response = $method->void(array('authorization_id'=>$paypal_order->id_transaction));
                } catch (PayPal\Exception\PPConnectionException $e) {
                    $ex_detailed_message = $this->l('Error connecting to ') . $e->getUrl();
                } catch (PayPal\Exception\PPMissingCredentialException $e) {
                    $ex_detailed_message = $e->errorMessage();
                } catch (PayPal\Exception\PPConfigurationException $e) {
                    $ex_detailed_message = $this->l('Invalid configuration. Please check your configuration file');
                }
                if ($refund_response['success']) {
                    $capture->result = 'voided';
                    $paypal_order->payment_status = 'voided';
                }
            } else {
                try {
                    $refund_response = $method->refund();
                } catch (PayPal\Exception\PPConnectionException $e) {
                    $ex_detailed_message = $this->l('Error connecting to ') . $e->getUrl();
                } catch (PayPal\Exception\PPMissingCredentialException $e) {
                    $ex_detailed_message = $e->errorMessage();
                } catch (PayPal\Exception\PPConfigurationException $e) {
                    $ex_detailed_message = $this->l('Invalid configuration. Please check your configuration file');
                } catch (PayPal\Exception\PayPalConnectionException $e) {
                    $decoded_message = Tools::jsonDecode($e->getData());
                    $ex_detailed_message = $decoded_message->message;
                } catch (PayPal\Exception\PayPalInvalidCredentialException $e) {
                    $ex_detailed_message = $e->errorMessage();
                } catch (PayPal\Exception\PayPalMissingCredentialException $e) {
                    $ex_detailed_message = $this->l('Invalid configuration. Please check your configuration file');
                } catch (Exception $e) {
                    $ex_detailed_message = $e->errorMessage();
                }

                if ($refund_response['success']) {
                    $capture->result = 'refunded';
                    $paypal_order->payment_status = 'refunded';
                }
            }

            if ($refund_response['success']) {
                $capture->save();
                $paypal_order->save();
            }

            if ($ex_detailed_message) {
                $orderMessage->message = $ex_detailed_message;
            } else {
                foreach ($refund_response as $key => $msg) {
                    $orderMessage->message .= $key." : ".$msg.";\r";
                }
            }
            $orderMessage->id_order = $params['id_order'];
            $orderMessage->private = 1;
            $orderMessage->id_customer = $this->context->customer->id;
            if ($orderMessage->message) {
                $orderMessage->add();
            }
            if (!isset($refund_response['already_refunded']) && !isset($refund_response['success'])) {
                Tools::redirect($_SERVER['HTTP_REFERER'].'&error_refund=1');
            }
        }

        if ($params['newOrderStatus']->id == Configuration::get('PS_OS_PAYMENT')) {
            $capture = PaypalCapture::loadByOrderPayPalId($paypal_order->id);
            if (!Validate::isLoadedObject($capture)) {
                return false;
            }

            try {
                $capture_response = $method->confirmCapture();
            } catch (PayPal\Exception\PPConnectionException $e) {
                $ex_detailed_message = $this->l('Error connecting to ') . $e->getUrl();
            } catch (PayPal\Exception\PPMissingCredentialException $e) {
                $ex_detailed_message = $e->errorMessage();
            } catch (PayPal\Exception\PPConfigurationException $e) {
                $ex_detailed_message = $this->l('Invalid configuration. Please check your configuration file');
            }

            if (isset($capture_response['success'])) {
                $paypal_order->payment_status = $capture_response['status'];
                $paypal_order->save();
            }
            if ($ex_detailed_message) {
                $orderMessage->message = $ex_detailed_message;
            } else {
                foreach ($capture_response as $key => $msg) {
                    $orderMessage->message .= $key." : ".$msg.";\r";
                }
            }

            $orderMessage->id_order = $params['id_order'];
            $orderMessage->id_customer = $this->context->customer->id;
            $orderMessage->private = 1;
            if ($orderMessage->message) {
                $orderMessage->add();
            }

            if (!isset($capture_response['already_captured']) && !isset($capture_response['success'])) {
                Tools::redirect($_SERVER['HTTP_REFERER'].'&error_capture=1');
            }
        }
    }

    public function getPartnerInfo($method)
    {
        $return_url = $this->getBaseLink().basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&active_method='.Tools::getValue('method');

        if ($this->context->country->iso_code == "CN") {
            $country = "C2";
        } else {
            $country = $this->context->country->iso_code;
        }

        $partner_info = array(
            'email'         => $this->context->employee->email,
            'language'      => $this->context->language->iso_code.'_'.Tools::strtoupper($this->context->country->iso_code),
            'shop_url'      => Tools::getShopDomainSsl(true),
            'address1'      => Configuration::get('PS_SHOP_ADDR1', null, null, null, ''),
            'address2'      => Configuration::get('PS_SHOP_ADDR2', null, null, null, ''),
            'city'          => Configuration::get('PS_SHOP_CITY', null, null, null, ''),
            'country_code'  => Tools::strtoupper($country),
            'postal_code'   => Configuration::get('PS_SHOP_CODE', null, null, null, ''),
            'state'         => Configuration::get('PS_SHOP_STATE_ID', null, null, null, ''),
            'return_url'    => $return_url,
            'first_name'    => $this->context->employee->firstname,
            'last_name'     => $this->context->employee->lastname,
            'shop_name'     => Configuration::get('PS_SHOP_NAME', null, null, null, ''),
            'ref_merchant'  => 'PrestaShop_'.(getenv('PLATEFORM') == 'PSREADY' ? 'Ready':''),
        );

        $sdk = new PaypalSDK(Configuration::get('PAYPAL_SANDBOX'));

        $response = $sdk->getUrlOnboarding($partner_info);
        return $response;
    }

    public function getBtConnectUrl()
    {
        $return_url = $this->getBaseLink().basename(_PS_ADMIN_DIR_).'/'.$this->context->link->getAdminLink('AdminModules', true).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name.'&active_method='.Tools::getValue('method');
        $connect_params = array(
            'user_country' => $this->context->country->iso_code,
            'user_email' => Configuration::get('PS_SHOP_EMAIL'),
            'business_name' => Configuration::get('PS_SHOP_NAME'),
            'redirect_url' => $return_url,
        );
        $sdk = new BraintreeSDK(Configuration::get('PAYPAL_SANDBOX'));
        return $sdk->getUrlConnect($connect_params);
    }

    public function hookDisplayInvoiceLegalFreeText($params)
    {
        $paypal_order = PaypalOrder::loadByOrderId($params['order']->id);
        if (!Validate::isLoadedObject($paypal_order) || $paypal_order->method != 'PPP'
            || $paypal_order->payment_tool != 'PAY_UPON_INVOICE') {
            return;
        }

        $method = AbstractMethodPaypal::load('PPP');
        $information = $method->getInstructionInfo($paypal_order->id_payment);
        $tab = $this->l('The bank name').' : '.$information->recipient_banking_instruction->bank_name.'; 
        '.$this->l('Account holder name').' : '.$information->recipient_banking_instruction->account_holder_name.'; 
        '.$this->l('IBAN').' : '.$information->recipient_banking_instruction->international_bank_account_number.'; 
        '.$this->l('BIC').' : '.$information->recipient_banking_instruction->bank_identifier_code.'; 
        '.$this->l('Amount due / currency').' : '.$information->amount->value.' '.$information->amount->currency.';
        '.$this->l('Payment due date').' : '.$information->payment_due_date.'; 
        '.$this->l('Reference').' : '.$information->reference_number.'.';
        return $tab;
    }

    public function getBaseLink()
    {
        static $force_ssl = null;

        if ($force_ssl === null)
            $force_ssl = (Configuration::get('PS_SSL_ENABLED') && Configuration::get('PS_SSL_ENABLED_EVERYWHERE'));
        $ssl = $force_ssl;

        $shop = Context::getContext()->shop;

        $base = (($ssl && $this->ssl_enable) ? 'https://'.$shop->domain_ssl : 'http://'.$shop->domain);

        return $base.$shop->getBaseURI();
    }

    public function displayWarning($string)
    {
        $output = '
		<div class="module_warning alert alert-warning">
			'.$string.'
		</div>';
        return $output;
    }

    /**
     * Update context after customer login
     * @param Customer $customer Created customer
     */
    public function updateCustomer(Customer $customer)
    {
        $context = $this->context;
        $context->customer = $customer;
        $context->cookie->id_customer = (int) $customer->id;
        $context->cookie->customer_lastname = $customer->lastname;
        $context->cookie->customer_firstname = $customer->firstname;
        $context->cookie->passwd = $customer->passwd;
        $context->cookie->logged = 1;
        $customer->logged = 1;
        $context->cookie->email = $customer->email;
        $context->cookie->is_guest =  $customer->isGuest();
        $context->cart->secure_key = $customer->secure_key;

        if (Configuration::get('PS_CART_FOLLOWING') && (empty($context->cookie->id_cart) || Cart::getNbProducts($context->cookie->id_cart) == 0) && $idCart = (int) Cart::lastNoneOrderedCart($context->customer->id)) {
            $context->cart = new Cart($idCart);
        } else {
            $idCarrier = (int) $context->cart->id_carrier;
            $context->cart->id_carrier = 0;
            $context->cart->setDeliveryOption(null);
            $context->cart->id_address_delivery = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
            $context->cart->id_address_invoice = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
        }
        $context->cart->id_customer = (int) $customer->id;

        if (isset($idCarrier) && $idCarrier) {
            $deliveryOption = [$context->cart->id_address_delivery => $idCarrier.','];
            $context->cart->setDeliveryOption($deliveryOption);
        }

        $context->cart->save();
        $context->cookie->id_cart = (int) $context->cart->id;
        $context->cookie->write();
        $context->cart->autosetProductAddress();
    }

}
