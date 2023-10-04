<?php
require_once('metr.php');
require_once('oarequest.php');

class phrases extends metr
{
    public $token;
    public $site;
    public $date1;
    public $date2;
    public $goalID;
    public $group;
    public $perPage;
    public $siteID;
    public $socket;
    public $resHTML;
    public $errno;
    public $errstr;

    public $debug;

    public $phrases = array(); /* data about the largest number of visitors from search engines for search phrases (Top)*/
    public $datesForMonths = array(); /* month, start date, end date */
    public $Top = array();
    public $totals;

    function __construct($token, $site, $goalID = '', $group = 'month', $perPage = 20)
    {
        $this->token = $token;
        $this->site = $site;
        $this->goalID = $goalID;
        $this->group = $group;
        $this->perPage = $perPage;
        $this->setDatesForMonths();
        $this->setSiteID();
        //$this->setTop3();
        $this->setPhrases();
        //$this->setGoals();
        //$this->setGoalsRes();
        $this->setHTML();
    }

    public function monthToRus($num)
    {
        $month = array();
        $month[1] = 'January';
        $month[2] = 'February';
        $month[3] = 'March';
        $month[4] = 'April';
        $month[5] = 'May';
        $month[6] = 'June';
        $month[7] = 'July';
        $month[8] = 'August';
        $month[9] = 'September';
        $month[10] = 'October';
        $month[11] = 'November';
        $month[12] = 'December';
        return $month[$num];

    }

    public function openConn($out, $mode = 1)
    {
        $this->socket = fsockopen("api-metrika.yandex.local", 80, $this->errno, $this->errstr, 30);
        if (!$this->socket) {
            $this->resHTML = "$this->errstr ($this->errno)<br />\n";
            return false;
        } else {
            fwrite($this->socket, $out);

            while ((fgets($this->socket, 4096) != "\r\n") && !feof($this->socket)) ;
            fgets($this->socket, 4096);
            $res = '';
            while (($get = fgets($this->socket, 4096)) && !feof($this->socket)) {
                if ($get != "\r\n") {
                    $res .= $get;
                } else {
                    break;
                }
            }
            fclose($this->socket);

            if ($mode) {
                return $res;
            } else {
                $xmlSummary = simplexml_load_string($res);
                if (isset($xmlSummary)) {
                    return $xmlSummary;
                } else {
                    return false;
                }
            }
        }

    }

    //getting site id by its name
    private function setSiteId()
    {
        /*
                $out = "GET http://api-metrika.yandex.local/counters HTTP/1.1\r\n";
                $out .= "Host: api-metrika.yandex.local\r\n";
                $out .= "Authorization: OAuth {$this->token}\r\n";
                $out .= "Accept: application/x-yametrika+xml\r\n\r\n";
                $out .= "Content-Type: application/x-yametrika+xml\r\n\r\n";
                $out .= "Connection: Close\r\n\r\n";
                $xmlResponse = $this->openConn($out);
        */
        $url = 'http://api-metrika.yandex.local/counters?oauth_token=7541404faac016ab56b3ac3f33c506f8';

        $res = file_get_contents($url);
        $xmlResponse = simplexml_load_string($res);

        foreach ($xmlResponse->counters->children() as $node) {
            if (strpos((string)$node->site, $this->site) !== false) {
                $this->siteID = $node->id;
            }
        }


    }

    public function isHasStat1($date, $goal = '')
    {
        //BEG checking if there are statistics for the 1st day of the month
        $out = "GET http://api-metrika.yandex.local/stat/sources/summary?id={$this->siteID}&goal_id={$goal}&per_page={$this->perPage}&date1={$date}&date2={$date}&group={$this->group} HTTP/1.1\r\n";
        $out .= "Host: api-metrika.yandex.local\r\n";
        $out .= "Authorization: OAuth {$this->token} \r\n";
        $out .= "Accept: application/x-yametrika+xml \r\n\r\n";
        $out .= "Content-Type: application/x-yametrika+xml \r\n\r\n";
        $out .= "Connection: Close\r\n\r\n";

        $xmlSummary = $this->openConn($out);
        if (isset($xmlSummary->data) && isset($xmlSummary->totals)) {
            return true;
        } else {
            return false;
        }
    }

    public function setDatesForMonths()
    {
        $day = (int)date('j') - 1;
        $bufDateTime = time() - ($day * 24 * 60 * 60);

        //find start and end dates of months
        /*    $this->datesForMonths[0]['month'] = '9';
            $this->datesForMonths[0]['date1'] = '20110820';
            $this->datesForMonths[0]['date2'] = '20110920';

            $this->datesForMonths[0]['month'] = '8';
            $this->datesForMonths[0]['date1'] = '20110720';
            $this->datesForMonths[0]['date2'] = '20110820';

            $this->datesForMonths[0]['month'] = '7';
            $this->datesForMonths[0]['date1'] = '20110620';
            $this->datesForMonths[0]['date2'] = '20110720'; */

        for ($i = 0; $i != 13; $i++) {
            $bufDateTime = $bufDateTime - (1 * 24 * 60 * 60);
            $this->date2 = date('Ymd', $bufDateTime);
            $bufDateTime = $bufDateTime - (((int)date('j', $bufDateTime) - 1) * 24 * 60 * 60);
            $this->date1 = date('Ymd', $bufDateTime);
            $this->datesForMonths[$i]['month'] = date('n', $bufDateTime);
            $this->datesForMonths[$i]['date1'] = $this->date1;
            $this->datesForMonths[$i]['date2'] = $this->date2;
        }
    }

