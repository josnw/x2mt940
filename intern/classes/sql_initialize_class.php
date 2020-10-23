<?php

class sqlInitialize {

	private $pg_pdo;
	
	
	public function __construct($wwsInstallServer, $wwsAdminUser, $wwsAdminPass, $options = null) {
		
		// connect to wws/erp
		if (! $this->pg_pdo = new PDO($wwsInstallServer, $wwsAdminUser, $wwsAdminPass, $options) ) {
			throw new Exception($row_qry->errorInfo()[2]);
		}

		return [ 'status' => true ];

	}
	
	public function executeSQL($sqlfile,$parameter) {
	
		$sqlFile = new myFile('./intern/sql/'.$sqlfile,'readfull');
		
		$sql = $sqlFile->getContent();


		foreach ($parameter as $value => $key) {
			// $row_qry->bindValue($value, $key);
			$sql = str_replace($value,$this->pg_pdo->quote($key),$sql);
		}

		$statements = explode(";",$sql);
		
		foreach ($statements as $statement) {
			if (strlen(trim($statement)) > 0) {
				$row_qry = $this->pg_pdo->prepare($statement);
				if (!$row_qry->execute()) {
					throw new Exception($row_qry->errorInfo()[2]);
				}
			}
		}
		
		return true;
	}
	
	
	public function writeConfig($parameter) {

		try {
			$parameter["systemInstalled"] = true;
			$configFile = new myFile('./intern/config.json','writefull');
			$configFile->putContent(json_encode($parameter));
			return [ 'status' => true ];
		} catch ( exception $e ) {
			$msg = "Error writing config file!".$e->getMessage();
			throw new Exception( $msg );
		}
	
	}
}