<?php

// select multiple rows
$_sel = "
     SELECT  - SUM(tbl_data.amount) as amount,
                       calc_year_month,
                       YEAR(calc_date) as calc_date_year,
                        MONTH(calc_date) as calc_date_month,
                        DAY(calc_date) as calc_date_day,
                        calc_date

                FROM tbl_data
                WHERE tbl_data.connection_id = '$connection_id' AND

                      calc_year_month BETWEEN '201901' AND '202112' AND
                      ledger_type_id IN (80,81,82)
                GROUP BY calc_year_month
                ORDER  BY calc_year_month
    ;";

$q_res_sales_per_month = $oDbConn->selectAll($_sel);

$chartData = [];

foreach ($q_res_sales_per_month as $key){
    $chartData[] = (object)[
        'date' => substr($key["calc_date"], 0, 10),
        'value' => $key["amount"],
    ];
}

$chart_data_json = json_encode($chartData);
//print_array($chartData, '$chartData');

$q_res_num_rows = $oDbConn->getNumRows();

$q_res_sales_per_month_json = json_encode($q_res_sales_per_month);

$test_array = [
        [
            'date'=>'Jan',
            '2017'=>450,
            '2018'=>362,
            '2019'=>410
            ],
    [
        'date'=>'Feb',
        '2017'=>455,
        '2018'=>-367,
        '2019'=>423
    ],
    [
        'date'=>'Mrt',
        '2017'=>460,
        '2018'=>382,
        '2019'=>405
    ],
    [
        'date'=>'Apr',
        '2017'=>440,
        '2018'=>352,
        '2019'=>400
    ],
];


$test_array_json = json_encode($test_array);
//echo "<pre>test_array_json= $test_array_json <br>";

/* @var $oDateUtility \Claystone\Util\DateUtility */
$oDateUtility = $cScontainer->create('\Claystone\Util\DateUtility');

$oDateUtility->ini(
    [
        'date_from'=>'20190101',
        'date_until'=>'20211231',
        'interval'=>'per_month'
    ]);

$oDateUtility->buildPeriods();

// get all periods
$temp_periods = $oDateUtility->getPeriods();
//print_array($temp_periods, '$temp_periods');


$temp_months = $oDateUtility->getMonths();
//print_array($temp_months, '$temp_months');

$temp_years = $oDateUtility->getYears();
//print_array($temp_years, '$temp_years');

$oDateUtility->buildMonthsYearsStacked();
$temp_month_years = $oDateUtility->getMonthsYears();
//print_array($temp_month_years, '$temp_month_years');

// ini final chart data
$temp_chart_data = [];
$temp_chart_data2 = [];

// loop each month
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

//        echo "key= $key key2= $key2     start_date= $start_date  end_date= $end_date<br>";

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

        // add sales per month year to final cahrt data
        $temp_chart_data[$key][$key2] = (!empty($q_res_tot_amount) ? $q_res_tot_amount : '0');
    endforeach;

    // reset month array into final chart_data
    $temp_chart_data2[] = $temp_chart_data[$key];
endforeach;

//$temp_chart_data3[] = $temp_chart_data2;

//print_array($temp_chart_data, '$temp_chart_data');
$test_array_json = json_encode($temp_chart_data2);


?>

<style>
    /*#chartdiv {*/
    /*    width: 100%;*/
    /*    height: 500px;*/
    /*    max-width: 100%;*/
    /*}*/
</style>

<div>
    <h2>amChart update</h2>
    <form action="" method="get" id="am_chart_query_selection">
        <label for="date_from">Search period:</label>
        <input type="date" id="date_from" name="date_from">

        <label for="date_untill"> t/m </label>
        <input type="date" id="date_untill" name="date_untill">

        <button id="submit" name="submit" value="search">search</button>
    </form>
    <button id="toggling_ssline_and_msline">view multi serie</button>
    <button id="toggling_between_line_and_bar">column chart</button>

    <div id="chartdiv"></div>
