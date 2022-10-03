<?php
// Load the Google API PHP Client Library.
require_once __DIR__ . '/vendor/autoload.php';

$analytics = initializeAnalytics();
$response = getReport($analytics);
list($final_dates, $pageviews_by_day) = printResults($response);

/**
 * Initializes an Analytics Reporting API V4 service object.
 *
 * @return An authorized Analytics Reporting API V4 service object.
 */
function initializeAnalytics()
{

  // Use the developers console and download your service account
  // credentials in JSON format. Place them in this directory or
  // change the key file location if necessary.
  $KEY_FILE_LOCATION = __DIR__ . '/service-account-credentials.json';

  // Create and configure a new client object.
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);

  return $analytics;
}

/**
 * Queries the Analytics Reporting API V4.
 *
 * @param service An authorized Analytics Reporting API V4 service object.
 * @return The Analytics Reporting API V4 response.
 */
function getReport($analytics) {
  $VIEW_ID = "18399576";

  $query = [
    "viewId" => $VIEW_ID,

    "dateRanges" => [
        "startDate" => "7daysAgo",
        "endDate" => "yesterday"
    ],

    "dimensions" => [
        ["name" => "ga:date"],
        ["name" => "ga:pagePath"],
    ],

    "dimensionFilterClauses" => [
        'filters' => [
            "dimension_name" => "ga:pagePath",
            "operator" => "REGEXP",
            "expressions" => ["/imoveis"]
        ]
    ],

    "metrics" => [
      "expression" => "ga:pageviews"
    ],

    "orderBys" => [
      "fieldName" => "ga:date",
      "orderType" => "VALUE",
      "sortOrder" => "ASCENDING"
    ],

    "pageSize" => "100000",
    "includeEmptyRows" => true,
    "hideTotals" => false,
    "hideValueRanges" => false,
];

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests(array($query));
  return $analytics->reports->batchGet($body);
}

/**x
 * Parses and prints the Analytics Reporting API V4 response.
 *
 * @param An Analytics Reporting API V4 response.
 */
function printResults($reports) {
  
  $response_length = $reports[0]->data->rowCount;
  $all_rows = $reports[0]->data->rows;
  $all_dates = [];
  $pageviews_by_day = [];

  // get all dates
  for ($i = 0; $i < $response_length; $i++) {
    $all_dates[$i] = $all_rows[$i]->dimensions[0];
  };
  // get all dates

  // get all right dates
  $right_dates = array_unique($all_dates);
  $final_dates = array_values($right_dates);
  // get all right dates

  $final_dates_length = count($final_dates);

  for ($i = 0; $i < $final_dates_length; $i++) {
    $pageviews_by_day[$i] = 0;
  };

  for ($i = 0; $i < $final_dates_length; $i++) { 
    for ($j = 0; $j < $response_length; $j++) { 
      if ($all_rows[$j]->dimensions[0] == $final_dates[$i]) {
        $pageviews_by_day[$i] += $all_rows[$j]->metrics[0]->values[0];
      };
    };
  };

  return array($final_dates, $pageviews_by_day);
}
?>

<html>
  <head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">

      window.onload = function () {

        google.charts.load('current', {'packages':['corechart']});

        google.charts.setOnLoadCallback(drawChart);

        function drawChart() {

          var data = new google.visualization.DataTable();
          data.addColumn('string', 'Dia');
          data.addColumn('number', 'Pageviews');


          var date = <?php echo json_encode($final_dates); ?>

          var pageview_by_day = <?= json_encode($pageviews_by_day); ?>

          var arrays_size = date.length;
          

          for (i = 0; i < arrays_size; i++) {
            let a = JSON.stringify(date[i])
            let regex_date = a.replace(/^(\d{4})(\d{1,2})(\d{1,2})$/, "$3/$2/$1");
            let b = pageview_by_day[i]
            data.addRow([regex_date, b])
          }
          
          var options = {'title':'Pageviews dos Imóveis',
                        'width':1500,
                        'height':500};

          var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));
          chart.draw(data, options);
        }

      }

    </script>
  </head>

  <body>
    <!--Div that will hold the pie chart-->
    <div id="chart_div" style="position: relative; top: 30%; left: 55%; transform: translate(-45%, -1%);"></div>
  </body>
</html>