<?php
require_once(dirname(__FILE__) . '/../lib/jpgraph/jpgraph.php');
require_once(dirname(__FILE__) . '/../lib/jpgraph/jpgraph_bar.php');
require_once('class/metr.php');
require_once('oarequest2.php');

//attendance figures
class visiting extends metr
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

    public $maxVisitors;
    public $step;
    public $divisor; //divisor to convert number of visits to pixels

    public $graph;
    public $yaxis; //Y-axis labels

    //column height
    public $bar1;
    public $bar2;
    public $bar3;
    public $bar4;

    public $debug;

    public $months = array(); /* site visit data by month directEnter-searchEngins-siteEnter*/
    public $datesForMonths = array(); /* month, start date, end date */
    public $date; //The current date

    function __construct($token, $site, $goalID = '', $group = 'month', $perPage = 1000)
    {
        $this->token = $token;
        $this->site = $site;
        //$this->date1    = $date1;
        //$this->date2    = $date2;
        $this->goalID = $goalID;
        $this->group = $group;
        $this->perPage = $perPage;
        $this->setSiteID();
        $this->setMonths();
        $this->setHTML();
        $this->setGraph();
    }

    public function monthToRus($num)
    {
        $month = array();
        $month[0] = 'December';
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

    private function openConn()
    {
        $this->socket = fsockopen("api-metrika.yandex.local", 80, $this->errno, $this->errstr, 30);
    }

    //getting site id by its name
    private function setSiteId()
    {
        $this->openConn();
        if (!$this->socket) {
            $this->resHTML = "$this->errstr ($this->errno)<br />\n";
        } else {
            $url = "http://api-metrika.yandex.local/counters";
            $oarequest = new oarequest2();
            $res = $oarequest->request($url, $this->token);
//            var_dump($res);die();
            $xmlResponse = simplexml_load_string($res);
            foreach ($xmlResponse->counters->children() as $node) {
                if (strpos((string)$node->site, $this->site) !== false) {
                    $this->siteID = $node->id;
                }
            }
            fclose($this->socket);
        }
    }

    public function isHasStat1($date)
    {
        $this->openConn();
        //BEG checking if there are statistics for the 1st day of the month
        $out = "GET http://api-metrika.yandex.local/stat/sources/summary.xml?id={$this->siteID}&goal_id={$this->goalID}&per_page={$this->perPage}&date1={$date}&date2={$date}&group={$this->group} HTTP/1.1\r\n";
        $out .= "Host: api-metrika.yandex.local\r\n";
        $out .= "Authorization: OAuth {$this->token}\r\n";
        $out .= "Accept: application/x-yametrika+xml\r\n";
        $out .= "Connection: Close\r\n\r\n";
        $this->debug[] = $out;
        fwrite($this->socket, $out);

        while ((fgets($this->socket) != "\r\n") && !feof($this->socket)) ;
        fgets($this->socket);

        $res = fgets($this->socket);
        $res .= fgets($this->socket);
        $this->debug[] = $res;
        $xmlSummary = simplexml_load_string($res);
        fclose($this->socket);
        //visits
        if (isset($xmlSummary->data) && isset($xmlSummary->totals)) {
            return true;
        } else {
            return false;
        }
    }

    //getting data for the last 12 months
    public function setMonths()
    {

        $day = (int)date('j') - 1;
        $bufDateTime = time() - ($day * 25 * 60 * 60); // TODO: doesn't work after 23:00 :D

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

        for ($i = 0; $i != 13; $i++) {
            $bufDateTime = $bufDateTime - (1 * 24 * 60 * 60);
            $this->date2 = date('Ymd', $bufDateTime);
            $bufDateTime = $bufDateTime - (((int)date('j', $bufDateTime) - 1) * 24 * 60 * 60);
            $this->date1 = date('Ymd', $bufDateTime);
            $this->datesForMonths[$i]['month'] = date('n', $bufDateTime);
            $this->datesForMonths[$i]['date1'] = $this->date1;
            $this->datesForMonths[$i]['date2'] = $this->date2;
        }


        //get attendance data by month
        for ($i = 0; $i != 13; $i++) {
            //BEG checking if there are statistics for the 1st day of the month
//            if ($this->isHasStat1($this->datesForMonths[$i]['date1'])) {
            $this->openConn();
            if (!$this->socket) {
                $this->resHTML = "$this->errstr ($this->errno)<br />\n";
            } else {
                //$out = "GET http://api-metrika.yandex.local/stat/sources/summary?id={$this->siteID}&goal_id={$this->goalID}&per_page={$this->perPage}&date1={$this->datesForMonths[$i]['date1']}&date2={$this->datesForMonths[$i]['date2']}&group={$this->group} HTTP/1.1\r\n";
                //$out .= "Host: api-metrika.yandex.local\r\n";
                //$out .= "Authorization: OAuth {$this->token}\r\n";
                //$out .= "Accept: application/x-yametrika+xml\r\n";
                //$out .= "Connection: Close\r\n\r\n";
                //$this->debug[] = $out;
                //fwrite($this->socket, $out);
                //
                //while((fgets($this->socket) != "\r\n") && !feof($this->socket));
                //fgets($this->socket);
                //
                //$res = fgets($this->socket);
                //$res .= fgets($this->socket);

// My edits after it ceased to form
                $id = $this->siteID;
                $date1 = $this->datesForMonths[$i]['date1'];
                $date2 = $this->datesForMonths[$i]['date2'];
                $goal_id = $this->goalID;
                $perPage = $this->perPage;
                $group = $this->group;
                $url = 'http://api-metrika.yandex.local/stat/sources/summary.xml?id=' . $id . '&goal_id=' . $goal_id . '&per_page=' . $perPage . '&date1=' . $date1 . '&date2=' . $date2 . '&group=all&oauth_token=7541404faac016ab56b3ac3f33c506f8';
                $res = file_get_contents($url);
// My edits after it ceased to form


                $this->debug[] = $res;
                $xmlSummary = simplexml_load_string($res);

                //visits
                if (isset($xmlSummary->data)) {
                    foreach ($xmlSummary->data->children() as $node) {
                        switch ((string)$node->name) {
                            case "Referrals from search engines" :
                                $this->months[$i]['searchEngins'] = (int)$node->visits;
                                break;
                            case "Direct entries" :
                                $this->months[$i]['directEnter'] = (int)$node->visits;
                                break;
                            case "Website link clicks" :
                                $this->months[$i]['siteEnter'] = (int)$node->visits;
                                break;
                            case "Ad clicks" :
                                $this->months[$i]['adEnter'] = (int)$node->visits;
                                break;
                            default :
                                break;

                        }
                    }
                } else {
                    $this->months[$i]['searchEngins'] = 'No data';
                    $this->months[$i]['directEnter'] = 'No data';
                    $this->months[$i]['siteEnter'] = 'No data';
                    $this->months[$i]['adEnter'] = 'No data';
                }
                //total visitors (totals)
                if (!isset($xmlSummary->totals->visits)) {
                    $this->months[$i]['totals'] = 'No data';
                } else {
                    $this->months[$i]['totals'] = (int)$xmlSummary->totals->visits;
                }


            }
            fclose($this->socket);
            /*            } else {

                            $this->months[$i]['searchEngins'] = 'No data1';
                            $this->months[$i]['directEnter'] = 'No data2';
                            $this->months[$i]['siteEnter'] = 'No data3';
                            $this->months[$i]['adEnter'] = 'No data4';
                        }
            */
            if (!isset($this->months[$i]['searchEngins'])) {
                $this->months[$i]['searchEngins'] = 'No data1';
            }
            if (!isset($this->months[$i]['directEnter'])) {
                $this->months[$i]['directEnter'] = 'No data1';
            }
            if (!isset($this->months[$i]['siteEnter'])) {
                $this->months[$i]['siteEnter'] = 'No data1';
            }
            if (!isset($this->months[$i]['adEnter'])) {
                $this->months[$i]['adEnter'] = 'No data1';
            }


        }

    }

    public function setHTML()
    {
        $this->resHTML = "<table border=\"1\"><tr><th class=\"left\">Index</th><th>" . $this->monthToRus($this->datesForMonths[2]['month']) . "</th><th>" . $this->monthToRus($this->datesForMonths[1]['month']) . "</th><th>" . $this->monthToRus($this->datesForMonths[0]['month']) . "</th><th>Dynamics</th></tr>";
        $this->resHTML .= "<tr><td class=\"left\">Visitors</td>";

        $this->resHTML .= "<td>" . $this->months[2]['totals'] . "</td><td>" . $this->months[1]['totals'] . "</td><td>" . $this->months[0]['totals'] . "</td>";
        $this->resHTML .= "<td>";

        //dynamics of change
        if (is_numeric($this->months[1]['totals']) && is_numeric($this->months[0]['totals'])) {
            $res = $this->months[0]['totals'] - $this->months[1]['totals'];
            $din = '';
            if ($res == 0) {
                $din = "<span class=\"null\">" . $res . "</span>";
            } else if ($res > 0) {
                $din = "<span class=\"plus\">+" . $res . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $res . "</span>";
            }
            $this->resHTML .= $din;
        } else {
            $this->resHTML .= "No data";
        }

        $this->resHTML .= "</td></tr>";

        //BEG direct entries
        $this->resHTML .= "<tr><td class=\"left\">Direct entries</td><td>";
        if (is_numeric($this->months[2]['directEnter'])) {
            $directEnterPercent = round($this->months[2]['directEnter'] * 100 / $this->months[2]['totals'], 2);
            $this->resHTML .= $this->months[2]['directEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[2]['directEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[1]['directEnter'])) {
            $directEnterPercent = round($this->months[1]['directEnter'] * 100 / $this->months[1]['totals'], 2);
            $this->resHTML .= $this->months[1]['directEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[1]['directEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[0]['directEnter'])) {
            $directEnterPercent = round($this->months[0]['directEnter'] * 100 / $this->months[0]['totals'], 2);
            $this->resHTML .= $this->months[0]['directEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[0]['directEnter'];
        }
        $this->resHTML .= "</td><td>";

        //dynamics of change
        if (is_numeric($this->months[1]['directEnter']) && is_numeric($this->months[0]['directEnter'])) {
            $res = $this->months[0]['directEnter'] - $this->months[1]['directEnter'];
            $din = '';
            if ($res == 0) {
                $din = "<span class=\"null\">" . $res . "</span>";
            } else if ($res > 0) {
                $din = "<span class=\"plus\">+" . $res . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $res . "</span>";
            }
            $this->resHTML .= $din;
        } else {
            $this->resHTML .= "No data";
        }

        $this->resHTML .= "</td></tr>";
        //END direct entries

        //BEG search engines
        $this->resHTML .= "<tr><td class=\"left\">Search engines</td><td>";
        if (is_numeric($this->months[2]['searchEngins'])) {
            $directEnterPercent = round($this->months[2]['searchEngins'] * 100 / $this->months[2]['totals'], 2);
            $this->resHTML .= $this->months[2]['searchEngins'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[2]['searchEngins'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[1]['searchEngins'])) {
            $directEnterPercent = round($this->months[1]['searchEngins'] * 100 / $this->months[1]['totals'], 2);
            $this->resHTML .= $this->months[1]['searchEngins'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[1]['searchEngins'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[0]['searchEngins'])) {
            $directEnterPercent = round($this->months[0]['searchEngins'] * 100 / $this->months[0]['totals'], 2);
            $this->resHTML .= $this->months[0]['searchEngins'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[0]['searchEngins'];
        }
        $this->resHTML .= "</td><td>";

        //dynamics of change
        if (is_numeric($this->months[1]['searchEngins']) && is_numeric($this->months[0]['searchEngins'])) {
            $res = $this->months[0]['searchEngins'] - $this->months[1]['searchEngins'];
            $din = '';
            if ($res == 0) {
                $din = "<span class=\"null\">" . $res . "</span>";
            } else if ($res > 0) {
                $din = "<span class=\"plus\">+" . $res . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $res . "</span>";
            }
            $this->resHTML .= $din;
        } else {
            $this->resHTML .= "No data";
        }

        $this->resHTML .= "</td></tr>";
        //END search engines

        //BEG sites
        $this->resHTML .= "<tr><td class=\"left\">Referring Sites</td><td>";
        if (is_numeric($this->months[2]['siteEnter'])) {
            $directEnterPercent = round($this->months[2]['siteEnter'] * 100 / $this->months[2]['totals'], 2);
            $this->resHTML .= $this->months[2]['siteEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[2]['siteEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[1]['siteEnter'])) {
            $directEnterPercent = round($this->months[1]['siteEnter'] * 100 / $this->months[1]['totals'], 2);
            $this->resHTML .= $this->months[1]['siteEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[1]['siteEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[0]['siteEnter'])) {
            $directEnterPercent = round($this->months[0]['siteEnter'] * 100 / $this->months[0]['totals'], 2);
            $this->resHTML .= $this->months[0]['siteEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[0]['siteEnter'];
        }
        $this->resHTML .= "</td><td>";

        //dynamics of change
        if (is_numeric($this->months[1]['siteEnter']) && is_numeric($this->months[0]['siteEnter'])) {
            $res = $this->months[0]['siteEnter'] - $this->months[1]['siteEnter'];
            $din = '';
            if ($res == 0) {
                $din = "<span class=\"null\">" . $res . "</span>";
            } else if ($res > 0) {
                $din = "<span class=\"plus\">+" . $res . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $res . "</span>";
            }
            $this->resHTML .= $din;
        } else {
            $this->resHTML .= "No data";
        }

        $this->resHTML .= "</td></tr>";

        //BEG on advertising
        $this->resHTML .= "<tr><td class=\"left\">Ad clicks</td><td>";
        if (is_numeric($this->months[2]['adEnter'])) {
            $directEnterPercent = round($this->months[2]['adEnter'] * 100 / $this->months[2]['totals'], 2);
            $this->resHTML .= $this->months[2]['adEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[2]['adEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[1]['adEnter'])) {
            $directEnterPercent = round($this->months[1]['adEnter'] * 100 / $this->months[1]['totals'], 2);
            $this->resHTML .= $this->months[1]['adEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[1]['adEnter'];
        }
        $this->resHTML .= "</td><td>";

        if (is_numeric($this->months[0]['adEnter'])) {
            $directEnterPercent = round($this->months[0]['adEnter'] * 100 / $this->months[0]['totals'], 2);
            $this->resHTML .= $this->months[0]['adEnter'] . " (" . $directEnterPercent . "%)";
        } else {
            $this->resHTML .= $this->months[0]['adEnter'];
        }
        $this->resHTML .= "</td><td>";

        //dynamics of change
        if (is_numeric($this->months[1]['adEnter']) && is_numeric($this->months[0]['adEnter'])) {
            $res = $this->months[0]['adEnter'] - $this->months[1]['adEnter'];
            $din = '';
            if ($res == 0) {
                $din = "<span class=\"null\">" . $res . "</span>";
            } else if ($res > 0) {
                $din = "<span class=\"plus\">+" . $res . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $res . "</span>";
            }
            $this->resHTML .= $din;
        } else {
            $this->resHTML .= "No data";
        }
        //END on advertising

        $this->resHTML .= "</td></tr>";
        //END sites

        $this->resHTML .= "</table>";

    }

    public function getHTML()
    {
        return $this->resHTML;
    }


    public function genGraph()
    {
        if (file_exists($this->graph)) {
            unlink($this->graph);
        }

        //bar3
        $data3y = array_reverse($this->bar1['count']);
        $data4y = array_reverse($this->bar2['count']);
        $data5y = array_reverse($this->bar3['count']);
        $data6y = array_reverse($this->bar4['count']);

        // Create the graph. These two calls are always required
        $graph = new Graph(590, 290, 'auto');
        $graph->SetScale("textlinintlin");

        $graph->SetY2Scale("lin", 0, 90);
        $graph->SetY2OrderBack(false);

        $graph->SetMargin(50, 50, 30, 20);

        $theme_class = new UniversalTheme;
        $graph->SetTheme($theme_class);

        //$graph->yaxis->SetTickPositions(array(0,50,100,150,200,250,300,350), array(25,75,125,175,275,325));
        //$graph->y2axis->SetTickPositions(array(30,40,50,60,70,80,90));

        $months = array_reverse($this->bar1['month']);
        //$graph->SetBox(false);

        $graph->ygrid->SetFill(false);
        $graph->yaxis->HideLine(false);
        $graph->yaxis->HideTicks(false, false);
        //$graph->xaxis->SetTickPositions(array(0,50,100,150,200,250,300,350), array(25,75,125,175,275,325));
        $graph->xaxis->SetTickLabels($months);
        $graph->xaxis->SetTextTickInterval(2); //x label spacing

        $b3plot = new BarPlot($data3y);
        $b4plot = new BarPlot($data4y);
        $b5plot = new BarPlot($data5y);
        $b6plot = new BarPlot($data6y);

        // Create the grouped bar plot
        $gbbplot = new AccBarPlot(array($b3plot, $b4plot, $b5plot, $b6plot));

        // ...and add it to the graPH
        $graph->Add($gbbplot);

        $b6plot->SetColor("#F5B741");
        $b6plot->SetFillColor("#F5B741");
        $b6plot->SetLegend("Ad clicks");

        $b5plot->SetColor("#9BBB59");
        $b5plot->SetFillColor("#9BBB59");
        $b5plot->SetLegend("Referring Sites");

        $b4plot->SetColor("#c0504d");
        $b4plot->SetFillColor("#c0504d");
        $b4plot->SetLegend("Search engines");

        $b3plot->SetColor("#4f81bd");
        $b3plot->SetFillColor("#4f81bd");
        $b3plot->SetLegend("Direct entries");

        $graph->legend->SetFrameWeight(1);
        $graph->legend->SetColumns(6);
        $graph->legend->SetColor('#4E4E4E', '#FFFFFF');

        $graph->title->Hide();

        //signatures "No data"
        $xc = 68;
        $yc = 140;
        for ($i = 0; $i != 13; $i++) {
            if ((!is_numeric($data3y[$i]) || ($data3y[$i] == 0))
                && (!is_numeric($data4y[$i]) || ($data4y[$i] == 0))
                && (!is_numeric($data5y[$i]) || ($data5y[$i] == 0))
                && (!is_numeric($data6y[$i]) || ($data6y[$i] == 0))) {
                $txt = new Text("No data", $xc, $yc);
                $txt->Align('right', 'top');
                $txt->SetFont(FF_ARIAL, FS_NORMAL, 8);
                $txt->SetColor('gray');
                $txt->SetAngle(90);
                $txt->Set("No data");
                $txt->SetParagraphAlign('left');
                $graph->Add($txt);
            }
            $xc += 39;
        }
        $this->graph = 'mygraph.png';
        // Display the graph
        if (file_exists($this->graph)) {
            unlink($this->graph);
        }
        $graph->Stroke($this->graph);

    }

    public function setGraph()
    {
        //directEnter-searchEngins-searchEngins

        //bar1Pixels -- directEnter
        for ($i = 0; $i != 13; $i++) {
            $this->bar1['month'][$i] = $this->monthToRus($this->datesForMonths[$i]['month']);
            if (is_numeric($this->months[$i]['directEnter'])) {
                $this->bar1['count'][$i] = $this->months[$i]['directEnter'];
            } else {
                $this->bar1['count'][$i] = 0;
            }
        }

        //bar2Pixels -- searchEngins
        for ($i = 0; $i != 13; $i++) {
            $this->bar2['month'][$i] = $this->monthToRus($this->datesForMonths[$i]['month']);
            if (is_numeric($this->months[$i]['searchEngins'])) {
                $this->bar2['count'][$i] = $this->months[$i]['searchEngins'];
            } else {
                $this->bar2['count'][$i] = 0;
            }
        }

        //bar3Pixels -- siteEnter
        for ($i = 0; $i != 13; $i++) {
            $this->bar3['month'][$i] = $this->monthToRus($this->datesForMonths[$i]['month']);
            if (is_numeric($this->months[$i]['siteEnter'])) {
                $this->bar3['count'][$i] = $this->months[$i]['siteEnter'];
            } else {
                $this->bar3['count'][$i] = 0;
            }
        }
        //bar4Pixels -- adEnter
        for ($i = 0; $i != 13; $i++) {
            $this->bar4['month'][$i] = $this->monthToRus($this->datesForMonths[$i]['month']);
            if (is_numeric($this->months[$i]['adEnter'])) {
                $this->bar4['count'][$i] = $this->months[$i]['adEnter'];
            } else {
                $this->bar4['count'][$i] = 0;
            }
        }
        $this->genGraph();
    }

    public function getGraph()
    {
        return $this->graph;
    }
}
