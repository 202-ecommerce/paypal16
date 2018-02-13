{if $advancedEU}
    <div id="paypal-eu-paypal">
        {include file="./payment_infos.tpl"}
    </div>
    <form method="POST" action="{$action_url_paypal|escape:'htmlall':'UTF-8'|urldecode}"></form>
{else}
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
{/if}
<script>
    var merchant_id = "{$merchant_id|escape:'htmlall':'UTF-8'}";
    var environment = "{$environment|escape:'htmlall':'UTF-8'}";
    var url_token = "{$url_token|escape:'htmlall':'UTF-8'|urldecode}";
    var advancedEU = '{$advancedEU|escape:'htmlall':'UTF-8'}';

    $(document).ready(function(){
        if (advancedEU) {
            $(document).on('click', '.payment_module', function(e) {
                var ppp_el = $(this).parent().find('#paypal-eu-paypal');
                if (ppp_el.length) {               // Clicked element has a #paypal-eu-paypal child.
                    ppp_el.parent().stop().show();
                } else {                           // Clicked element doesn't contain #ppplusDiv child,
                    $(document).find('#paypal-eu-paypal').parent().stop().hide();
                }
            });
        }
    });
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
