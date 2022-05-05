# amChart
Chart created with amChart
I used an opensource graph called amChart with PHP
To display periodic numeric/administration data in the chart
Ajax is used to make data request

The amChart.inc.php presents the view point were the user can set a query based on date (e.g. 01-01-2021 - 01-01-2022) the input of the user get collected with javascript once the user presses on search then ajax is used to make the request and sends it to amChart.ajax.php to make the connection with the database and get the response back to the graph

I started (small) with using direct php connection to get data and send it to te graph this is still visible on top of the amChart.inc.php page, afterwards when it worked I started with creating buttons that collect the user date selection then I console logged the results in the console of the browser when the correct data input has been previewed I went on to create the ajax function that then converts the data from javascript to php in the amChart.ajax.php file the connection to the database is created and gets the response of the query back to the graph
