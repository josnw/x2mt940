<?php

class paypalTTR {

	private $mt940param;
	private	$data;
	private $inFile;
	private $ppHeader;
	private $amountTotal;
	private $wwsInvoices;
	private $dataPos;
	private $dataCount;
	private $ppcodes;
	private $mapping;
	
	public function __construct($fileName) {

		include './intern/config.php';

		// initialize variables
		$this->mt940param = $paypal;
		$this->data = [];
		$this->amountTotal = 0;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();

		$this->infile = new myfile($fileName);
		
		$pp_codes = new myfile("./intern/mapping/paypal_codes.txt","readfull");
		$this->ppcodes = $pp_codes->getContent();
		
		$map = new myfile("./intern/mapping/paypal_trr.json","readfull");
		$this->mapping = $map->readJson();
		
		// initiate inFile and read Header
		while ((($row = $this->infile->readCSV(',')) !== FALSE) and ($row[0] != "CH")) {
			if ($row[0] == "SH") {
				$this->mt940param['startdate'] = strtotime($row[1]);
				$this->mt940param['enddate'] = strtotime($row[2]);
				$this->mt940param['konto'] = $row[3];
			}	
		}
		$this->ppHeader = $row;

	}
	
	public function importData() {
		if (count($this->data) > 0) {
			return true;
		}
		
		while ((($row = $this->infile->readCSV(',')) !== FALSE) and ($row[0] == "SB")) {
			$rowdata = [];
			$rowdata = array_combine($this->ppHeader,$row);

			if ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] <> 'T0400') {
			
				if (substr($rowdata[$this->mapping['TRANSACTION_TYPE']],0,1) == "C") {
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']]/100;
				} else {
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_AMOUNT']]/100;
				}
				if (substr($rowdata[$this->mapping['TRANSACTION_CHARGETYPE']],0,1) == "C") {
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]/100;
				} else {
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]/100;
				}
				$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_SELLER_ID']]));

				$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])-(24*60*60*4));
				$toDate = date("Y-m-d",time());
				$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];
				
				$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				
				$invoiceStr = '';
				foreach($invoiceData as $invoice) {
					$invoiceStr .= 'RG'.$invoice['invoice']; 
				}
			
				$mt940 = [];
				
				if (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) and ($rowdata[$this->mapping['TRANSACTION_STAT']] == "S")) {
					
					$mt940 = [
						'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
						'PAYMENT_TYPE' => substr($rowdata[$this->mapping['TRANSACTION_TYPE']],0,1),
						'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]/100)),
						'PAYMENT_NDDT' => $invoiceData->fnum[0]["invoice"],
						'PAYMENT_TEXT00' => 'PAYPAL',
						'PAYMENT_TEXT20' => 'KD'.$invoiceData->fnum[0]["customer"],
						'PAYMENT_TEXT21' => $invoiceStr,
						'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'PAYMENT_TEXT23' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata[$this->mapping['TRANSACTION_SELLER_NAME']]." ".$rowdata[$this->mapping['TRANSACTION_SELLER_ID']])),
						'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
						'PAYMENT_CODE_TEXT' => $this->ppcodes[$rowdata[$this->mapping['TRANSACTION_EVENTCODE']]],
						'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
						'CHARGE_TYPE' => substr($rowdata[$this->mapping['TRANSACTION_CHARGETYPE']],0,1),
						'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]/100)),
						'CHARGE_NDDT' => 'NONREF',
						'CHARGE_TEXT00' => 'PAYPAL',
						'CHARGE_TEXT20' => 'PAYPAL GEBUEHR',
						'CHARGE_TEXT21' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'CHARGE_TEXT22' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata[$this->mapping['TRANSACTION_SELLER_NAME']]." ".$rowdata[$this->mapping['TRANSACTION_SELLER_ID']])),
						'PAYMENT_STATE' =>  $rowdata[$this->mapping['TRANSACTION_STAT']]
					];
				} elseif  ($rowdata[$this->mapping['TRANSACTION_STAT']] <> "S") {
					$mt940 = [
						'PAYMENT_NDDT' => $invoiceData->fnum[0]["invoice"],
						'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'PAYMENT_TEXT23' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata[$this->mapping['TRANSACTION_SELLER_NAME']]." ".$rowdata[$this->mapping['TRANSACTION_SELLER_ID']])),
						'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
						'PAYMENT_STATE' =>  $rowdata[$this->mapping['TRANSACTION_STAT']]
					];
				}
				
				$this->data[] = $mt940;
				$this->dataCount++;
			}
			
		}
		
		if ($this->amountTotal < 0) {
			$SH = "D";
			$this->amountTotal = str_replace(".",",",sprintf("%01.2f",$this->amountTotal * (-1)));
		} else {
			$SH = "C";
			$this->amountTotal = str_replace(".",",",sprintf("%01.2f",$this->amountTotal));
		}
		 $this->mt940param["TotalAmount"] = $this->amountTotal;
		 $this->mt940param["TotalSH"] = $SH;
	}
	
	public function getAllData() {
		return $this->data;
	}
	
	public function getNext() {
		if ($this->dataPos < $this->dataCount) {
			return $this->data[$this->dataPos++];
		} else {
			return false;
		}
	}
	
	public function getParameter() {
		return $this->mt940param;
	}

	
}

?>