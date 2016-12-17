<?php
/**
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
*/

/**
 * @deprecated 1.5.0 This file is deprecated, use moduleFrontController instead
 */

/* SSL Management */
$useSSL = true;

require '../../config/config.inc.php';
Tools::displayFileAsDeprecated();

// init front controller in order to use Tools::redirect
$controller = new FrontController();
$controller->init();

Tools::redirect(Context::getContext()->link->getModuleLink('weixinpay', 'payment'));
