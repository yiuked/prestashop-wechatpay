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

class QRrawcode
{
    public $version;
    public $datacode = array();
    public $ecccode = array();
    public $blocks;
    public $rsblocks = array(); //of RSblock
    public $count;
    public $dataLength;
    public $eccLength;
    public $b1;

    //----------------------------------------------------------------------
    public function __construct(QRinput $input)
    {
        $spec = array(0,0,0,0,0);

        $this->datacode = $input->getByteStream();
        if (is_null($this->datacode)) {
            throw new Exception('null imput string');
        }

        QRspec::getEccSpec($input->getVersion(), $input->getErrorCorrectionLevel(), $spec);

        $this->version = $input->getVersion();
        $this->b1 = QRspec::rsBlockNum1($spec);
        $this->dataLength = QRspec::rsDataLength($spec);
        $this->eccLength = QRspec::rsEccLength($spec);
        $this->ecccode = array_fill(0, $this->eccLength, 0);
        $this->blocks = QRspec::rsBlockNum($spec);

        $ret = $this->init($spec);
        if ($ret < 0) {
            throw new Exception('block alloc error');

            return null;
        }

        $this->count = 0;
    }

    //----------------------------------------------------------------------
    public function init(array $spec)
    {
        $dl = QRspec::rsDataCodes1($spec);
        $el = QRspec::rsEccCodes1($spec);
        $rs = QRrs::initRs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);

        $blockNo = 0;
        $dataPos = 0;
        $eccPos = 0;
        for ($i=0; $i<QRspec::rsBlockNum1($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);
            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el, $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode, 0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        if (QRspec::rsBlockNum2($spec) == 0) {
            return 0;
        }

        $dl = QRspec::rsDataCodes2($spec);
        $el = QRspec::rsEccCodes2($spec);
        $rs = QRrs::initRs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);

        if ($rs == null) {
            return -1;
        }

        for ($i=0; $i<QRspec::rsBlockNum2($spec); $i++) {
            $ecc = array_slice($this->ecccode, $eccPos);
            $this->rsblocks[$blockNo] = new QRrsblock($dl, array_slice($this->datacode, $dataPos), $el, $ecc, $rs);
            $this->ecccode = array_merge(array_slice($this->ecccode, 0, $eccPos), $ecc);

            $dataPos += $dl;
            $eccPos += $el;
            $blockNo++;
        }

        return 0;
    }

    //----------------------------------------------------------------------
    public function getCode()
    {
        $ret = '';

        if ($this->count < $this->dataLength) {
            $row = $this->count % $this->blocks;
            $col = $this->count / $this->blocks;
            if ($col >= $this->rsblocks[0]->dataLength) {
                $row += $this->b1;
            }
            $ret = $this->rsblocks[$row]->data[$col];
        } elseif ($this->count < $this->dataLength + $this->eccLength) {
            $row = ($this->count - $this->dataLength) % $this->blocks;
            $col = ($this->count - $this->dataLength) / $this->blocks;
            $ret = $this->rsblocks[$row]->ecc[$col];
        } else {
            return 0;
        }
        $this->count++;

        return $ret;
    }
}
