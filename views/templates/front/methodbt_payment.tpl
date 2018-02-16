{*
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
*}

{if 1}
<div class="row">
    <div class="col-xs-12 col-md-6">
        <div class="braintree-row-payment">
        <p class="payment_module braintree-card">
            <p class="head"><img src="{$path|escape:'htmlall':'UTF-8'}/views/img/mini-cards.png" alt="{l s='Pay with card' mod='paypal'}">
                {l s='Pay with card' mod='paypal'}</p>
            <form action="{$braintreeSubmitUrl|escape:'htmlall':'UTF-8'|urldecode}" id="braintree-form" method="post">
                <div id="block-card-number" class="block_field">
                    <div id="card-number" class="hosted_field"><div id="card-image"></div></div>

                </div>

                <div id="block-expiration-date" class="block_field half_block_field">
                    <div id="expiration-date" class="hosted_field"></div>
                </div>

                <div id="block-cvv" class="block_field half_block_field">
                    <div id="cvv" class="hosted_field"></div>
                </div>

                <input type="hidden" name="deviceData" id="deviceData"/>
                <input type="hidden" name="client_token" value="{$braintreeToken}">
                <input type="hidden" name="liabilityShifted" id="liabilityShifted"/>
                <input type="hidden" name="liabilityShiftPossible" id="liabilityShiftPossible"/>
                <input type="hidden" name="payment_method_nonce" id="payment_method_nonce"/>
                <input type="hidden" name="card_type" id="braintree_card_type"/>
                <input type="hidden" name="payment_method_bt" value="card-braintree"/>
                <div class="paypal_clear"></div>
                <input type="submit" value="{l s='Pay' mod='paypal'}"  id="braintree_submit"/>
                <div id="bt-card-error-msg"></div>
                <div id="logo_braintree_by_paypal"><img src="https://s3-us-west-1.amazonaws.com/bt-partner-assets/paypal-braintree.png" height="20px"></div>
            </form>
        </p>
        </div>
    </div>
</div>
{else}
    <p class="payment_module">
        <a href="#" title="{l s='Pay with card' mod='paypal'}">
            <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/mini-cards.png" alt="{l s='Pay with card' mod='paypal'}">
            {l s='Pay with card' mod='paypal'}
        </a>
    <div class="braintree-row-payment">
        <div class="payment_module braintree-card">
            <form action="{$braintreeSubmitUrl|escape:'htmlall':'UTF-8'|urldecode}" id="braintree-form" method="post">
                <div id="block-card-number" class="block_field">
                    <div id="card-number" class="hosted_field"><div id="card-image"></div></div>

                </div>

                <div id="block-expiration-date" class="block_field half_block_field">
                    <div id="expiration-date" class="hosted_field"></div>
                </div>

                <div id="block-cvv" class="block_field half_block_field">
                    <div id="cvv" class="hosted_field"></div>
                </div>

                <input type="hidden" name="deviceData" id="deviceData"/>
                <input type="hidden" name="client_token" value="{$braintreeToken}">
                <input type="hidden" name="liabilityShifted" id="liabilityShifted"/>
                <input type="hidden" name="liabilityShiftPossible" id="liabilityShiftPossible"/>
                <input type="hidden" name="payment_method_nonce" id="payment_method_nonce"/>
                <input type="hidden" name="card_type" id="braintree_card_type"/>
                <input type="hidden" name="payment_method_bt" value="card-braintree"/>
                <div class="paypal_clear"></div>
                <input type="submit" value="{l s='Pay' mod='paypal'}"  id="braintree_submit"/>
                <div id="bt-card-error-msg"></div>
                <div id="logo_braintree_by_paypal"><img src="https://s3-us-west-1.amazonaws.com/bt-partner-assets/paypal-braintree.png" height="20px"></div>
            </form>
        </div>
    </div>
    </p>
{/if}
{if $paypal_active}
{if 1}
    <div class="row">
    <div class="paypal-braintree-row-payment col-xs-12 col-md-6">
        <div class="payment_module paypal-braintree">
            <p class="payment_module braintree-paypal">
                <p class="head">
                    <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with paypal' mod='paypal'}">
                    {l s='Pay with paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
                </p>
                <form action="{$braintreeSubmitUrl|escape:'htmlall':'UTF-8'|urldecode}" id="paypal-braintree-form" method="post">
                    {include file="./payment_infos.tpl"}
                    <input type="hidden" name="payment_method_nonce" id="paypal_payment_method_nonce"/>
                    <input type="hidden" name="payment_method_bt" value="paypal-braintree"/>
                    <div id="paypal-button"></div>
                    <div id="paypal-vault-info"><p>{l s='You have to finish your payment done with your account PayPal:' mod='paypal'}</p></div>
                </form>
                <div id="bt-paypal-error-msg"></div>
            </p>
        </div>
    </div>
    </div>
{else}
    <p class="payment_module">
        <a title="{l s='Pay with paypal' mod='paypal'}">
            <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with paypal' mod='paypal'}">
            {l s='Pay with paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
        </a>
        <div class="paypal-braintree-row-payment">
            <div class="payment_module paypal-braintree">
                <form action="{$braintreeSubmitUrl|escape:'htmlall':'UTF-8'|urldecode}" id="paypal-braintree-form" method="post">
                    {include file="./payment_infos.tpl"}
                    <input type="hidden" name="payment_method_nonce" id="paypal_payment_method_nonce"/>
                    <input type="hidden" name="payment_method_bt" value="paypal-braintree"/>
                    <div id="paypal-button"></div>
                    <div id="paypal-vault-info"><p>{l s='You have to finish your payment done with your account PayPal:' mod='paypal'}</p></div>
                </form>
            <div id="bt-paypal-error-msg"></div>
            </div>
        </div>
    </p>
{/if}
{/if}

<script>
    var mode = '{$mode}';
    var authorization = '{$braintreeToken}';
    var bt_amount = {$braintreeAmount};
    var check3DS = {$check3Dsecure};
    var bt_translations = {
        client:"{l s='Error create Client' mod='paypal'}",
        card_nmb:"{l s='Card number' mod='paypal'}",
        cvc:"{l s='CVC' mod='paypal'}",
        date:"{l s='MM/YY' mod='paypal'}",
        hosted:"{l s='Error create Hosted fields' mod='paypal'}",
        empty:"{l s='All fields are empty! Please fill out the form.' mod='paypal'}",
        invalid:"{l s='Some fields are invalid :' mod='paypal'}",
        token:"{l s='Tokenization failed server side. Is the card valid?' mod='paypal'}",
        network:"{l s='Network error occurred when tokenizing.' mod='paypal'}",
        tkn_failed:"{l s='Tokenize failed' mod='paypal'}",
        https:"{l s='3D Secure requires HTTPS.' mod='paypal'}",
        load_3d:"{l s='Load 3D Secure Failed' mod='paypal'}",
        request_problem:"{l s='There was a problem with your request.' mod='paypal'}",
        failed_3d:"{l s='3D Secure Failed' mod='paypal'}",
        empty_field:"{l s='is empty.' mod='paypal'}",
        expirationDate:"{l s='Expiration Date' mod='paypal'}",
        number:"{l s='card number' mod='paypal'}",
        cvv:"{l s='CVV' mod='paypal'}",
        empty_nonce:"{l s='Click paypal button first' mod='paypal'}"
    };
    var waiting_redirection = "{l s='Payment in process. Please wait.' mod='paypal'}";
    initBraintreeCard();

</script>

{if $paypal_active}
    <script>
    initPaypalBraintree();
    </script>
{/if}