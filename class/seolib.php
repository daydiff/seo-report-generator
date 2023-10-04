<?php
//error_reporting(error_reporting() & ~E_DEPRECATED);
include('class/xmlrpc.inc');

//include ('xmlrpc.inc');

class seolib
{
    public $projName;
    public $login;
    public $pass;
    public $projID;
    public $region;
    public $regionID;
    public $date;
    public $dates;
    public $period;
    public $resQuery;
    public $resHTML;
    public $res;
    public $fday;
    public $qpositions;
    public $top5count;
    public $top10count;
    public $count = array();
    protected $server;
    public $mDates;

    public $debug;

    public function __construct($projectName, $login, $pass, $dates)
    {
        $this->projName = $projectName;
        $this->login = $login;
        $this->pass = $pass;
        $this->date = date('d.m.Y', time());
        $this->period = 30;
        $this->mDates = $dates;

        $this->server = new xmlrpc_client('/script/xmlrpc/server.php', 'www.seolib.local', 80);
        $this->server->setAcceptedCompression('');
        $this->server->setRequestCompression('');
        //$this->server->setDebug(1);
        $this->setRes();
        $this->setTop();
    }

    public function month2Word($m)
    {
        switch ($m) {
            case  0:
                return "December";
            case  1:
                return "January";
            case  2:
                return "February";
            case  3:
                return "March";
            case  4:
                return "April";
            case  5:
                return "May";
            case  6:
                return "June";
            case  7:
                return "Jule";
            case  8:
                return "August";
            case  9:
                return "September";
            case 10:
                return "October";
            case 11:
                return "November";
            case 12:
                return "December";
            default:
                return "January";
        }

    }

    public function setProjID()
    {
        //$data = ['projects' => new xmlrpcval('a:1:{s:8:&quot;projects&quot;;a:0:{}}', 'string')];
        //$msg = new xmlrpcval(null, 'struct');
        //$msg->addStruct($data);
        //print "<PRE>\n---PRE---\n" . htmlentities($msg->serialize()) . "\n---END---\n</PRE>";
        //die;

        //---------------------- list of projects
        $AuthString = serialize(array($this->login, $this->pass));

        $message = new xmlrpcmsg('result.viewProjects', array(new xmlrpcval($AuthString, 'string')));
        $result = $this->server->send($message); # xmlrpcresp()
        /** @var xmlrpcval $struct */
        $struct = $result->value();
        $projectsval = $struct->structmem('projects');
        /** @var string $projects */
        $projects = $projectsval->scalarval(); # last value from xmlrpcval->me[]
        $projects_array = unserialize(html_entity_decode($projects));


        foreach ($projects_array as $proj_arr) {
            $proj = $proj_arr['url'];
            if (stripos($proj, 'www.') === 0) {
                $proj = substr($proj, 4, strlen($proj));
            }
            if (strpos($proj, '/') == (strlen($proj) - 1)) {
                $proj = substr($proj, 0, strlen($proj) - 1);
            }
            if ($proj == $this->projName) {
                $this->projID = $proj_arr['id'];
                break;
            }
        }
    }

    protected function win2utf($val)
    {
        if (is_array($val)) {
            foreach ($val as &$item) {
                $item = mb_convert_encoding($item, 'utf-8', 'windows-1251');
            }
        } else {
            $val = mb_convert_encoding($val, 'utf-8', 'windows-1251');
        }
        return $val;
    }

    public function setRegionID()
    {
        //---------------- list of regions

        $param_string = serialize(array($this->login, $this->pass, $this->projID));
        /*
                $message = new xmlrpcmsg('result.viewCheckYandex', array(new xmlrpcval($param_string, 'string')));
                $message->xml_header('UTF-8');
                $result = $this->server->send($message);
                $struct = $result->value();
                $projectsval = $struct->structmem('regions');
                $regions = $projectsval->scalarval();
        */
        $message = new xmlrpcmsg('result.viewCheckYandex', array(new xmlrpcval($param_string, 'string')));
        $message->xml_header('UTF-8');
        $result = $this->server->send($message);
        $struct = $result->value();
        $regionsval = $struct->structmem('regions');
        $regions = $regionsval->scalarval();
        $regions = html_entity_decode($regions);


        $regions_array = unserialize(html_entity_decode($regions));
        //choose a region
        $regs = array();
        $regs['site'][] = 'npo-promek.ru';
        $regs['region'][] = 'Ekaterinburg';
        $regs['site'][] = 'promek-kuban.ru';
        $regs['region'][] = 'Krasnodar';
        $regs['site'][] = 'proekt-ek.ru';
        $regs['region'][] = 'Ekaterinburg';
        $regs['site'][] = 'td-komandor.ru';
        $regs['region'][] = 'Moscow';

        $siteRegion = '';

        $count = count($regs['site']);
        for ($i = 0; $i != $count; $i++) {
            if (strpos($this->projName, $regs['site'][$i]) !== false) {
                $siteRegion = $regs['region'][$i];
            }
        }

        foreach ($regions_array as $id => $reg) {
            if (!empty($siteRegion)) {
                if (strpos($this->win2utf($reg), $siteRegion) !== false) {
                    $this->regionID = $id;
                    break;
                }
            } else {
                $this->regionID = $id;
                break;
            }
        }
    }

