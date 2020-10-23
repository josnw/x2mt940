<?php

 include_once './intern/autoload.php';
 
 if ( file_exists('./intern/config.json') ) {
	include './intern/auth.php';
	
	if ( $_SESSION['level'] < 9 ) {

		$logtext = "Wrong auth level! You must be Administrator for Setup!\n";
		include("./intern/views/setup_log_view.php");
		
	}
	
 }


 
 include("./intern/views/setup_view.php");

 if (isset($_POST["setup"]) or (isset($argv) and in_array("/setup", $argv))) {
	 
	$logtext = "Starting Setup ... <br>\n";
	include("./intern/views/setup_log_view.php");
	$wwsServerHost = substr(preg_replace("[^0-9A-Za-z\-\.\_]","",$_POST["wwsServerHost"]),0,50);
	$wwsServerPort = substr(preg_replace("[^0-9]","",$_POST["wwsServerPort"]),0,5);
	$wwsDBName = substr(preg_replace("[^0-9A-Za-z\-]","",$_POST["wwsDBName"]),0,50);
	$wwsServer = "pgsql:host=".$wwsServerHost.";port=".$wwsServerPort.";dbname=".$wwsDBName;

	$wwsUser = substr(preg_replace("[^0-9A-Za-z\-\.\_]","",$_POST["wwsUser"]),0,50);
	$wwsUserPass = substr(preg_replace("[\"\'\\]","",$_POST["wwsUserPassword"]),0,50);
	$wwsAdmin = substr(preg_replace("[^0-9A-Za-z\-\.\_]","",$_POST["wwsAdmin"]),0,50);
	$wwsAdminPassword =substr( preg_replace("[\"\'\\]","",$_POST["wwsAdminPassword"]),0,50);

 	try {
		$setup = new sqlInitialize($wwsServer, $wwsAdmin, $wwsAdminPassword);
		
		$logtext = "Admin Login successful ...";
		include("./intern/views/setup_log_view.php");
		
		$sqlparameter = [
			":username" => $wwsUser,
			":passwd" => $wwsUserPass
		];
		
		$setup->executeSQL('adduser.sql',$sqlparameter);

		$parameter = [
			"wwsserver"	=> $wwsServer,
			"wwsuser" => $wwsUser,
			"wwspass" => $wwsUserPass
		];

		$logtext = "Database user created ...";
		include("./intern/views/setup_log_view.php");
		
		$setup->writeConfig($parameter);

		$logtext = "Setup.json write ...";
		include("./intern/views/setup_log_view.php");
		
		
		if ( file_exists('./intern/config.json') ) {
			$configFile = new myFile('./intern/config.json', 'readfull');
			$parameter = $configFile->readJson();
			if  ( isset($parameter["systemInstalled"]) and ($parameter["systemInstalled"] == true) ) {
				$logtext = "Setup successfull finished!";
				include("./intern/views/setup_log_view.php");
			} else {
				$logtext = "Error in config.json Format!";
				throw new Exception( $logtext );
			}
		}

	} catch ( exception $e ) {
		$logtext = "Setup failed ... <br/>";
		$logtext .= $e->getMessage();
		include("./intern/views/setup_log_view.php");
	}
	
	
 }
 
 include("./intern/views/setup_footer_view.php");
?>