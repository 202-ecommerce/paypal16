<p class="payment_module">
    <a href="{if !$in_context}{$action_url_paypal|escape:'htmlall':'UTF-8'|urldecode}{/if}"
       onclick="ECInContext()" title="{l s='Pay with Paypal' mod='paypal'}">
        <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with Paypal' mod='paypal'}">
        {l s='Pay with Paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
    </a>
    {include file="./payment_infos.tpl"}
</p>
{if $card_active}
<p class="payment_module">
    <a href="{$action_url_card|escape:'htmlall':'UTF-8'|urldecode}" title="{l s='Pay with debit or credit card' mod='paypal'}">
        <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/logo_card.png" alt="{l s='Pay with debit or credit card' mod='paypal'}">
        {l s='Pay with debit or credit card' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
    </a>
    <p>{l s='You will be redirected to the PayPal website to process your card payment.' mod='paypal'}</p>
    <p>{l s='PayPal secures your payment and protect your financial information with strong encryption tools.' mod='paypal'}</p>
</p>
{/if}