    public function query($date)
    {
        //-------------------- position list

        $date_arr = explode(".", $date);
        $today = gmmktime(0, 0, 0, $date_arr[1], $date_arr[0], $date_arr[2]);

        $data_arr = array($this->regionID, $this->period, $this->login, $this->pass, $this->projID, $today);
        $ParamString = serialize($data_arr);

        $message = new xmlrpcmsg('result.viewPositionsYandexMonth', array(new xmlrpcval($ParamString, 'string')));
        $result = $this->server->send($message);
        $struct = $result->value();

        $positionsval = $struct->structmem('positions');
        $positions = $positionsval->scalarval();
        $positions = html_entity_decode($positions);
        $positions_arr = unserialize($positions);

        $this->resQuery = $positions_arr;

    }

    public function setDates4Months()
    {
        $this->date = date('d.m.Y', time());
        for ($i = 0; $i != 4; $i++) {
            $date_arr = explode(".", $this->date);
            $day = date('t', gmmktime(0, 0, 0, $date_arr[1], 1, $date_arr[2]));
            $today = gmmktime(0, 0, 0, $date_arr[1], $day, $date_arr[2]);
            $this->dates[$i]['month'] = $this->month2Word(date('n', $today) - 1);
            $this->dates[$i]['date'] = date('d.m.Y', $today);
            $this->date = date('d.m.Y', $today - (45 * 24 * 60 * 60));
        }
        $this->dates = array_reverse($this->dates);
    }

    public function getfDay($value)
    {
        $start_day = 100;
        foreach ($this->resQuery as $key => $pos_arr) {
            if (($this->win2utf($key) == 'Keyword')
                || ($this->win2utf($key) == 'top10')
                || ($this->win2utf($key) == 'top50')
                || ($this->win2utf($key) == 'topAbove')) {
                continue;
            }
            $size = count($pos_arr);
            for ($i = 2; $i != $size; $i++) {
                $this->debug[]['$i'] = $i;
                $this->debug[]['$pos_arr[$i]'] = $pos_arr[$i];
                $this->debug[]['$key'] = $key;

                if (is_numeric($pos_arr[$i]) && ($i < $start_day)) {
                    $start_day = $i;
                    break;
                }
            }
        }
        if ($start_day == 100) {
            return false;
        } else {
            return $start_day;
        }
    }

    public function setRes()
    {
        $this->setProjID();
        $this->setRegionID();
        $this->setDates4Months();

        for ($i = 0; $i != 4; $i++) {
            $fday = 0; //first day of the month for which statistics are available
            $date = $this->dates[$i]['date'];

            $this->query($date);
            if (!isset($this->mDates[$i])) {
                $fday = $this->getfDay($this->resQuery);
            } else {
                $fday = $this->mDates[$i] + 1;
            }
            $this->fday = $fday;

            foreach ($this->resQuery as $key => $pos_arr) {
                if ($this->win2utf($key) == 'Keyword') {
                    continue;
                }
                if ($this->win2utf($key) == 'top10') {
                    break;
                }
                $this->res[$this->win2utf($key)][$i] = $pos_arr[$fday];
            }
        }
        //dynamics calculation
        foreach ($this->res as $word => $pos) {
            if (is_numeric($pos[3]) && is_numeric($pos[2])) {
                $res = $pos[2] - $pos[3];
                if ($res > 0) {
                    $res = '+' . $res;
                }
            } else {
                $res = '—';
            }
            $this->res[$word][4] = $res;
        }
    }

