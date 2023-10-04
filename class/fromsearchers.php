<?php
require_once('class/metr.php');
require_once('oarequest.php');

class fromsearchers extends metr
{
    public $token;
    public $site;
    public $date1;
    public $date2;
    public $goalID;
    public $group;
    public $perPage;
    public $_top;

    public $siteID;
    public $socket;
    public $resHTML;
    public $errno;
    public $errstr;

    public $debug;
    public $deb;

    public $months = array(); /* site visit data by month*/
    public $datesForMonths = array(); /* month, start date, end date */
    public $Top = array();


    function __construct($token, $site, $goalID = '', $group = 'month', $perPage = 1000)
    {
        $this->token = $token;
        $this->site = $site;
        $this->goalID = $goalID;
        $this->group = $group;
        $this->perPage = $perPage;
        $this->setDatesForMonths();
        $this->setSiteID();
        //$this->setTop();
        $this->setMonths();
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

            while ((fgets($this->socket) != "\r\n") && !feof($this->socket)) ;
            fgets($this->socket);

            $res = fgets($this->socket);
            $res .= fgets($this->socket);

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
        $out .= "Authorization: OAuth {$this->token}\r\n";
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
        //find start and end dates of months
        /*    $this->datesForMonths[0]['month'] = '9';
            $this->datesForMonths[0]['date1'] = '20110820';
            $this->datesForMonths[0]['date2'] = '20110920';

            $this->datesForMonths[1]['month'] = '8';
            $this->datesForMonths[1]['date1'] = '20110720';
            $this->datesForMonths[1]['date2'] = '20110820';

            $this->datesForMonths[2]['month'] = '7';
            $this->datesForMonths[2]['date1'] = '20110620';
            $this->datesForMonths[2]['date2'] = '20110720'; */

        for ($i = 0; $i != 5; $i++) {
            $bufDateTime = $bufDateTime - (1 * 24 * 60 * 60);
            $this->date2 = date('Ymd', $bufDateTime);
            $bufDateTime = $bufDateTime - (((int)date('j', $bufDateTime) - 1) * 24 * 60 * 60);
            $this->date1 = date('Ymd', $bufDateTime);
            $this->datesForMonths[$i]['month'] = date('n', $bufDateTime);
            $this->datesForMonths[$i]['date1'] = $this->date1;
            $this->datesForMonths[$i]['date2'] = $this->date2;
        }
    }

    //top 5 search engines for the previous month
    public function setSearchers(&$xmlTop)
    {
        if (!$xmlTop) {
            $this->resHTML = "$this->errstr ($this->errno)<br />\n";
            return false;
        } else {

            //transitions from search engines
            if (isset($xmlTop->data)) {
                $data = array();
                foreach ($xmlTop->data->children() as $node) {
                    $data['name'][] = (string)$node->name;
                    $data['visits'][] = 'No data';
                    $data['id'][] = 'No data';
                }
                $data['name'] = array_slice($data['name'], 0, 5);
                $data['visits'] = array_slice($data['visits'], 0, 5);
                $data['id'] = array_slice($data['id'], 0, 5);
            } else {
                for ($x = 0; $x != 5; $x++) {
                    $data['name'][$x] = 'No data';
                    $data['visits'][$x] = 'No data';
                    $data['id'][$x] = 'No data';
                }
            }
            return $data;
        }
    }

