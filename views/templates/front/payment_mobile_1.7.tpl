{*
* 2010-2015 Yiuked
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
* Do not edit or add to this file if you wish to upgrade yiukedautoseo to newer
* versions in the future.
*
*  @author    Yiuked SA <yiuked@vip.qq.com>
*  @copyright 2010-2015 Yiuked
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{extends file='page.tpl'}
{block name='page_title'}
    {l s='WeChat Pay' mod='weixinpay'}
{/block}

{block name='page_content'}
    {if $nbProducts <= 0}
        <p class="warning">{l s='Your shopping cart is empty.' mod='weixinpay'}</p>
    {else}
        <div class="order-box">
            <div>
                <h3>{l s='Please confirm the payment button after you confirm to continue!' mod='weixinpay'}</h3>
                <p>{l s='After opening the WeChat payment, please complete the payment operation as soon as possible' mod='weixinpay'}</p>
            </div>
            <div>
                <div class="o-price-label">{l s='Total amount' mod='weixinpay'}</div>
                <div class="o-price-number"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></div>
            </div>
            <br>
            <p>
                <input type="hidden" value="1" name="unifiedOrder">
                <button class="btn btn-success blod" onclick="jsApiCall()">
                    <span><strong>{l s='Run WeChat payment' mod='weixinpay'} </strong><i class="icon-chevron-right right"></i></span>
                </button>
            </p>
            <div class="clearfix"></div>
        </div>
    {/if}
    <script language="javascript">
        var jsSDKPaymentUrl = '{$link->getModuleLink('weixinpay', 'jssdkpayment')|escape:'htmlall':'UTF-8'}';
        var openID = '{$openId|escape:'htmlall':'UTF-8'}';
        var rangArgs = "{$rand_args|escape:'htmlall':'UTF-8'}";
        var returnUrl = "{$return_url|escape:'htmlall':'UTF-8'}";
        function jsApiCall() {
            $.ajax({
                type: 'POST',
                url: jsSDKPaymentUrl,
                data: "jssdkUnifiedOrder=1&openID=" + openID,
                dataType: 'json',
                cache: false,
                success: function (result) {
                    if (result.statusCode == 1) {
                        WeixinJSBridge.invoke(
                            'getBrandWCPayRequest',
                            result.jsApiParameters,
                            function(res){
                                newRequest();
                            }
                        );
                    } else {
                        alert('Error');
                    }
                }
            });
        }
        function newRequest() {
            $.ajax({
                type: 'GET',
                url: prestashop.urls.base_url + 'modules/weixinpay/weixinpay-ajax.php',
                data: "rangargs=" + rangArgs,
                dataType: 'json',
                cache: false,
                success: function (result) {
                    if (result.status === 1) {
                        location.href = returnUrl.replace(/&amp;/g, "&");
                    } else {
                        newRequest();
                    }
                }
            });
        }
        function callpay() {
            if (typeof WeixinJSBridge == "undefined"){
                if( document.addEventListener ){
                    document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
                }else if (document.attachEvent){
                    document.attachEvent('WeixinJSBridgeReady', jsApiCall);
                    document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
                }
            }else{
                jsApiCall();
            }
        }
    </script>
{/block}