<?php
$res = "From {fday} to {lday} {month} {year} year site was visited by <b>{people}</b> visitors, which is {compare} than the total site traffic in {monthprev} by <b>{less}</b> visitors. Out of those <b>{search}</b> visits from search engines, thereby, the share of visits from search engines was <b>{percent}%</b> of the total number of site visits.
    The site occupies a high position in Yandex for <b>{x}</b> out of <b>{n}</b> key phrases (<b>{percx}%</b> of the total number of requests from the semantic core) .
    ";

function month2Rus1($num)
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

function month2Rus2($num)
{
    $month = array();
    $month[1] = 'January`';
    $month[2] = 'February`';
    $month[3] = 'March`';
    $month[4] = 'April`';
    $month[5] = 'May`';
    $month[6] = 'June`';
    $month[7] = 'Jule`';
    $month[8] = 'August`';
    $month[9] = 'September`';
    $month[10] = 'October`';
    $month[11] = 'November`';
    $month[12] = 'December`';
    return $month[$num];
}


$rFday = date('j', strtotime($sites->datesForMonths[0]['date1']));
$rLday = date('j', strtotime($sites->datesForMonths[0]['date2']));
$rMonth = month2Rus1($sites->datesForMonths[0]['month']);
$rMonthPrev = month2Rus2($sites->datesForMonths[1]['month']);
$rYear = date('Y', strtotime($sites->datesForMonths[0]['date2']));
$rPeople = $visits->months[0]['totals'];
$rPeoplePrev = $visits->months[1]['totals'];
$rLess = $rPeople - $rPeoplePrev;
if ($rLess < 0) {
    $rCompare = "less";
} else if ($rLess > 0) {
    $rCompare = "more";
} else {
    $rCompare = "equal";
}
$rLess = abs($rLess);
$rSearch = $visits->months[0]['searchEngins'];
$rPercent = round($rSearch * 100 / $rPeople, 0);


if (is_numeric($seolib->top10count[3])) {
    $rX = $seolib->top10count[3];
} else {
    $rX = 0;
}
$count = $seolib->count[3];
if (is_numeric($count)) {
    $rN = $seolib->count[3];
} else {
    $rN = 0;
}
//$rN = $seolib->count[0];
$rPercX = round($seolib->top10count[3] * 100 / $seolib->count[3], 0);

$res = str_replace('{fday}', $rFday, $res);
$res = str_replace('{lday}', $rLday, $res);
$res = str_replace('{month}', $rMonth, $res);
$res = str_replace('{year}', $rYear, $res);
$res = str_replace('{people}', $rPeople, $res);
$res = str_replace('{compare}', $rCompare, $res);
$res = str_replace('{monthprev}', $rMonthPrev, $res);
$res = str_replace('{less}', $rLess, $res);
$res = str_replace('{search}', $rSearch, $res);
$res = str_replace('{percent}', $rPercent, $res);

$res = str_replace('{x}', $rX, $res);
$res = str_replace('{n}', $rN, $res);
$res = str_replace('{percx}', $rPercX, $res);
