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

class QRtools
{
    //----------------------------------------------------------------------
    public static function binarize($frame)
    {
        $len = count($frame);
        foreach ($frame as &$frameLine) {
            for ($i=0; $i<$len; $i++) {
                $frameLine[$i] = (ord($frameLine[$i])&1)?'1':'0';
            }
        }

        return $frame;
    }

    //----------------------------------------------------------------------
    public static function tcpdfBarcodeArray($code, $mode = 'QR,L', $tcPdfVersion = '4.5.037')
    {
        $barcode_array = array();

        if (!is_array($mode)) {
            $mode = explode(',', $mode);
        }

        $eccLevel = 'L';

        if (count($mode) > 1) {
            $eccLevel = $mode[1];
        }

        $qrTab = QRcode::text($code, false, $eccLevel);
        $size = count($qrTab);

        $barcode_array['num_rows'] = $size;
        $barcode_array['num_cols'] = $size;
        $barcode_array['bcode'] = array();

        foreach ($qrTab as $line) {
            $arrAdd = array();
            foreach (str_split($line) as $char) {
                $arrAdd[] = ($char=='1')?1:0;
            }
            $barcode_array['bcode'][] = $arrAdd;
        }

        return $barcode_array;
    }

    //----------------------------------------------------------------------
    public static function clearCache()
    {
        self::$frames = array();
    }

    //----------------------------------------------------------------------
    public static function buildCache()
    {
        QRtools::markTime('before_build_cache');

        $mask = new QRmask();
        for ($a=1; $a <= QRSPEC_VERSION_MAX; $a++) {
            $frame = QRspec::newFrame($a);
            if (QR_IMAGE) {
                $fileName = QR_CACHE_DIR.'frame_'.$a.'.png';
                QRimage::png(self::binarize($frame), $fileName, 1, 0);
            }

            $width = count($frame);
            $bitMask = array_fill(0, $width, array_fill(0, $width, 0));
            for ($maskNo=0; $maskNo<8; $maskNo++) {
                $mask->makeMaskNo($maskNo, $width, $frame, $bitMask, true);
            }
        }

        QRtools::markTime('after_build_cache');
    }

    //----------------------------------------------------------------------
    public static function log($outfile, $err)
    {
        if (QR_LOG_DIR !== false) {
            if ($err != '') {
                if ($outfile !== false) {
                    file_put_contents(QR_LOG_DIR.basename($outfile).'-errors.txt', date('Y-m-d H:i:s').': '.$err, FILE_APPEND);
                } else {
                    file_put_contents(QR_LOG_DIR.'errors.txt', date('Y-m-d H:i:s').': '.$err, FILE_APPEND);
                }
            }
        }
    }

    //----------------------------------------------------------------------
    public static function dumpMask($frame)
    {
        $width = count($frame);
        for ($y=0; $y<$width; $y++) {
            for ($x=0; $x<$width; $x++) {
                echo ord($frame[$y][$x]).',';
            }
        }
    }

    //----------------------------------------------------------------------
    public static function markTime($markerId)
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float) $usec + (float) $sec);

        if (!isset($GLOBALS['qr_time_bench'])) {
            $GLOBALS['qr_time_bench'] = array();
        }

        $GLOBALS['qr_time_bench'][$markerId] = $time;
    }

    //----------------------------------------------------------------------
    public static function timeBenchmark()
    {
        self::markTime('finish');

        $lastTime = 0;
        $startTime = 0;
        $p = 0;

        echo '<table cellpadding="3" cellspacing="1">
                <thead><tr style="border-bottom:1px solid silver"><td colspan="2" style="text-align:center">BENCHMARK</td></tr></thead>
                <tbody>';

        foreach ($GLOBALS['qr_time_bench'] as $markerId => $thisTime) {
            if ($p > 0) {
                echo '<tr><th style="text-align:right">till '.$markerId.': </th><td>'.number_format($thisTime-$lastTime, 6).'s</td></tr>';
            } else {
                $startTime = $thisTime;
            }

            $p++;
            $lastTime = $thisTime;
        }

        echo '</tbody><tfoot>
            <tr style="border-top:2px solid black"><th style="text-align:right">TOTAL: </th><td>'.number_format($lastTime-$startTime, 6).'s</td></tr>
        </tfoot>
        </table>';
    }
}