    public function setTop()
    {
        $top3count = array();
        $top5count = array();
        $top10count = array();

        foreach ($this->res as $word) {
            for ($i = 0; $i != 4; $i++) {
                $this->count[$i] = $this->count[$i] ?? 0;
                $this->count[$i]++;  // += $word[$i];
                if (is_numeric($word[$i])) {
                    if ($word[$i] < 4) {
                        $top3count[$i] = $top3count[$i] ?? 0;
                        $top5count[$i] = $top5count[$i] ?? 0;
                        $top10count[$i] = $top10count[$i] ?? 0;
                        $top3count[$i]++;
                        $top5count[$i]++;
                        $top10count[$i]++;
                    } else if ($word[$i] < 6) {
                        $top5count[$i] = $top5count[$i] ?? 0;
                        $top10count[$i] = $top10count[$i] ?? 0;
                        $top5count[$i]++;
                        $top10count[$i]++;
                    } else if ($word[$i] < 11) {
                        $top10count[$i] = $top10count[$i] ?? 0;
                        $top10count[$i]++;
                    }
                }
            }
        }
        $this->top5count = $top5count;
        $this->top10count = $top10count;
        $qpositions = array(
            'top3' => array()
        , 'top5' => array()
        , 'top10' => array()
        );

        //position of requests in percent by month
        /*
                for ($i = 0; $i != 4; $i++) {
                    $qpositions['top3'][$i] = round($top3count[$i] * 100 / $this->count[$i], 0);
                    $qpositions['top5'][$i] = round($this->top5count[$i] * 100 / $this->count[$i], 0);
                    $qpositions['top10'][$i] = round($this->top10count[$i] * 100 / $this->count[$i], 0);
                }
        */
        for ($i = 0; $i != 4; $i++) {
            $qpositions['top3'][$i] = 1;
            $qpositions['top5'][$i] = 2;
            $qpositions['top10'][$i] = 3;
        }


        //dynamics of change
        $qpositions['top3'][4] = $qpositions['top3'][3] - $qpositions['top3'][2];
        $qpositions['top5'][4] = $qpositions['top5'][3] - $qpositions['top5'][2];
        $qpositions['top10'][4] = $qpositions['top10'][3] - $qpositions['top10'][2];

        $this->qpositions = $qpositions;

    }

    public function setHTMLq()
    {
        $html = "<h2>Site positions by queries</h2>\n<table border=\"1\">\n <thead>\n <tr>\n <th class=\"left\">Key phrase</th>\n";
        for ($i = 0; $i != 4; $i++) {
            $html .= '<th>' . $this->dates[$i]['month'] . "</th>\n";
        }
        $html .= "<th>Dynamics</th>\n</tr>\n </thead>\n <tbody>\n";

        $tbody = "";
        foreach ($this->res as $word => $pos) {
            $tbody .= "<tr>\n <td class=\"left\">" . $word . "</td>\n";
            for ($i = 0; $i != 4; $i++) {
                $tbody .= "<td>" . $pos[$i] . "</td>\n";
            }
            $tbody .= "<td>";
            $din = "";

            if ($pos[4] == 0) {
                $din = "<span class=\"null\">" . $pos[4] . "</span>";
            } else if ($pos[4] > 0) {
                $din = "<span class=\"plus\">" . $pos[4] . "</span>";
            } else {
                $din = "<span class=\"minus\">" . $pos[4] . "</span>";
            }
            $tbody .= $din . "</td>\n </tr>\n";
        }
        $html .= $tbody . "</tbody>\n </table>\n";
        $this->resHTML = $html;

    }

    public function setHTMLtop()
    {
        $html = "<h2>The position of the main queries</h2>\n<table border=\"1\">\n <thead>\n <tr>\n <th class=\"left\">Position of requests</th>\n";
        for ($i = 0; $i != 4; $i++) {
            $html .= '<th>' . $this->dates[$i]['month'] . "</th>\n";
        }
        $html .= "<th>Dynamics</th>\n</tr>\n </thead>\n <tbody>\n";

        $tbody = "";
        foreach ($this->qpositions as $word => $pos) {
            $tbody .= "<tr>\n <td class=\"left\">" . $word . "</td>\n";
            for ($i = 0; $i != 4; $i++) {
                $tbody .= "<td>" . $pos[$i] . "%</td>\n";
            }
            $tbody .= "<td>";
            $din = "";

            if ($pos[4] == 0) {
                $din = "<span class=\"null\">" . $pos[4] . "%</span>";
            } else if ($pos[4] > 0) {
                $din = "<span class=\"plus\">+" . $pos[4] . "%</span>";
            } else {
                $din = "<span class=\"minus\">" . $pos[4] . "%</span>";
            }
            $tbody .= $din . "</td>\n </tr>\n";
        }
        $html .= $tbody . "</tbody>\n </table>\n";
        $this->resHTML .= $html;

    }

    public function setHTML()
    {
        $this->setHTMLq();
        $this->setHTMLtop();
    }

    public function getHTML()
    {
        $this->setHTML();
        return $this->resHTML;
    }
}
