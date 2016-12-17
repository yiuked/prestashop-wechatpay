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

class QRrsblock
{
    public $dataLength;
    public $data = array();
    public $eccLength;
    public $ecc = array();

    public function __construct($dl, $data, $el, &$ecc, QRrsItem $rs)
    {
        $rs->encodeRsChar($data, $ecc);

        $this->dataLength = $dl;
        $this->data = $data;
        $this->eccLength = $el;
        $this->ecc = $ecc;
    }
}
