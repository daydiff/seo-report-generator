<?php

class my_date
{
    public static function getMonth($n)
    {
        $date1 = '';
        $date2 = '';
        $month = array();

        $day = (int)date('j') - 1;
        $bufDateTime = time() - ($day * 24 * 60 * 60);

        //find start and end dates of months
        for ($i = 0; $i != abs($n); $i++) {
            $bufDateTime = $bufDateTime - (1 * 24 * 60 * 60);
            $date2 = date('Ymd', $bufDateTime);
            $date = date('j', $bufDateTime);
            $bufDateTime = $bufDateTime - (((int)date('j', $bufDateTime) - 1) * 24 * 60 * 60);
            $date1 = date('Ymd', $bufDateTime);
            $month[0] = date('n', $bufDateTime);
            $month[1] = $date;
        }
        return $month;
    }

    public static function month2word($num)
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


}
