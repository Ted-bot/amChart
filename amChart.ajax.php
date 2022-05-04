<?php

//require(_CS_CORE_PAGES_ . 'html_header4.inc.php');

/* @var $oDateUtility \Claystone\Util\DateUtility */
$oDateUtility = $cScontainer->create('\Directory\Class\DateClass');

/**      Get input from user       **/
$start = $_GET["start"];
$end = $_GET["end"];
$view = $_GET["toggle"];

function sendJSON($data, $terminate = true) {
    header('Content-type: application/json');
    echo json_encode($data);
    if ($terminate) die();
}


$oDateUtility->ini(
    [
        'date_from' => $start,
        'date_until'=> $end,
        'interval'=>'per_month'
    ]);

$oDateUtility->buildPeriods();

// get all periods
$temp_periods = $oDateUtility->getPeriods();Z

$temp_years = $oDateUtility->getYears();

$oDateUtility->buildMonthsYearsStacked();
$temp_month_years = $oDateUtility->getMonthsYears();

// ini final chart data
$temp_chart_data = [];
$temp_chart_data2 = [];
$temp_chart_data3 = [];

$temp_chart_for_create_serie = [];

if($view == 0){
    /**     Load single series in Chart      **/

    foreach($temp_periods as $key => $value):
        // skip when key=date, no data needs to be adjusted
        if ($key === 'date') {

            continue;
        }

        // set select dates per month+year combi
        $start_date = $value['date_month_from'];
        $end_date = $value['date_month_until'];

        // get sales amounts
        $_sel = "
         SELECT  - SUM(tbl_data.amount) as tot_amount
        FROM    tbl_data
        WHERE   tbl_data.connection_id = '$connection_id' AND
                tbl_data.calc_date BETWEEN '{$start_date}' AND '{$end_date}' AND
                tbl_data.ledger_type_id IN (80,81,82)
        GROUP BY tbl_data.connection_id, tbl_data.calc_year_month
        ;";

        // returns only value (higher speed then row)
        $q_res_tot_amount = $oDbConn->selectValue($_sel, 'noshow');

//        $q_res_tot_amount = $value["amount"];
        $q_res_num_rows = $oDbConn->getNumRows();

        /**  create single line format */

        // add sales per month year to final chart data
        $temp_chart_data3[0][] = (object)[
            $value["year"] => $q_res_tot_amount,
            'date' => $value["month_year_name"]
        ];

    endforeach;
    $temp_chart_data3[1] = $view;
    sendJSON($temp_chart_data3);
    exit();
} else if ($view == 1) {
    /**     Load Multi series in Chart      **/

    foreach ($temp_month_years as $key=>$val):

        // set x-axis name
        $temp_chart_data[$key]['date'] = $key;

        // loop per month each selected year
        foreach ($temp_month_years[$key] as $key2=>$val2):

            // skip when key=date, no data needs to be adjusted
            if ($key2 === 'date') {

                continue;
            }

            // set select dates per month+year combi
            $start_date = $val2['date_month_from'];
            $end_date = $val2['date_month_until'];


            // get sales amounts
            $_sel = "
         SELECT  - SUM(tbl_data.amount) as tot_amount
        FROM    tbl_data
        WHERE   tbl_data.connection_id = '$connection_id' AND
                tbl_data.calc_date BETWEEN '{$start_date}' AND '{$end_date}' AND
                tbl_data.ledger_type_id IN (80,81,82)
        GROUP BY tbl_data.connection_id, tbl_data.calc_year_month
        ;";

            // returns only value (higher speed then row)
            $q_res_tot_amount = $oDbConn->selectValue($_sel, 'noshow');
            $q_res_num_rows = $oDbConn->getNumRows();

            // add sales per month year to final chart data
            $temp_chart_data[$key][$key2] = (!empty($q_res_tot_amount) ? round($q_res_tot_amount, 2) : '0');


//            $temp_chart_for_create_serie[$key][$key2] = (!empty($q_res_tot_amount) ? round($q_res_tot_amount, 2) : '0');


        endforeach;

        /**  create multi line format */
        // reset month array into final chart_data
        $temp_chart_data2[0] = array_values($temp_chart_data);

    endforeach;

    $temp_chart_data2[1] = $view;

    $temp_chart_data2[2] = $temp_chart_for_create_serie;

    sendJSON($temp_chart_data2);

    exit();
}



