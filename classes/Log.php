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

require_once(WXP_MODDULE_DIR.'classes/CLogFileHandler.php');

class Log
{
    private $handler = null;
    private $level = 15;

    private static $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function init($handler = null, $level = 15)
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
            self::$instance->__setHandle($handler);
            self::$instance->__setLevel($level);
        }

        return self::$instance;
    }

    private function __setHandle($handler)
    {
        $this->handler = $handler;
    }

    private function __setLevel($level)
    {
        $this->level = $level;
    }

    public static function DEBUG($msg)
    {
        self::$instance->write(1, $msg);
    }

    public static function WARN($msg)
    {
        self::$instance->write(4, $msg);
    }

    public static function ERROR($msg)
    {
        $debugInfo = debug_backtrace();
        $stack = "[";
        foreach ($debugInfo as $val) {
            if (array_key_exists("file", $val)) {
                $stack .= ",file:" . $val["file"];
            }
            if (array_key_exists("line", $val)) {
                $stack .= ",line:" . $val["line"];
            }
            if (array_key_exists("function", $val)) {
                $stack .= ",function:" . $val["function"];
            }
        }
        $stack .= "]";
        self::$instance->write(8, $stack . $msg);
    }

    public static function INFO($msg)
    {
        self::$instance->write(2, $msg);
    }

    private function getLevelStr($level)
    {
        switch ($level) {
            case 1:
                return 'debug';
            case 2:
                return 'info';
            case 4:
                return 'warn';
            case 8:
                return 'error';
            default:
        }
    }

    protected function write($level, $msg)
    {
        if (($level & $this->level) == $level) {
            $msg = '['.date('Y-m-d H:i:s').']['.$this->getLevelStr($level).'] '.$msg."\n";
            $this->handler->write($msg);
        }
    }
}
