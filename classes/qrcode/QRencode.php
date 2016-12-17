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

/*
* PHP QR Code encoder
*
* Config file, tuned-up for merged verion
*/

define('QR_CACHEABLE', false);       // use cache - more disk reads but less CPU power, masks and format templates are stored there
define('QR_CACHE_DIR', false);       // used when QR_CACHEABLE === true
define('QR_LOG_DIR', false);         // default error logs dir

define('QR_FIND_BEST_MASK', true);                                                          // if true, estimates best mask (spec. default, but extremally slow; set to false to significant performance boost but (propably) worst quality code
define('QR_FIND_FROM_RANDOM', 2);                                                       // if false, checks all masks available, otherwise value tells count of masks need to be checked, mask id are got randomly
define('QR_DEFAULT_MASK', 2);                                                               // when QR_FIND_BEST_MASK === false

define('QR_PNG_MAXIMUM_SIZE', 1024);                                                       // maximum allowed png image width (in pixels), tune to make sure GD and PHP can handle such big images

include_once 'QRtools.php';
include_once 'QRstr.php';
include_once 'QRcode.php';

QRtools::markTime('start');

include_once 'QRspec.php';
include_once 'QRimage.php';
include_once 'QRinput.php';
include_once 'QRinputItem.php';
include_once 'QRbitstream.php';
include_once 'QRsplit.php';
include_once 'QRrs.php';
include_once 'QRrsItem.php';
include_once 'QRrsblock.php';
include_once 'QRmask.php';
include_once 'QRrawcode.php';
include_once 'FrameFiller.php';
/*
* PHP QR Code encoder
*
* Main encoder classes.
*
* Based on libqrencode C library distributed under LGPL 2.1
* Copyright (C) 2006, 2007, 2008, 2009 Kentaro Fukuchi <fukuchi@megaui.net>
*
* PHP QR Code is distributed under LGPL 3
* Copyright (C) 2010 Dominik Dzienia <deltalab at poczta dot fm>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

class QRencode
{
    public $casesensitive = true;
    public $eightbit = false;

    public $version = 0;
    public $size = 3;
    public $margin = 4;

    public $structured = 0; // not supported yet

    public $level = QR_ECLEVEL_L;
    public $hint = QR_MODE_8;

    //----------------------------------------------------------------------
    public static function factory($level = QR_ECLEVEL_L, $size = 3, $margin = 4)
    {
        $enc = new QRencode();
        $enc->size = $size;
        $enc->margin = $margin;

        switch ($level.'') {
            case '0':
            case '1':
            case '2':
            case '3':
                    $enc->level = $level;
                break;
            case 'l':
            case 'L':
                    $enc->level = QR_ECLEVEL_L;
                break;
            case 'm':
            case 'M':
                    $enc->level = QR_ECLEVEL_M;
                break;
            case 'q':
            case 'Q':
                    $enc->level = QR_ECLEVEL_Q;
                break;
            case 'h':
            case 'H':
                    $enc->level = QR_ECLEVEL_H;
                break;
        }

        return $enc;
    }

    //----------------------------------------------------------------------
    public function encodeRAW($intext, $outfile = false)
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        return $code->data;
    }

    //----------------------------------------------------------------------
    public function encode($intext, $outfile = false)
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        QRtools::markTime('after_encode');

        if ($outfile!== false) {
            file_put_contents($outfile, join("\n", QRtools::binarize($code->data)));
        } else {
            return QRtools::binarize($code->data);
        }
    }

    //----------------------------------------------------------------------
    public function encodePNG($intext, $outfile = false, $saveandprint = false)
    {
        try {
            ob_start();
            $tab = $this->encode($intext);
            $err = ob_get_contents();
            ob_end_clean();

            if ($err != '') {
                QRtools::log($outfile, $err);
            }

            $maxSize = (int) (QR_PNG_MAXIMUM_SIZE / (count($tab)+2*$this->margin));

            QRimage::png($tab, $outfile, min(max(1, $this->size), $maxSize), $this->margin, $saveandprint);
        } catch (Exception $e) {
            QRtools::log($outfile, $e->getMessage());
        }
    }
}
