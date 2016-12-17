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

class FrameFiller
{
    public $width;
    public $frame;
    public $x;
    public $y;
    public $dir;
    public $bit;

    //----------------------------------------------------------------------
    public function __construct($width, &$frame)
    {
        $this->width = $width;
        $this->frame = $frame;
        $this->x = $width - 1;
        $this->y = $width - 1;
        $this->dir = -1;
        $this->bit = -1;
    }

    //----------------------------------------------------------------------
    public function setFrameAt($at, $val)
    {
        $this->frame[$at['y']][$at['x']] = chr($val);
    }

    //----------------------------------------------------------------------
    public function getFrameAt($at)
    {
        return ord($this->frame[$at['y']][$at['x']]);
    }

    //----------------------------------------------------------------------
    public function next()
    {
        do {
            if ($this->bit == -1) {
                $this->bit = 0;

                return array('x' => $this->x, 'y' => $this->y);
            }

            $x = $this->x;
            $y = $this->y;
            $w = $this->width;

            if ($this->bit == 0) {
                $x--;
                $this->bit++;
            } else {
                $x++;
                $y += $this->dir;
                $this->bit--;
            }

            if ($this->dir < 0) {
                if ($y < 0) {
                    $y = 0;
                    $x -= 2;
                    $this->dir = 1;
                    if ($x == 6) {
                        $x--;
                        $y = 9;
                    }
                }
            } else {
                if ($y == $w) {
                    $y = $w - 1;
                    $x -= 2;
                    $this->dir = -1;
                    if ($x == 6) {
                        $x--;
                        $y -= 8;
                    }
                }
            }
            if ($x < 0 || $y < 0) {
                return null;
            }

            $this->x = $x;
            $this->y = $y;
        } while (ord($this->frame[$y][$x]) & 0x80);

        return array('x' => $x, 'y' => $y);
    }
}
