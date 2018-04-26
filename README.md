# PHP-Server-Load-Monitor

Simple self-contained PHP script to record server load and display results in a chart.

![screenshot.png?raw=true](Screenshot)

This script is used for tracking the server load figures. Only works on Linux servers.

It is run in 2 ways:

  1. As a cron task that records the server load figures every X mins

  2. When run in browser is displays the results in a chart
  

# Installation

  1. Create a new database on remote web host

  2. Create a new database user

  3. Add both of these to the configuration section inside `server-load.php`

  4. Create an empty database table with the script below:
  
         CREATE TABLE `server_load_monitor` (
    		 `id` int(11) NOT NULL AUTO_INCREMENT,
			 `timestamp` datetime NOT NULL,
			 `load1` float(6,2) NOT NULL,
			 `load5` float(6,2) NOT NULL,
			 `load15` float(6,2) NOT NULL,
			 PRIMARY KEY (`id`)
	     ) ENGINE=InnoDB
  
  5. Upload file to remote web space

  6. Run the following URL (twice) in browser to begin to store some data (you can check to ensure it's being saved in DB correctly)
  
  		 http://_________/server-load/server-load.php?savetodb=1
  
  7. Load the following URL in browser to display the chart and confirm chart is working
  
  		 http://_________/server-load/server-load.php
  		
  8. Setup cron job running every 5 mins (or however long you prefer) that runs this command to record load figures:
  
  		 curl --silent --show-error http://_________/server-load/server-load.php?savetodb=1

# Licence

Open source. Free BSD licence.