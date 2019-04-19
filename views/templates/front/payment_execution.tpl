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

{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'htmlall':'UTF-8'}"
       title="{l s='Go back to the Checkout' mod='weixinpay'}">{l s='Checkout' mod='weixinpay'}</a>
    <span class="navigation-pipe">{$navigationPipe|escape:'htmlall':'UTF-8'}</span>
    {l s='WeChat payment' mod='weixinpay'}
{/capture}
{assign var='current_step' value='payment'}{include file="$tpl_dir./order-steps.tpl"}
{if $nbProducts <= 0}
    <p class="warning">{l s='Your shopping cart is empty.' mod='weixinpay'}</p>
{else}
    <div class="order-box">
        <div class="o-left">
            <h3 class="o-title">{l s='Please pay in time so that your order will be processed as soon as possible!' mod='weixinpay'}</h3>
            <p class="o-tips">{l s='Please pay in' mod='weixinpay'}
                <span class="font-red"> {$expire_time|escape:'htmlall':'UTF-8'} {l s='minutes' mod='weixinpay'} </span>
                {l s='to complete the payment, otherwise the order will be automatically canceled.' mod='weixinpay'}
            </p>
        </div>
        <div class="o-right">
            <div class="o-price-label">{l s='Total amount' mod='weixinpay'}</div>
            <div class="o-price-number"><strong>{displayPrice price=$total}</strong></div>
        </div>
        <div class="clearfix"></div>
    </div>
    <h3>{l s='WeChat Pay' mod='weixinpay'}</h3>
    <div class="image-box">
        <div class="box-left">
            <img class="border-grey" src="{$payimage|escape:'htmlall':'UTF-8'}" alt="{l s='WeChat Pay' mod='weixinpay'}" width="300"/>
            <div class="pw-box-ft">
                <p>{l s='Use WeChat Scan' mod='weixinpay'}</p>
                <p>{l s='Scan 2-D barcode' mod='weixinpay'}</p>
            </div>
        </div>
    </div>
    </form>
{/if}
<script>
    var rangArgs = "{$rand_args|escape:'htmlall':'UTF-8'}";
    var returnUrl = "{$return_url|escape:'htmlall':'UTF-8'}";
    function newRequest() {
        $.ajax({
            type: 'GET',
            url: baseDir + 'modules/weixinpay/weixinpay-ajax.php',
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
    newRequest();
</script>