<?php


class myfile {

	private $fileHandle;
	private $mode;
	private $fullText;
	private $checkedPathName;
	private $checkedName;
	private $isFacFile;

	public function __construct($filename, $mode = "read") {
		try {
			if (in_array(substr($filename,-4),['.php','html','.htm'])) {
				$filename .= '.txt';			
			}
			
			$this->isFacFile = false;
			$realpath = realpath( dirname($filename) );
			$subpath = str_replace(getcwd() . DIRECTORY_SEPARATOR , '', $realpath);
			$realname = preg_replace("[^a-zA-Z0-9_\-\.". DIRECTORY_SEPARATOR ."]","_",basename($filename)) ;
			$this->checkedPathName =  getcwd() . DIRECTORY_SEPARATOR . $subpath.  DIRECTORY_SEPARATOR . $realname;
			$this->checkedName = $realname;
			if ($mode == "append") {
				$this->fileHandle = fopen($this->checkedPathName , "a+");
				$this->mode = "append";
			} elseif ($mode == "new") {
				if (file_exists($this->checkedPathName)) {
					$this->findNewName();
				}
				$this->fileHandle = fopen($this->checkedPathName , "a+");
				$this->mode = "append";
			} elseif ($mode == "newUpload") {
				if (file_exists($this->checkedPathName)) {
					$this->findNewName();
				}
				$this->mode = "newUpload";
			} elseif ($mode == "readfull") {
				$this->fileHandle = NULL;
				$this->fullText = file_get_contents($this->checkedPathName);
				$this->mode = "readfull";
			} elseif ($mode == "read") {
				$this->fileHandle = fopen($this->checkedPathName , "r");
				$this->mode = "read";
			} elseif ($mode == "writefull") {
				$this->mode = "writefull";
			} else {
				return false;
			}
		} catch (Exception $e) {
			die("Exception: ".$e->getMessage());
		}
	}
	
	private function findNewName() {
		$info = pathinfo($this->checkedPathName);
		$i = 0;
		
		do {
			$fileNameTest = $info["filename"] . "_" . sprintf("%04d",$i) .".".$info["extension"] ;
			$pathNameTest = $info["dirname"] . DIRECTORY_SEPARATOR . $info["filename"] . "_" . sprintf("%04d",$i++) .".".$info["extension"] ;
		} while (file_exists($pathNameTest));
		
		$this->checkedPathName = $pathNameTest;
		$this->checkedName = basename($fileNameTest) ;
		
	}
	
	public function getCheckedName() {

		return $this->checkedName;
	}

	public function getCheckedPathName() {

		return $this->checkedPathName;
	}
	
	public function write($line) {
		if ($this->mode == "append") {
			fwrite($this->fileHandle, $line);
		} else {
			return false;
		}
	}
	
	public function writeLn($line) {
		if ($this->mode == "append") {
			fwrite($this->fileHandle, $line. "\n");
		} else {
			return false;
		}
	}
	
	public function writeCSV($data, $seperator = ";", $textsep = '"') {
		if ($this->mode == "append") {
			fputcsv($this->fileHandle, $data, $seperator, $textsep);
		} else {
			return false;
		}
	}

	public function writeJson($array) {
		if ($this->mode == "append") {
			fwrite($this->fileHandle, json_encode($array));
		} else {
			return false;
		}
	}
	
	public function readLn() {
		if ($this->mode == "read") {
			return fgets($this->fileHandle, 4048);
		} else {
			return false;
		}
	}

	public function readCSV($sep = ';') {
		if ($this->fileHandle == NULL) {
			return false;
		}
		if ($this->mode == "read") {
			return fgetcsv($this->fileHandle, 4048, $sep);
		} else {
			return false;
		}
	}
	
	public function getContent() {
		if ($this->mode == "readfull") {
			return $this->fullText;
		} else {
			return false;
		}
	}
	
	public function putContent( $data ) {
		if ($this->mode == "writefull") {
			file_put_contents($this->checkedPathName, $data);
		} else {
			return false;
		}
	}

	public function readJson() {
		if ($this->mode == "readfull") {
			return json_decode($this->fullText,true);
		} else {
			return false;
		}
	}

	
	public function fileSize() {
		return filesize($this->checkedName);
	}

	public function type() {
		return mime_content_type($this->checkedName);
	}

	public function close() {
		if ($this->isFacFile) {
			$this->facFoot();
		}
		
		fclose($this->fileHandle);
		$this->fileHandle = NULL;
		$this->mode = NULL;
		$this->fullText = NULL;
		$this->checkedName = NULL;
	}
	
	public function facHead($table, $fromFil = 0, $typ = "N ") {
	
		include './intern/config.php';
		
		if ($this->isFacFile) {
			$this->facfoot();
		}
		
		$this->writeLn('<<<:5'.$typ.';E000998F000000D'.date("dmyZHi").'P_ScanDesk0V5.3');
		$this->writeLn('DBN:2');
		$this->writeLn('INF:'.sprintf("%03d",$fromFil));
		$this->writeLn('TAB:'.$table);
		$this->writeLn('008:'.sprintf("%03d",$fromFil));
		$this->writeLn('FIL:'.sprintf("%03d",$FacFiliale));
		
		$this->isFacFile = true;
	}

	public function facData($data) {
		foreach($data as $key=>$value) {
			if (is_array($value)) {
				$cnt = 0;
				foreach($value as $subkey=>$subvalue) {
					$subvalue = preg_replace("/[\n\r]/","",$subvalue);
					if ($cnt++ == 0) {
						$this->writeLn($key.":".$subvalue);
					} else {
						$this->writeLn($key."+".$subvalue);
					}
				}
			} else {
				$value = preg_replace("/[\n\r]/","",$value);
				$this->writeLn($key.":".$value);
			}
		}
	}

	public function facfoot() {
	
		$this->writeLn('>>>');
		$this->isFacFile = false;
	}

	public function moveUploaded($uploadFile) {
		move_uploaded_file($uploadFile,$this->checkedPathName);	
	}
}
