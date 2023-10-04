<?php

ini_set("session.use_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.auto_start", 0);
session_start();
if (!isset($_SESSION['rev_seo'])) {
    $logout = 'hidden';
    header('Location: login.php');
} else {

    if (!empty($_POST['reportText'])) {
        $html = stripslashes($_POST['reportText']);
        require_once(dirname(__FILE__) . '/lib/mpdf/mpdf.php');
        $mpdf = new mPDF('utf-8', 'A4', 0, '', 15, 15, 20, 16, 4, 4, 'P');
        $stylesheet = file_get_contents("css/report-pdf.css");
        $mpdf->SetHTMLHeader('<div style="text-align: left; font: 8pt Sans-Serif; color: #8C8C8C; width: 50%; float: left; padding: 0;">Web-studio "Avanta"</div><div style="text-align: right; font: 8pt Sans-Serif; color: #8C8C8C; width: 50%; float: right; padding: 0; line-height: 1.2em;">Ekaterinburg, Malisheva street 19, office 913<br />Phone: (343) 201-73-11<br />e-mail: info@avanta.ru<br />avanta.ru</div>');

        $mpdf->WriteHTML($stylesheet, 1);
        $mpdf->WriteHTML($html, 2);
        $mpdf->Output($_POST['sitename'], 'I');
        exit;

        /*    require_once(dirname(__FILE__).'/lib/html2pdf/html2pdf.class.php');
            try
            {
                $html2pdf = new HTML2PDF('P', 'A4', 'en');
                $html2pdf->writeHTML($html);
                $html2pdf->Output('gf.pdf');
                exit;
            }
            catch(HTML2PDF_exception $e) {
                echo $e;
                exit;
            }

        */
    } else {
        header('Location: /report');
    }
}
