<?php

ini_set("session.use_cookies", 1);
ini_set("session.use_trans_sid", 0);
ini_set("session.auto_start", 0);
error_reporting(0);
session_start();
if (!isset($_SESSION['rev_seo'])) {
    $logout = 'hidden';
    header('Location: login.php');
} else {
    require_once('class/_date.php');
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    ?>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <link rel="stylesheet" href="css/stat.css">
        <script type="text/javascript" src="lib/ckeditor/ckeditor.js"></script>

    </head>
    <body>
    <div class="header">
        <div id="name"><a href="<?php echo "http://" . $_SERVER['SERVER_NAME'] . "/report" ?>">Site progress report</a>
        </div>
        <div id="logout"><a href="<?php echo "http://" . $_SERVER['SERVER_NAME'] . "/report/logout.php" ?>">Sign out</a>
        </div>
    </div>
    <div class="report">

        <form action="index.php" method="get">
            <label for="site">Site name (example.com): </label>

            <input type="text" name="site" value="<?php if (isset($_GET['site'])) {
                echo $_GET['site'];
            } ?>">
            <?php
            for ($j = 4; $j != 0; $j--) {
                $m = my_date::getMonth(-$j);
                echo my_date::month2word($m[0]) . ' <select name="month' . $j . '">';
                echo '<option name="month' . $j . '[]" value="0"></option>';
                $mm = 'month' . $j;
                for ($i = 1; $i != $m[1] + 1; $i++) {
                    if (!empty($_GET[$mm]) && $_GET[$mm] == $i) {
                        echo '<option name="month' . $j . '[]" value="' . $i . '" selected>' . $i . '</option>';
                    } else {
                        echo '<option name="month' . $j . '[]" value="' . $i . '">' . $i . '</option>';
                    }
                }

                echo '</select>';
            }
            ?>


            <input type="submit" name="getstat" value="Generate report">
        </form>


        <?php

        $html = '';
        if (isset($_GET['getstat']) && !empty($_GET['site'])) {
//require_once('http/request2.php');

            $mDates = array();
            for ($i = 0; $i != 4; $i++) {
                $m = 'month' . ($i + 1);
                if (!empty($_GET[$m])) {
                    $mDates[$i] = $_GET[$m];
                } else {
                    $mDates[$i] = null;
                }
            }
            require_once('metrika.php');
            $token = "7541404faac016ab56b3ac3f33c506f8";

            $login = 'avanta';
            $pass = md5('Begemot1985');//for seolib.ru

            $site = $_GET['site'];


            $visits = new visiting($token, $site);
            $searchers = new fromsearchers($token, $site);
            $phrases = new phrases($token, $site);
            $sites = new sites($token, $site);
            $seolib = new seolib($site, $login, $pass, array_reverse($mDates));


            $img = '<div class="center"><img src="' . $visits->getGraph() . '" /></div>';
            $title = '<div id="titul"><h1 id="title">Site progress report</h1><hr />';
            $title .= '<span id="site"><a href="http://www.' . $site . '">http://www.' . $site . '/</a></span>';
            $title .= '<br /><span id="date">' . $sites->monthToRus(date('n', time()) - 1) . ' ' . date('Y', time()) . '</span></div><pagebreak />';

            require_once('class/res.php');

            $html = $title . "<h2>Results</h2>" . $res;
            $html .= $seolib->getHTML() . "<pagebreak /><h2>Traffic</h2>" . $img;
            $html .= $visits->getHTML();

            $html .= "<h2>Referrals from search engines (Top5*)</h2>";

            $html .= $searchers->getHTML() . "<span style=\"font-size: small;\">* If there is data for the specified number of positions. </span>";
            $html .= "<pagebreak /><h2>The largest number of visitors from search engines by search phrases (Top20*)</h2>";
            $html .= $phrases->getHTML() . "<span style=\"font-size: small;\">* If there is data for the specified number of positions. </span>";
            $html .= "<pagebreak /><h2>Website referrals (Top20*)</h2>";
            $html .= $sites->getHTML() . "<span style=\"font-size: small;\">* If there is data for the specified number of positions. </span>";

        }

        ?>
        <form action="export.php" method="post">
            <input type='hidden' name='sitename' value='<?php echo $_GET['site'] ?? ''; ?>'>
            <p>
        <textarea cols="80" id="reportText" name="reportText" rows="80">
        <?php echo $html; ?>
        </textarea>
                <script type="text/javascript">
                    CKEDITOR.replace('reportText');
                    CKEDITOR.add;
                    CKEDITOR.config.height = '35em';
                    CKEDITOR.config.contentsCss = '/report/css/report.css';
                </script>

            </p>
            <p>
                <input type="submit" value="Save to pdf"/>
            </p>
        </form>

    </div>
    </body>
    </html>

<?php } ?>