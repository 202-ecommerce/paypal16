<p class="payment_module">
    <a href="{if !$in_context}{$action_url_paypal|escape:'htmlall':'UTF-8'|urldecode}{/if}"
       onclick="ECInContext()" title="{l s='Pay with Paypal' mod='paypal'}">
        <img src="{$path|escape:'htmlall':'UTF-8'}/views/img/paypal_sm.png" alt="{l s='Pay with Paypal' mod='paypal'}">
        {l s='Pay with Paypal' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
    </a>
    {if !$is_virtual}
        <p>{l s='Benefit from many PayPal advantages such as :' mod='paypal'}</p>
        <p><img src="{$path|escape:'htmlall':'UTF-8'}/views/img/protected.png" style="height: 43px; padding-right: 10px;">{l s='Your orders are protected' mod='paypal'}*</p>
        <p><img src="{$path|escape:'htmlall':'UTF-8'}/views/img/refund.png" style=" height: 43px; padding-right: 10px;">{l s='Return shipping refunded' mod='paypal'}*</p>
        <p><i>{l s='* See conditions on PayPal website' mod='paypal'}</i></p>
    {/if}
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