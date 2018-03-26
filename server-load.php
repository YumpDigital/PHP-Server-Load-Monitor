<?php
/**
 * This script is used for tracking the server load figures
 * 
 * It is run in 2 ways:
 * 
 *   1. As a cron task that records the server load figures every X mins
 *   2. When run in browser is displays the results in a chart
 */

define('SERVER', '');
define('USER', '');
define('PASSWORD', '');
define('DATABASE', '');
define('NUMBER_OF_CORES', 6);
define('TABLE', 'monitor');


$c = mysql_connect(SERVER, USER, PASSWORD);
mysql_select_db(DATABASE);

// If called with 'savetodb' parameter, get the load, save it to database, then exit
if((isset($_GET['savetodb']) && $_GET['savetodb']) || $argv[1]=='savetodb') {

	$loads = sys_getloadavg();

	print_r($loads);

	$q= "INSERT INTO `".DATABASE."`.`".TABLE."` (`timestamp`, `load1`, `load5`, `load15`) 
		VALUES ( CURRENT_TIMESTAMP, '".$loads[0]."', '".$loads[1]."', '".$loads[2]."');";

	mysql_query($q); 

// Otherwise display the graph of data
} else {

	$q = "SELECT * FROM `".DATABASE."`.`".TABLE."`;";

	$r = mysql_query($q); 
	$data = '';
	while ($row = mysql_fetch_object($r)){

		list($date,$time)=explode(' ',$row->timestamp);
		list($y,$m,$d)=explode('-',$date);
		list($h,$i,$s)=explode(':',$time);
		$load1=$row->load1;
		$load5=$row->load5;
		$load15=$row->load15;
		$data.="{c:[{v: new Date($y, $m, $d, $h, $i, $s)}, {v: $load1}, {v: $load5}, {v: $load15}, {v: " . NUMBER_OF_CORES . "}]},";

	}
	//generate and show graph

	?>
	<html>
	<head>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">

		google.load('visualization', '1.0', {'packages':['corechart','annotatedtimeline','table']});
		google.setOnLoadCallback(function() {

		    var data = new google.visualization.DataTable({
		     	cols: [
			     	{id: 'date', label: 'Date', type: 'datetime'},
				    {id: 'load1', label: 'Load 1 min avg =', type: 'number'},
					{id: 'load5min', label: 'Load 5 min avg =', type: 'number'},
					{id: 'load15min', label: 'Load 15 min avg =', type: 'number'},
					{id: 'max-usage', label: '100% Usage =', type: 'number'}
				],
		     	rows: [
			    	<?= $data ?>
			   	]
		    }); 
			var options = {
				'title': '15 minutes load xiilo.com',
			    'displayAnnotations': true,
			    'colors': ['black','#999999','#CCCCCC','red'],
			    // 'width': 400,
			    // 'height': 300,
			    // 'fill': 20,
			    // 'thickness': 2,
			};

		  	var annotatedtimeline = new google.visualization.AnnotatedTimeLine(
		      	document.getElementById('visualization')
		    );
		  	annotatedtimeline.draw(data, options);

		});

	</script>
	<style>
		div {
			width:100%; 
			max-width: 1100px;
			margin: 0 auto 30px;
		}
	</style>
	</head>
	<body>
		<div id="visualization" style=" height:500px;"></div>
		<div id="explanation">
			This server has <?= NUMBER_OF_CORES ?> cores, as such a load of <?= NUMBER_OF_CORES ?> represents 100% CPU usage.
		</div>
	</body>
	</html>
<?php
}
