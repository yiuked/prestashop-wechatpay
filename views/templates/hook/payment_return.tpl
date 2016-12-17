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

{if $status == 'ok'}
<p>{l s='Your order on %s is complete.' sprintf=[$shop_name] mod='weixinpay'}
		<br />
		<br /><br />- {l s='Amount' mod='weixinpay'} <span class="price"><strong>{$total_to_pay|escape:'htmlall':'UTF-8'}</strong></span>
		{if !isset($reference)}
			<br /><br />- {l s='Your order number #%d.' sprintf=[$id_order] mod='weixinpay'}
		{else}
			<br /><br />- {l s='Your order reference %s.' sprintf=[$reference] mod='weixinpay'}
		{/if}		<br /><br />{l s='An email has been sent with this information.' mod='weixinpay'}
		<br />
		<br /><br />{l s='If you have questions, comments or concerns, please contact our' mod='weixinpay'} <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='expert customer support team' mod='weixinpay'}</a>.
	</p>
{else}
	<p class="warning">
		{l s='We noticed a problem with your order. If you think this is an error, feel free to contact our' mod='weixinpay'}
		<a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='expert customer support team' mod='weixinpay'}</a>.
	</p>
{/if}