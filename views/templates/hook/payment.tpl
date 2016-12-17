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

<div class="row">
	<div class="col-xs-12">
		<p class="payment_module">
			{if $enable_h5pay && $mobile_device}
			<a class="winxinpay" href="{$link->getModuleLink('weixinpay', 'jssdkpayment')|escape:'htmlall':'UTF-8'}" title="{l s='Pay by WeChat payment' mod='weixinpay'}">
				{l s='Pay by WeChat payment' mod='weixinpay'}
			</a>
			{else}
			<a class="winxinpay" href="{$link->getModuleLink('weixinpay', 'payment')|escape:'htmlall':'UTF-8'}" title="{l s='Pay by WeChat payment' mod='weixinpay'}">
				{l s='Pay by WeChat payment' mod='weixinpay'}
			</a>
			{/if}
		</p>
	</div>
</div>