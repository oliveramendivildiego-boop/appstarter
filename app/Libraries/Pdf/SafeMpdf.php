<?php

namespace App\Libraries\Pdf;

/**
 * mPDF 8.x: en algunos saltos de página (float / keep_block_together) incrementa $page
 * sin crear $pages[$page] → ErrorException en BaseWriter.php:245.
 */
final class SafeMpdf extends \Mpdf\Mpdf
{
    /**
     * @param mixed $html
     * @param mixed $mode
     * @param mixed $init
     * @param mixed $close
     */
    public function WriteHTML($html, $mode = \Mpdf\HTMLParserMode::DEFAULT_MODE, $init = true, $close = true)
    {
        $this->ensurePagesInitialized();

        return parent::WriteHTML($html, $mode, $init, $close);
    }

    public function Write($h, $txt, $currentx = 0, $link = '', $directionality = 'ltr', $align = '', $fill = 0)
    {
        $this->ensurePagesInitialized();

        return parent::Write($h, $txt, $currentx, $link, $directionality, $align, $fill);
    }

    public function AddPage(
        $orientation = '',
        $condition = '',
        $resetpagenum = '',
        $pagenumstyle = '',
        $suppress = '',
        $mgl = '',
        $mgr = '',
        $mgt = '',
        $mgb = '',
        $mgh = '',
        $mgf = '',
        $ohname = '',
        $ehname = '',
        $ofname = '',
        $efname = '',
        $ohvalue = 0,
        $ehvalue = 0,
        $ofvalue = 0,
        $efvalue = 0,
        $pagesel = '',
        $newformat = '',
    ) {
        $ret = parent::AddPage(
            $orientation,
            $condition,
            $resetpagenum,
            $pagenumstyle,
            $suppress,
            $mgl,
            $mgr,
            $mgt,
            $mgb,
            $mgh,
            $mgf,
            $ohname,
            $ehname,
            $ofname,
            $efname,
            $ohvalue,
            $ehvalue,
            $ofvalue,
            $efvalue,
            $pagesel,
            $newformat,
        );

        $this->ensurePagesInitialized();

        return $ret;
    }

    public function SetHTMLFooter($footer = '', $OE = '')
    {
        $ret = parent::SetHTMLFooter($footer, $OE);
        $this->ensurePagesInitialized();

        return $ret;
    }

    public function DefHTMLFooterByName($name, $html)
    {
        parent::DefHTMLFooterByName($name, $html);
        $this->ensurePagesInitialized();
    }

    private function ensurePagesInitialized(): void
    {
        $current = max(0, (int) $this->page);
        if ($current < 1) {
            return;
        }

        for ($p = 1; $p <= $current; $p++) {
            if (! array_key_exists($p, $this->pages)) {
                $this->pages[$p] = '';
            }
        }
    }
}
