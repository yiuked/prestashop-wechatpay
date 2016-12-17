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

class QRrs
{
    public static $items = array();

    //----------------------------------------------------------------------
    public static function initRs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        foreach (self::$items as $rs) {
            if ($rs->pad != $pad) {
                continue;
            }
            if ($rs->nroots != $nroots) {
                continue;
            }
            if ($rs->mm != $symsize) {
                continue;
            }
            if ($rs->gfpoly != $gfpoly) {
                continue;
            }
            if ($rs->fcr != $fcr) {
                continue;
            }
            if ($rs->prim != $prim) {
                continue;
            }

            return $rs;
        }

        $rs = QRrsItem::initRsChar($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
        array_unshift(self::$items, $rs);

        return $rs;
    }
}
