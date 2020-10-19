<?php

class sqlInitialize {

	private $pg_pdo;
	
	
	public function __construct($wwsserver, $wwsuser, $wwspass) {

		include './intern/config.php';
		if ( isset($parameter['systemInstalled']) and ($parameter['systemInstalled'] ) ) {
			die("system setup is finished!");
		}
		
		// connect to wws/erp
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
	}
	
	public function executeSQL($sqlfile,$parameter) {
	
		$sqlFile = new myFile('./intern/sql/'.$sqlfile,'readfull');
		
		$sql = $sqlFile->readFull();

		$row_qry = $this->pg_pdo->prepare($sql);

		foreach ($parameter as $value => $key) {
			$row_qry->bindValue($value, $key);
		}
		
		$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
		return true;

	}
	
	public function writeConfig($parameter) {

		$parameter["systemInstalled"] = true;
		$configFile = new myFile('./intern/config.json','new');
		$configFile->writeJson($parameter);
	
	}
}