<?php
/**
 * This script is used for tracking the server load figures. Only works on Linux servers.
 * 
 * It is run in 2 ways:
 * 
 *   1. As a cron task that records the server load figures every X mins
 *   2. When run in browser is displays the results in a chart
 *   
 * To install:
 * 
 *   1. Create a new database on remote web host
 *   2. Create a new database user
 *   3. Add both of these to the configuration section below
 *   4. Create an empty database table with the script below:
 *   
 *   		CREATE TABLE `server_load_monitor` (
 *     		 `id` int(11) NOT NULL AUTO_INCREMENT,
 * 			 `timestamp` datetime NOT NULL,
 * 			 `load1` float(6,2) NOT NULL,
 * 			 `load5` float(6,2) NOT NULL,
 * 			 `load15` float(6,2) NOT NULL,
 * 			 PRIMARY KEY (`id`)
 * 			) ENGINE=InnoDB
 *   
 *   5. Upload file to remote web space
 *   6. Run the following URL (twice) in browser to begin to store some data (you can check to ensure it's being saved in DB correctly)
 *   
 *   		http://_________/server-load/server-load.php?savetodb=1
 *   
 *   7. Load the following URL in browser to confirm chart is working
 *   
 *   		http://_________/server-load/server-load.php
 *   		
 *   8. Setup cron job running every 5 mins that runs this:
 *   
 *   		curl --silent --show-error http://_________/server-load/server-load.php?savetodb=1
 *   
 */

// error_reporting(E_ALL);
// ini_set('display_errors', true);

//------- Configuration -------
$dbConnection = new PDO('mysql:host=localhost;dbname=xxxxxxxxxxxx;charset=utf8mb4', 'xxxxxxxxxxxx', 'xxxxxxxxxxxx');
$TABLE = 'server_load_monitor';
$NUMBER_OF_CORES = 16;

//------- Logic -------
$dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// If called with 'savetodb' parameter, get the load, save it to database, then exit
if((isset($_GET['savetodb']) && $_GET['savetodb']) || @$argv[1] == 'savetodb') {

	$loads = sys_getloadavg();

	print_r($loads);

	// TODO: Convert this to proper prepared statement
	$dbConnection->exec("INSERT INTO `$TABLE` (`timestamp`, `load1`, `load5`, `load15`) 
		VALUES ( CURRENT_TIMESTAMP, '".$loads[0]."', '".$loads[1]."', '".$loads[2]."');");

// Otherwise display the graph of data
} else {

	$data = $dbConnection->query("SELECT * FROM `$TABLE`;"); 
	$chartData = '';
	foreach ($data as $row) {

		list($date, $time) = explode(' ', $row['timestamp']);
		list($y, $m, $d) = explode('-', $date);
		list($h, $i, $s) = explode(':', $time);
		$m -= 1; // Months seem to be zero based for some reason?
		$load1 = $row['load1'];
		$load5 = $row['load5'];
		$load15 = $row['load15'];
		$chartData .= "{c:[{v: new Date($y, $m, $d, $h, $i, $s)}, {v: $load1}, {v: $load5}, {v: $load15}, {v: $NUMBER_OF_CORES }]},";

	}
	// Generate and show graph
	// echo 'x';

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
			    	<?= $chartData ?>
			   	]
		    }); 
			var options = {
				'title': '15 minutes load xiilo.com',
			    'displayAnnotations': true,
			    'colors': ['black','#999999','#CCCCCC','red'],
				'max': <?= $NUMBER_OF_CORES * 2.2 ?>
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
			This server has <?= $NUMBER_OF_CORES ?> cores, as such a load of <?= $NUMBER_OF_CORES ?> represents 100% CPU usage.
		</div>
	</body>
	</html>
<?php
}



/**
 * Copyright © 2011 Erin Millard
 */
/**
 * Returns the number of available CPU cores
 * 
 * Should work for Linux, Windows, Mac & BSD
 * 
 * @return integer 
 */
function num_cpus()
{
  $cmd = "uname";
  $OS = strtolower(trim(shell_exec($cmd)));

  switch($OS) {
     case('linux'):
        $cmd = "cat /proc/cpuinfo | grep processor | wc -l";
        break;
     case('freebsd'):
        $cmd = "sysctl -a | grep 'hw.ncpu' | cut -d ':' -f2";
        break;
     default:
        unset($cmd);
  }

  if ($cmd != '') {
     $cpuCoreNo = intval(trim(shell_exec($cmd)));
  }
  
  return empty($cpuCoreNo) ? 1 : $cpuCoreNo;
}