    //obtaining data on clicks from search engines for the last 3 months
    public function setMonths()
    {
        $f = false;
        //we receive data on transitions from search engines by months
        for ($i = 0; $i != 3; $i++) {
            //checking if there are statistics for the 1st day of the month
            /*            if ($this->isHasStat1($this->datesForMonths[$i]['date1'])) {
                            $out = "GET http://api-metrika.yandex.local/stat/sources/search_engines?id={$this->siteID}&goal_id={$this->goalID}&per_page={$this->perPage}&date1={$this->datesForMonths[$i]['date1']}&date2={$this->datesForMonths[$i]['date2']}&group={$this->group}&table_mode=tree HTTP/1.1\r\n";
                            $out .= "Host: api-metrika.yandex.local\r\n";
                            $out .= "Authorization: OAuth {$this->token}\r\n";
                            $out .= "Connection: Close\r\n\r\n";
            */
            $oa = new oarequest();
            /*                $res = $oa->request(
                                'http://api-metrika.yandex.local/stat/sources/search_engines.xml'
                                , $this->siteID
                                , $this->datesForMonths[$i]['date1']
                                , $this->datesForMonths[$i]['date2']
                                , $this->token
                                , 'tree'
                                );
            */

// My edits after it ceased to form
            /*
            $url_m = 'http://api-metrika.yandex.local/counters?oauth_token=7541404faac016ab56b3ac3f33c506f8';

            $res_m = file_get_contents($url_m);
            $xmlTop_m = simplexml_load_string($res_m);

                                if(isset($xmlTop_m->counters)) {
                                    $data_m = array();
                                    $i = 0;
                                    foreach($xmlTop_m->counters->children() as $node) {
                                        $data_m['site'][$i] = (string)$node->site;
                                        $data_m['id'][$i] = (string)$node->id;

                                        if($data_m['site'][$i] == $this->site){
                                        //echo $data_['site'][$i];
                                        //echo $data_['id'][$i];
                                        $metrikaID = $data_m['id'][$i];
                                        $i++;
                                        }
                                    }
            //                        echo $metrikaID;
                                }
            */
            $date1 = $this->datesForMonths[$i]['date1'];
            $date2 = $this->datesForMonths[$i]['date2'];
            $id = $this->siteID;
//$id = $metrikaID;
            $url = 'http://api-metrika.yandex.local/stat/sources/search_engines.xml?date1=' . $date1 . '&date2=' . $date2 . '&group=all&id=' . $id . '&oauth_token=7541404faac016ab56b3ac3f33c506f8';
//echo $url;
//echo $this->site;
//$url = 'http://api-metrika.yandex.local/stat/sources/search_engines.xml?date1=20130901&date2=20130930&group=all&id=1641363&oauth_token=7541404faac016ab56b3ac3f33c506f8';

            $res = file_get_contents($url);
// My edits after it ceased to form


            $this->deb = $res;
            //echo $this->deb;
            $xmlTop = simplexml_load_string($res);
            if (!$f) {
                //transitions from search engines
                if (isset($xmlTop->data)) {
                    $data_ = array();
                    foreach ($xmlTop->data->children() as $node) {
                        $data_['name'][] = (string)$node->name;
                        $data_['visits'][] = 'No data';
                        $data_['id'][] = 'No data';
                    }
                    $data_['name'] = array_slice($data_['name'], 0, 5);
                    $data_['visits'] = array_slice($data_['visits'], 0, 5);
                    $data_['id'] = array_slice($data_['id'], 0, 5);
                } else {
                    for ($x = 0; $x != 5; $x++) {
                        $data_['name'][$x] = 'No data1';
                        $data_['visits'][$x] = 'No data1';
                        $data_['id'][$x] = 'No data1';
                    }
                }

                $f = true;
            }
            if (!$xmlTop) {
                $this->resHTML = "$this->errstr ($this->errno)<br />\n";
            } else {
                //transitions from search engines
                if (isset($xmlTop->data)) {
                    $data = array();
                    foreach ($xmlTop->data->children() as $node) {
                        $data['name'][] = (string)$node->name;
                        $data['visits'][] = (int)$node->visits;
                        $data['id'][] = (int)$node->id;
                    }
                } else {
                    for ($x = 0; $x != 5; $x++) {
                        $data['name'][] = 'No data';
                        $data['visits'][] = 'No data';
                        $data['id'][] = 'No data';
                    }
                }
            }
            /*            } else {
                            for($x = 0; $x != 5; $x++) {
                                $data['name'][] = 'No data';
                                $data['visits'][] = 'No data';
                                $data['id'][] = 'No data';

                                $data_['name'][] = 'No data';
                                $data_['visits'][] = 'No data';
                                $data_['id'][] = 'No data';

                            }
                        }
            */
            $_data_ = $data_;
            foreach ($_data_['name'] as $key => $name) {
                foreach ($data['name'] as $skey => $sname) {
                    if ($name == $sname) {
                        $_data_['visits'][$key] = $data['visits'][$skey];
                        $_data_['id'][$key] = $data['id'][$skey];
                    }
                }
            }
            $this->months[$i]['month'] = $this->monthToRus($this->datesForMonths[$i]['month']);
            $this->months[$i]['data'] = $_data_;
        }

    }


    public function setHTML()
    {
        $tHead = '';
        //collect the hat
        foreach ($this->months as $month) {
            $tHead = "<th>" . $month['month'] . "</th>" . $tHead;
        }
        $tHead = "<thead><tr><th class=\"left\">Source</th>" . $tHead . "</tr></thead>";


        $bySource = array();

        //table body
        $tBody = '';
        $n = count($this->months[0]['data']['name']);

        for ($i = 0; $i != $n; $i++) {
            $tS = "<tr><td class=\"left\">" . $this->months[0]['data']['name'][$i] . "</td>";
            $tMonth = '';
            for ($j = 0; $j != 3; $j++) {
                $tMonth = "<td>" . $this->months[$j]['data']['visits'][$i] . "</td>" . $tMonth;
            }

            $tBody = $tBody . $tS . $tMonth . "</tr>";
        }

        $tBody = "<tbody>" . $tBody . "</tbody>";
        $this->resHTML = "<table border=\"1\">" . $tHead . $tBody . "</table>";

        /*
                $data['name'][] = (string)$node->name;
                $data['visits'][] = (int)$node->visits;
                $data['id'][] = (int)$node->id;

                $this->months[$i]['month'] = $this->monthToRus($this->datesForMonths[$i]['month']);
                $this->months[$i]['data'] = $data;
        */
    }


    public function getHTML()
    {
        return $this->resHTML;
    }

}
