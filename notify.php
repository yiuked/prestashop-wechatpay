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
 * @author    Yiuked SA <yiuked@vip.qq.com>
 * @copyright 2010-2015 Yiuked
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

include(dirname(__FILE__) . '/../../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . 'weixinpay/defines.php');
require_once(_PS_MODULE_DIR_ . 'weixinpay/weixinpay.php');
require_once(WXP_MODDULE_DIR . 'classes/lib/WxPay.Notify.php');
require_once(WXP_MODDULE_DIR . 'classes/Log.php');

//初始化日志
$logHandler = new CLogFileHandler(WXP_MODDULE_DIR . WXP_MODDULE_LOGS . date('Y-m-d') . '.log');
$log = Log::init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
    public function notifyProcess($data, &$msg)
    {
        Log::DEBUG("call back(notify.php:notifyProcess):" . Tools::jsonEncode($data));
        if (array_key_exists("return_code", $data)
            && array_key_exists("result_code", $data)
            && $data["return_code"] == "SUCCESS"
            && $data["result_code"] == "SUCCESS"
        ) {
            $this->changeOrderStatus($data['out_trade_no'], $data['transaction_id']);
            return true;
        }
        return true;
    }

    public function changeOrderStatus($result_order, $transaction_id = "")
    {
        $orderPayments = OrderPayment::getByOrderReference($result_order);
        foreach ($orderPayments as $payment) {
            $payment->transaction_id = $transaction_id;
            $payment->update();
        }
        $orders = Order::getByReference($result_order);
        $isOk = true;
        if ($orders) {
            foreach ($orders as $order) {
                $isOk &= $this->changeOrderStatusSub($order->id);
            }
        }
        return $isOk;
    }

    public function changeOrderStatusSub($id_order)
    {

        if ($id_order) {
            $lastHistory = OrderHistory::getLastOrderState($id_order);
            if ($lastHistory->id == Configuration::get('PS_OS_PAYMENT')) {
                return true;
            }

            $history = new OrderHistory();
            $history->id_order = (int)$id_order;
            $history->changeIdOrderState(Configuration::get('PS_OS_PAYMENT'), (int)$id_order);
            if ($history->addWithemail()) {
                return true;
            }
        }
        return false;
    }
}

Log::DEBUG("call back:begin notify");
$notify = new PayNotifyCallBack();
$notify->handle();