    //obtaining data on clicks from search engines for the last 3 months
    public function setPhrases()
    {
        $phrases = array();
        //checking if there are statistics for the 1st day of the month
//        if ($this->isHasStat1($this->datesForMonths[0]['date1'])) {
        /*            $out = "GET http://api-metrika.yandex.local/stat/sources/phrases?id={$this->siteID}&goal_id={$this->goalID}&per_page={$this->perPage}&date1={$this->datesForMonths[0]['date1']}&date2={$this->datesForMonths[0]['date2']}&group={$this->group} HTTP/1.1\r\n";
                    $out .= "Host: api-metrika.yandex.local\r\n";
                    $out .= "Authorization: OAuth {$this->token}\r\n";
                $out .= "Accept: application/x-yametrika+xml \r\n\r\n";
                $out .= "Content-Type: application/x-yametrika+xml \r\n\r\n";
                    $out .= "Connection: Close\r\n\r\n";

                    $xmlTop = $this->openConn($out);
        */
        $oa = new oarequest();
//            $res = $oa->request(
//'http://api-metrika.yandex.local/stat/sources/phrases.xml?date1=20130301&date2=20130513&group=all&id=1641363&oauth_token=7541404faac016ab56b3ac3f33c506f8&offset=101'
//'http://api-metrika.yandex.local/stat/sources/phrases.xml'
//, $this->siteID
//, '&group=all&oauth_token=7541404faac016ab56b3ac3f33c506f8&table_mode=tree'
//                , $this->datesForMonths[0]['date1']
//                , $this->datesForMonths[0]['date2']
//, '&offset=101'

//                'http://api-metrika.yandex.local/stat/sources/phrases'
//                , $this->siteID
//                , $this->datesForMonths[0]['date1']
//                , $this->datesForMonths[0]['date2']
//                , $this->token
//                , 'tree'
//                );

// My edits after it ceased to form
        $date1 = $this->datesForMonths[0]['date1'];
        $date2 = $this->datesForMonths[0]['date2'];
        $id = $this->siteID;
        $url = 'http://api-metrika.yandex.local/stat/sources/phrases.xml?date1=' . $date1 . '&date2=' . $date2 . '&group=all&id=' . $id . '&oauth_token=7541404faac016ab56b3ac3f33c506f8';
        $res = file_get_contents($url);
// My edits after it ceased to form


        $xmlTop = simplexml_load_string($res);
        if (!$xmlTop) {
            $this->resHTML = "$this->errstr ($this->errno)<br />\n";
        } else {

            //transitions from search engines
            if (isset($xmlTop->data)) {
                $i = 0;
                foreach ($xmlTop->data->children() as $node) {
                    $i++;
                    if ($i < 20) {
                        $phrases['phrase'][] = (string)$node->phrase;
                        $phrases['visits'][] = (int)$node->visits;
                    }
                }
                $this->totals = $xmlTop->totals->visits;
            } else {
                for ($x = 0; $x != 20; $x++) {
                    $phrases['phrase'][] = 'No data3';
                    $phrases['visits'][] = 'No data3';
                }
            }
        }
//        } else {
//            for($x = 0; $x != 20; $x++) {
//                $phrases['phrase'][] = 'No data';
//                $phrases['visits'][] = 'No data';
//            }
//        }
        $this->phrases = $phrases;

    }


    public function setHTML()
    {
        $tHead = "<thead><tr><th class=\"left\">Search phrase</th><th>Visitors</th></tr></thead>";

        //table body
        $tBody = '';

        $n = count($this->phrases['phrase']);
        for ($i = 0; $i != $n; $i++) {

            $tPhras = "<td class=\"left\">" . $this->phrases['phrase'][$i] . "</td>";
            $tPhras = $tPhras . "<td>" . $this->phrases['visits'][$i] . "</td>";
            $tBody = $tBody . "<tr>" . $tPhras . "</tr>";
        }

        $tBody = "<tbody>" . $tBody . "<tr><td class=\"left\"><span class=\"tfoot\">Total:</span></td><td><span class=\"tfoot\">" . $this->totals . "</span></td></tr></tbody>";
        $this->resHTML = "<table border=\"1\">" . $tHead . $tBody . "</table>";

        /*
                $phrases[]['phrase'] = (string)$node->phrase;
                $phrases[]['visits'] = (int)$node->visits;
        */
    }


    public function getHTML()
    {
        return $this->resHTML;
    }

}
