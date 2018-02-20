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

{if $version16}
    <div class="col-xs-12 paypal-ec">
        <p class="payment_module">
            <a {if !$in_context} href="{$action_url_paypal|escape:'htmlall':'UTF-8'|urldecode}" {/if}
                    {if $in_context} onclick="ECInContext()"{/if} title="{l s='Pay with Paypal' mod='paypal'}">
                <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with Paypal' mod='paypal'}">
                {l s='Pay with Paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
            </a>
            {include file="./payment_infos.tpl"}
        </p>
    </div>
{else}
    <p class="payment_module ">
        <a {if !$in_context} href="{$action_url_paypal|escape:'htmlall':'UTF-8'|urldecode}" {/if}
                {if $in_context} onclick="ECInContext()"{/if} title="{l s='Pay with Paypal' mod='paypal'}">
            <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with Paypal' mod='paypal'}">
            {l s='Pay with Paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
        </a>
        {include file="./payment_infos.tpl"}
    </p>
{/if}

{if $card_active}
{if $version16}
    <div class="col-xs-12 paypal-ec">
        <p class="payment_module">
            <a href="{$action_url_card|escape:'htmlall':'UTF-8'|urldecode}" title="{l s='Pay with debit or credit card' mod='paypal'}">
                <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/logo_card.png" alt="{l s='Pay with debit or credit card' mod='paypal'}">
                {l s='Pay with debit or credit card' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
            </a>
            {include file="./payment_info_cards.tpl"}
        </p>
    </div>
{else}
    <p class="payment_module">
        <a href="{$action_url_card|escape:'htmlall':'UTF-8'|urldecode}" title="{l s='Pay with debit or credit card' mod='paypal'}">
            <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/logo_card.png" alt="{l s='Pay with debit or credit card' mod='paypal'}">
            {l s='Pay with debit or credit card' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
        </a>
        {include file="./payment_info_cards.tpl"}
    </p>
{/if}
{/if}

<script>
    var merchant_id = "{$merchant_id|escape:'htmlall':'UTF-8'}";
    var ec_environment = "{$environment|escape:'htmlall':'UTF-8'}";
    var url_token = "{$url_token|escape:'htmlall':'UTF-8'|urldecode}";
    window.paypalCheckoutReady = function() {
        paypal.checkout.setup(merchant_id, {
            environment: ec_environment,
        });
    };
</script>

{if isset($ec_sc_validation_url)}
    <p class="payment_module" id="paypal-es-checked">
        <a href="{$ec_sc_validation_url|escape:'htmlall':'UTF-8'|urldecode}" title="{l s='Pay with paypal express checkout' mod='paypal'}">
            <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with paypal express checkout' mod='paypal'}">
            {l s='Pay with paypal express checkout' mod='paypal'}
        </a>
    <p>{l s='You have already payed with PayPal express checkout.' mod='paypal'}</p>
    </p>
{/if}
