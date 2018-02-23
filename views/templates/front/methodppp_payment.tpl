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



{if $advancedEU}
    <form onsubmit="doPatchPPP(); return false;"></form>
    <div id="paypal-plus-payment">
    {include file="./payment_infos.tpl"}
    <div class="paypal-plus">
        <div id="ppplus" style="width: 100%;"> </div>
        <div id="bt-paypal-error-msg"></div>
    </div>
    </div>
{else}
    {if $version16}
        <div id="paypal-plus-payment">
            <p class="head">
                {l s='Pay with PayPal Plus' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}</p>
            {include file="./payment_infos.tpl"}
            <div class="paypal-plus">
                <div id="ppplus" style="width: 100%;"> </div>
                <div id="bt-paypal-error-msg"></div>
            </div>
        </div>
    {else}
        <p class="payment_module">
            <a title="{l s='Pay with PayPal Plus' mod='paypal'}">
                {l s='Pay with PayPal Plus' mod='paypal'}{if $advantages} | {l s='It\'s easy, simple and secure' mod='paypal'}{/if}
            </a>
            {include file="./payment_infos.tpl"}
        <div class="paypal-plus">
            <div id="ppplus" style="width: 100%;"> </div>
            <div id="bt-paypal-error-msg"></div>
        </div>
        </p>
    {/if}
{/if}


<script type="text/javascript">
    var advancedEU = '{$advancedEU|escape:'htmlall':'UTF-8'}';
    var ppp_approval_url = '{$approval_url_ppp|escape:'htmlall':'UTF-8'|urldecode}';
    var ppp_mode = '{$mode}';
    var ppp_language_iso_code = '{$ppp_language_iso_code}';
    var ppp_country_iso_code = '{$ppp_country_iso_code}';
    var ajax_patch_url = '{$ajax_patch_url|escape:'htmlall':'UTF-8'|urldecode}';
    var waiting_redirection = "{l s='In few seconds you will be redirected to PayPal. Please wait.' mod='paypal'}";
    var cgv_warning = "{l s='Please accept the terms and conditions' mod='paypal'}";



    if (ppp_mode == 'sandbox')
        showPui = true;
    else
        showPui = false;

    if (advancedEU)
        buttonLocation = "outside";
    else
        buttonLocation = "inside";

    var ppp = PAYPAL.apps.PPP({
        "approvalUrl": ppp_approval_url,
        "placeholder": "ppplus",
        "mode": ppp_mode,
        "language": ppp_language_iso_code,
        "country": ppp_country_iso_code,
        "buttonLocation": buttonLocation,
        "useraction": "continue",
        "showPuiOnSandbox": showPui,
        "onContinue" : function () {
            // eu-legal
            if($("#cgv-legal").length != 0){
                if($("#cgv-legal").is(":checked")){
                    doPatchPPP();
                }else{
                    alert(cgv_warning);
                }
            }else if($("#cgv").length != 0){ // advanced eu 161
                if($("#cgv").is(":checked")){
                    doPatchPPP();
                }else{
                    alert(cgv_warning);
                }
            }else{
                doPatchPPP();
            }
        },
    });
</script>