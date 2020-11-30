<?php

class mt940 {

	private $posparam;
	private	$dataset;
	private $inFile;
	private $ppHeader;
	private $amountTotal;
	private $wwsInvoices;
	private $dataPos;
	private $dataCount;
	
	
	public function __construct() {

		include './intern/config.php';

		// initialize variables
		$this->dataset = null;
		
	}
	
	private function mt940Header($parameter) {
			$header = ":20:PP".date("ymdHis")."\n";
			$header .= ":25:".$parameter['blz']."/".$parameter['konto']."\n";
			$header .= ":28C:0\n";
			$header .= ":60F:C".date("ymd",strtotime($parameter['startdate'])).$parameter['currency']."0,00"."\n";
			
			return $header;
	}
	
	private function mt940Pos($data) {
	
		
		if ($data['PAYMENT_STATE']	== "S") {
			$pos = ":61:".date("ymd",strtotime($data['PAYMENT_DATE']));
			$pos .= $data['PAYMENT_TYPE'];
			if (substr($data['PAYMENT_AMOUNT'],0,1) == ",") {
				$data['PAYMENT_AMOUNT'] = "0".$data['PAYMENT_AMOUNT'];
			}
			$pos .= $data['PAYMENT_AMOUNT'];
			$pos .= "NDDT".$data['PAYMENT_NDDT']."\n";
			$pos .= ":86:166?00".$data['PAYMENT_TEXT00']."\n";
			if (strlen($data['PAYMENT_TEXT20']) > 0) {
				$pos .= "?20".$data['PAYMENT_TEXT20']."\n";
			}
			if (strlen($data['PAYMENT_TEXT21']) > 0) {
				$pos .= "?21".$data['PAYMENT_TEXT21']."\n";
			}
			if (strlen($data['PAYMENT_TEXT22']) > 0) {
				$pos .= "?22".$data['PAYMENT_TEXT22']."\n";
			}
			if (strlen($data['PAYMENT_TEXT23']) > 0) {
				$pos .= "?23".$data['PAYMENT_TEXT23']."\n";
			}

			if ((isset($data['CHARGE_AMOUNT'])) and ( str_replace(",",".",$data['CHARGE_AMOUNT']) <> 0 ) ) {
				$pos .= ":61:".date("ymd",strtotime($data['CHARGE_DATE']));
				$pos .= $data['CHARGE_TYPE'];
				if (substr($data['CHARGE_AMOUNT'],0,1) == ",") {
					$data['CHARGE_AMOUNT'] = "0".$data['CHARGE_AMOUNT'];
				}
				$pos .= $data['CHARGE_AMOUNT'];
				$pos .= "NDDT".$data['CHARGE_NDDT']."\n";
				$pos .= ":86:166?00".$data['CHARGE_TEXT00']."\n";
				if (strlen($data['CHARGE_TEXT20']) > 0) {
					$pos .= "?20".$data['CHARGE_TEXT20']."\n";
				}
				if (strlen($data['CHARGE_TEXT21']) > 0) {
					$pos .= "?21".$data['CHARGE_TEXT21']."\n";
				}
				if (strlen($data['CHARGE_TEXT22']) > 0) {
					$pos .= "?22".$data['CHARGE_TEXT22']."\n";
				}
			}
			
			if (isset($data['DISCOUNT']) and (is_array($data['DISCOUNT'])) ) {
			
				foreach ($data['DISCOUNT'] as $discount) {
					
					$pos .= ":61:".date("ymd",strtotime($discount['DISCOUNT_DATE']));
					$pos .= $discount['DISCOUNT_TYPE'];
					if (substr($data['DISCOUNT_AMOUNT'],0,1) == ",") {
						$data['DISCOUNT_AMOUNT'] = "0".$data['DISCOUNT_AMOUNT'];
					}
					$pos .= $discount['DISCOUNT_AMOUNT'];
					$pos .= "NDDT".$discount['DISCOUNT_NDDT']."\n";
					$pos .= ":86:166?00".$discount['DISCOUNT_TEXT00']."\n";
					if (strlen($discount['DISCOUNT_TEXT20']) > 0) {
						$pos .= "?20".$discount['DISCOUNT_TEXT20']."\n";
					}
					if (strlen($discount['DISCOUNT_TEXT21']) > 0) {
						$pos .= "?21".$discount['DISCOUNT_TEXT21']."\n";
					}
					if (strlen($discount['DISCOUNT_TEXT22']) > 0) {
						$pos .= "?22".$discount['DISCOUNT_TEXT22']."\n";
					}
				}
			}
			
			return $pos;
			
		} else {
			
			return false;
			
		}
	}
	
	private function mt940footer($parameter) {
			$footer = ":62F:".$parameter["TotalSH"].date("ymd",strtotime($parameter["enddate"])).$parameter["currency"].$parameter["TotalAmount"]."\n";
			$footer .= "-\n";
			
			return $footer;
	}
	
	public function generateMT940($data, $parameter) {
		$this->dataset = $this->mt940Header($parameter);
		
		foreach($data as $line) {
			$this->dataset .= $this->mt940Pos($line); 	
		}

		$this->dataset .= $this->mt940Footer($parameter);
		
	}
		
	public function writeToFile($fileName) {
		if (strlen($this->dataset) > 0) {
			$this->outfile = new myfile($fileName, 'writefull');
			$this->outfile->putContent($this->dataset);
			return $this->outfile->getCheckedName();
		} else {
			return false;
		}
	}

	public function getMT940() {
		if (strlen($this->dataset) > 0) {
			return $this->dataset;
		} else {
			return false;
		}
	}
	
	public function getDataCount() {
		return $this->dataCount;
	}

	public function getAmountTotal() {
		return $this->amountTotal;
	}
	
}



?>