</div>

<script>

    // import * as am4core from "@amcharts/amcharts4/core";
    am4core.ready(function() {

        am4core.useTheme(am4themes_animated);

        let items = {};
        // items["toggle"] = 0;

        let toggle_single_or_stacked = 0;

        let start_date = '';

        let end_date = '';

// Create chart instance
        let chart = am4core.create("chartdiv", am4charts.XYChart);

// Add data
        chart.data = [{
            "2017": 450,
            "2018": 362,
            "2019": 410,
            "date": 'Jan'
        }, {
            "2017": 455,
            "2018": 365,
            "2019": 415,
            "date": 'Feb'
        }, {
            "2017": 450,
            "2018": 362,
            "2019": 425,
            "date": 'Mrt'
        }, {
            "2017": 450,
            "2018": 362,
            "2019": 435,
            "date": 'Apr'
        }];

        //chart.data = <?//= $test_array_json ?>//;



// Create axes
        let dateAxis = chart.xAxes.push(new am4charts.CategoryAxis());
        dateAxis.renderer.grid.template.location = 0;
        dateAxis.dataFields.category = "date";

        let valueAxis = chart.yAxes.push(new am4charts.ValueAxis());

        chart.scrollbarX = new am4core.Scrollbar();

// Create series
        function createSeries(field, name) {

            let series = chart.series.push(new am4charts.LineSeries());
            series.dataFields.valueY = field;

            series.dataFields.categoryX = "date";
            series.name = name;
            series.tooltipText = "Sales in on date {categoryX}: [bold]{valueY}[/]";
            series.strokeWidth = 2;

            let bullet = series.bullets.push(new am4charts.CircleBullet());
            bullet.circle.stroke = am4core.color("#fff");
            bullet.circle.strokeWidth = 2;

            return series;
        }

        <?php
//        $i = 1;
//        foreach ($temp_years as $key=> $value_):
//        ?>
//        createSeries("<?//= $temp_years[$key] ?>//", "ASeries#" + <?//= $temp_years[$key] ?>//);
//
//        <?php
//        $i++;
//        endforeach;
        ?>
        // let series1 = createSeries("2019", "Series#2019");
        // let series2 = createSeries("2020", "Series#2020");
        // let series3 = createSeries("2021", "Series#2021");


        console.log("chart.length[0]",Object.keys(chart.data[0]));

        let count_serie = Object.keys(chart.data[0]);

        count_serie.pop();

        console.log("count_serie",count_serie);

        for(let i = 0; i < count_serie.length; i++ ) {
            // let serie_val = "value" + i;
            let serie_name = count_serie[i];

            // console.log("length serie prop", count_serie.length);
            // console.log("Series#", serie_name);
            // console.log("Series#", serie_val);

            createSeries(serie_name, serie_name);
        }


        chart.legend = new am4charts.Legend();
        chart.cursor = new am4charts.XYCursor();

        // Loading Indicator

        let indicator;
        let indicatorInterval;

        function showIndicator() {

            if (!indicator) {
                indicator = chart.tooltipContainer.createChild(am4core.Container);
                indicator.background.fill = am4core.color("#fff");
                indicator.background.fillOpacity = 0.8;
                indicator.width = am4core.percent(100);
                indicator.height = am4core.percent(100);

                var indicatorLabel = indicator.createChild(am4core.Label);
                indicatorLabel.text = "Loading stuff...";
                indicatorLabel.align = "center";
                indicatorLabel.valign = "middle";
                indicatorLabel.fontSize = 20;
                indicatorLabel.dy = 50;

                var hourglass = indicator.createChild(am4core.Image);
                hourglass.href = "https://s3-us-west-2.amazonaws.com/s.cdpn.io/t-160/hourglass.svg";
                hourglass.align = "center";
                hourglass.valign = "middle";
                hourglass.horizontalCenter = "middle";
                hourglass.verticalCenter = "middle";
                hourglass.scale = 0.7;
            }

            indicator.hide(0);
            indicator.show();

            clearInterval(indicatorInterval);
            indicatorInterval = setInterval(function() {
                hourglass.animate([{
                    from: 0,
                    to: 360,
                    property: "rotation"
                }], 2000);
            }, 3000);
        }

        function hideIndicator() {
            indicator.hide();
            clearInterval(indicatorInterval);
        }


        $("#am_chart_query_selection").on('submit', function(e){
            e.preventDefault();

            // chart.preloader.disabled = true;
            showIndicator();

            let date_from = new Date($('#date_from').val());
            let day_from = date_from.getDate() < 9 ? '0' + (date_from.getDate()) : date_from.getDate();
            // let day_from = date_from.getDate();
            let month_from = date_from.getMonth() < 9 ? '0' + (date_from.getMonth() + 1) : date_from.getMonth() + 1 ;
            // let month_from = date_from.getMonth() + 1;
            let year_from = date_from.getFullYear();

            //get date until
            let date_untill = new Date($('#date_untill').val());
            let day_untill = date_untill.getDate() < 9 ? '0' + (date_untill.getDate()) : date_untill.getDate();
            // let day_untill = date_untill.getDate();
            let month_untill = date_untill.getMonth() + 1 < 9 ? '0' + (date_untill.getMonth() + 1) : date_untill.getMonth() + 1 ;
            // let month_untill = date_untill.getMonth() + 1;
            let year_untill = date_untill.getFullYear();
            // let from = [day_from, month_from, year_from].join('/');
            // let format_start_date = [year_from, month_from, day_from].join('');
            let format_start_date = [year_from, month_from, day_from].join('');
            start_date = String(format_start_date);
            // let untill = [day_untill, month_untill, year_untill].join('/');
            // let format_end_date = [year_untill, month_untill, day_untill].join('');
            let format_end_date = [year_untill, month_untill, day_untill].join('');
            end_date = String(format_end_date);
            items = {start: start_date, end: end_date, toggle: toggle_single_or_stacked};

            // function getArrayMax(array){
            //     return Math.max.apply(null, array);
            // }
            // function getArrayMin(array){
            //     return Math.min.apply(null, array);
            // }

           let new_amChart_data = '';
//
            console.log(items);
            $.ajax({
                url: '/bo/dev_teddy/ajax/amChart.ajax.php', //?start='+start_date+'&end='+end_date,
                method: "GET",
                data: items,
                dataType: "JSON",
                success: function(response){
                    console.log("stringify", JSON.stringify(response));
                    console.log("regular", response);

                    // chart.preloader.disabled = false;
                    hideIndicator();

                    // This will remove current series
                    chart.series.clear();

                    // collect dates
                    chart.data = response[0];

                    console.log("full Chart",chart.data);
                    console.log("piece Chart",chart.data["Jan"]);

                    // response third elemnt contains all years as keys without date
                    // let count_serie = Object.values(response[0])[0];
                    // let switch_serie = Object.values(chart.data)[0];
                    // console.log("switch_serie",switch_serie);
                    //
                    // // delete date before looping the years
                    // delete switch_serie.date;

                    if(response[1] == 0 ){

                        let arr = [];

                        for(let key in chart.data){
                            if (chart.data.hasOwnProperty(key)) {
                                let year = Object.keys(chart.data[key])[0];
                                if (arr.indexOf(year) == -1) {
                                    arr.push(year);
                                }
                            }
                        }

                        for(let i = 0; i < arr.length; i++ ) {
                            let serie_val = "value" + i;
                            let serie_name = arr[i];

                            // console.log("length serie prop", count_serie.length);
                            // console.log("Series#", serie_name);
                            // console.log("Series#", serie_val);

                            createSeries(serie_name, serie_name);
                        }

                    } else if (response[1] == 1) {

                        let arr_two = [];
                        let new_years = chart.data[0];
                        delete new_years.date;
                        for (let key in new_years) {

                            // if (chart.data["jan"].hasOwnProperty(key)) {

                            console.log("key", key);
                            // if (arr.indexOf(year) == -1) {
                            arr_two.push(key);
                            // }
                            // }
                        }

                        // arr_two.pop();
                        console.log("arr_twoß", arr_two);

                        for (let i = 0; i < arr_two.length; i++) {
                            // let serie_val = "value" + i;
                            let serie_name = arr_two[i];

                            createSeries(serie_name, serie_name);
                        }

                    }

                    // chart.legend.invalidate();
                    chart.invalidateData();

                },
                error: function (e) {
                    alert('Something went wrong with uploading the data');
                    console.log("Unsuccessful:", e);
                }
            });

            // chart.data = new_amChart_data;
        });


    $("#toggling_ssline_and_msline").on('click', function(e) {
        e.preventDefault();

        // chart.preloader.disabled = true;
        showIndicator();

        // check if there is toggle set
        if(items["toggle"] == 0){
            toggle_single_or_stacked = 1;
            console.log('change chart type: ',items["toggle"]);
            // change text of button
            this.textContent = 'view single line';
        } else if (items["toggle"] == 1){
            // set view
            toggle_single_or_stacked = 0;
            console.log('change chart type: ',items["toggle"]);
            // change text of button
            this.textContent = 'view multi line';
        } else {
            // if no key, display no data to convert
            alert("No data to visualize");
        }

        items = {start: start_date, end: end_date, toggle: toggle_single_or_stacked};

        console.log(items);
        $.ajax({
            url: '/bo/dev_teddy/ajax/amChart.ajax.php', //?start='+start_date+'&end='+end_date,
            method: "GET",
            data: items,
            dataType: "JSON",
            success: function(response){
                console.log("stringify", JSON.stringify(response));
                console.log("regular", response);

                // chart.preloader.disabled = false;
                hideIndicator();

                // This will remove current series
                chart.series.clear();

                // collect dates
                chart.data = response[0];

                console.log("full Chart",chart.data);
                console.log("piece Chart",chart.data["Jan"]);

                // response third elemnt contains all years as keys without date
                // let count_serie = Object.values(response[0])[0];
                // let switch_serie = Object.values(chart.data)[0];
                // console.log("switch_serie",switch_serie);
                //
                // // delete date before looping the years
                // delete switch_serie.date;

                if(response[1] == 0 ){

                    let arr = [];

                    for(let key in chart.data){
                        if (chart.data.hasOwnProperty(key)) {
                            let year = Object.keys(chart.data[key])[0];
                            if (arr.indexOf(year) == -1) {
                                arr.push(year);
                            }
                        }
                    }

                    for(let i = 0; i < arr.length; i++ ) {
                        let serie_val = "value" + i;
                        let serie_name = arr[i];

                        // console.log("length serie prop", count_serie.length);
                        // console.log("Series#", serie_name);
                        // console.log("Series#", serie_val);

                        createSeries(serie_name, serie_name);
                    }

                } else if (response[1] == 1) {

                    let arr_two = [];
                    let new_years = chart.data[0];
                    delete new_years.date;
                    for (let key in new_years) {

                        // if (chart.data["jan"].hasOwnProperty(key)) {

                            console.log("key", key);
                            // if (arr.indexOf(year) == -1) {
                            arr_two.push(key);
                            // }
                        // }
                    }

                    // arr_two.pop();
                    console.log("arr_twoß", arr_two);

                    for (let i = 0; i < arr_two.length; i++) {
                        // let serie_val = "value" + i;
                        let serie_name = arr_two[i];

                        createSeries(serie_name, serie_name);
                    }

                }

                // chart.legend.invalidate();
                chart.invalidateData();

            },
            error: function (e) {
                alert('Something went wrong with uploading the data');
                console.log("Unsuccessful:", e);
            }
        });

        // chart.data = new_amChart_data;
    });


    $("#toggling_between_line_and_bar").on('click', function(e) {
        e.preventDefault();

        // chart.preloader.disabled = true;
        showIndicator();

        // check if there is toggle set
        if(items["toggle"] == 0){
            toggle_single_or_stacked = 1;
            console.log('change chart type: ',items["toggle"]);
            // change text of button
            this.textContent = 'view ss column';
        } else if (items["toggle"] == 1){
            // set view
            toggle_single_or_stacked = 0;
            console.log('change chart type: ',items["toggle"]);
            // change text of button
            this.textContent = 'view ms column';
        } else {
            // if no key, display no data to convert
            alert("No data to visualize");
        }

        function createSeries(field, name) {

            let series = chart.series.push(new am4charts.ColumnSeries());
            series.dataFields.valueY = field;

            series.dataFields.categoryX = "date";
            series.name = name;
            series.clustered = false;
            series.columns.template.width = am4core.percent(50);
            series.tooltipText = "Sales in on date {categoryX}: [bold]{valueY}[/]";
            series.strokeWidth = 2;

            let bullet = series.bullets.push(new am4charts.CircleBullet());
            bullet.circle.stroke = am4core.color("#fff");
            bullet.circle.strokeWidth = 2;

            return series;
        }

        items = {start: start_date, end: end_date, toggle: toggle_single_or_stacked};

        console.log(items);
        $.ajax({
            url: '/bo/dev_teddy/ajax/amChart.ajax.php', //?start='+start_date+'&end='+end_date,
            method: "GET",
            data: items,
            dataType: "JSON",
            success: function(response){
                console.log("stringify", JSON.stringify(response));
                console.log("regular", response);

                // chart.preloader.disabled = false;
                hideIndicator();

                // This will remove current series
                chart.series.clear();

                // collect dates
                chart.data = response[0];

                console.log("full Chart",chart.data);
                console.log("piece Chart",chart.data["Jan"]);

                // response third elemnt contains all years as keys without date
                // let count_serie = Object.values(response[0])[0];
                // let switch_serie = Object.values(chart.data)[0];
                // console.log("switch_serie",switch_serie);
                //
                // // delete date before looping the years
                // delete switch_serie.date;

                if(response[1] == 0 ){

                    let arr = [];

                    for(let key in chart.data){
                        if (chart.data.hasOwnProperty(key)) {
                            let year = Object.keys(chart.data[key])[0];
                            if (arr.indexOf(year) == -1) {
                                arr.push(year);
                            }
                        }
                    }

                    for(let i = 0; i < arr.length; i++ ) {
                        let serie_val = "value" + i;
                        let serie_name = arr[i];

                        // console.log("length serie prop", count_serie.length);
                        // console.log("Series#", serie_name);
                        // console.log("Series#", serie_val);

                        createSeries(serie_name, serie_name);
                    }

                } else if (response[1] == 1) {

                    let arr_two = [];
                    let new_years = chart.data[0];
                    delete new_years.date;
                    for (let key in new_years) {

                        // if (chart.data["jan"].hasOwnProperty(key)) {

                        console.log("key", key);
                        // if (arr.indexOf(year) == -1) {
                        arr_two.push(key);
                        // }
                        // }
                    }

                    // arr_two.pop();
                    console.log("arr_twoß", arr_two);

                    for (let i = 0; i < arr_two.length; i++) {
                        // let serie_val = "value" + i;
                        let serie_name = arr_two[i];

                        createSeries(serie_name, serie_name);
                    }

                }

                // chart.legend.invalidate();
                chart.invalidateData();

            },
            error: function (e) {
                alert('Something went wrong with uploading the data');
                console.log("Unsuccessful:", e);
            }
        });
    });

    }); // end am4core.ready()

</script>
