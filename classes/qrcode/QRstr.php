<?php
/**
* PHP QR Code encoder
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
* @author    Kentaro Fukuchi <fukuchi@megaui.net>
* @copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
* @license   GNU Lesser General Public License
*/

// Encoding modes

define('QR_MODE_NUL', -1);
define('QR_MODE_NUM', 0);
define('QR_MODE_AN', 1);
define('QR_MODE_8', 2);
define('QR_MODE_KANJI', 3);
define('QR_MODE_STRUCTURE', 4);

// Levels of error correction.

define('QR_ECLEVEL_L', 0);
define('QR_ECLEVEL_M', 1);
define('QR_ECLEVEL_Q', 2);
define('QR_ECLEVEL_H', 3);

// Supported output formats

define('QR_FORMAT_TEXT', 0);
define('QR_FORMAT_PNG', 1);

class QRstr
{
    public static function set(&$srctab, $x, $y, $repl, $replLen = false)
    {
        $srctab[$y] = substr_replace($srctab[$y], ($replLen !== false)?Tools::substr($repl, 0, $replLen):$repl, $x, ($replLen !== false)?$replLen:count(str_split($repl)));
    }
}
