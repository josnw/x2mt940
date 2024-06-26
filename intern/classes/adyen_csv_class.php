<?php

class adyenCSV {

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
	private $errorlist = "";
	private $splitChar = ";";
	
	public function __construct($fileName) {

		include './intern/config.php';

		// initialize variables
		$this->mt940param = $adyen;
		$this->data = [];
		$this->amountTotal = 0;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();

		$this->infile = new myfile($fileName);

		if (file_exists("./intern/mapping/".$mapping_prefix."adyen.json")) {
			$mapping = new myfile("./intern/mapping/".$mapping_prefix."adyen.json","readfull");
		} else {
			$mapping = new myfile("./intern/mapping/adyen.json","readfull");
		}
		$this->mapping = $mapping->readJson();

		$this->mt940param['startdate'] = null;
		$this->mt940param['enddate'] = null;
		
		while (($row = $this->infile->readCSV($this->splitChar)) !== FALSE) {
			if ( $row[0] == $this->mapping['TRANSACTION_DATE']) {
				$this->ppHeader = $row;
				break;
			}	
		}
		if (! is_array($this->ppHeader)) {
			$this->splitChar = ",";
			$this->infile = new myfile($fileName);
			while (($row = $this->infile->readCSV($this->splitChar)) !== FALSE) {
				if ( $row[0] == $this->mapping['TRANSACTION_DATE']) {
					$this->ppHeader = $row;
					break;
				}
			}
		}
		
		
	}
	
	public function importData() {
		if (count($this->data) > 0) {
			return true;
		}
		$sumOfDay = 0;
		
		$this->errorlist = "";
		$rowcnt = 0;
		while (($row = $this->infile->readCSV($this->splitChar)) !== FALSE) {
			$rowcnt++;
		 	try {
				$rowdata = array_combine($this->ppHeader,$row);
				
				$rowdata[$this->mapping['TRANSACTION_DATE']] = str_replace(["Mär","Mai", "Okt", "Dez"],["Mar", "May","Oct", "Dec"],$rowdata[$this->mapping['TRANSACTION_DATE']]);
				$rowdata[$this->mapping['PAYOUT_DATE']] = str_replace(["Mär","Mai","Okt","Dez"],["Mar", "May","Oct","Dec"],$rowdata[$this->mapping['PAYOUT_DATE']]);
	
				if ($this->mt940param['enddate'] == null) {
					$this->mt940param['enddate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];
					$payoutdate = $rowdata[$this->mapping['PAYOUT_DATE']];
				}
				if ($this->splitChar == ";") {
					$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
					$rowdata[$this->mapping['TRANSACTION_NETAMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_NETAMOUNT']]);
		
					$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
					$rowdata[$this->mapping['TRANSACTION_NETAMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_NETAMOUNT']]);
				}
	
				if (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] == '--') and ($rowdata[$this->mapping['TRANSACTION_NETAMOUNT']] <> 0)){
					 $rowdata[$this->mapping['TRANSACTION_AMOUNT']] = $rowdata[$this->mapping['TRANSACTION_NETAMOUNT']];
				}
				
	
				if (in_array($rowdata[$this->mapping['TRANSACTION_EVENTCODE']], $this->mapping['CHECK_CHARGE_CODE'])) {
					$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
					$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = "";
				} else {
					$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = $rowdata[$this->mapping['TRANSACTION_AMOUNT']] - $rowdata[$this->mapping['TRANSACTION_NETAMOUNT']];
				}
				
				
				if  ( ! in_array($rowdata[$this->mapping['TRANSACTION_EVENTCODE']], $this->mapping['CHECK_EXCLUDECODE']) or 
					  ($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] <> 0 )  
					) {
				
					if ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] > 0) {
						$transactionType = "C";
						$transactionChargeType = "D";
						$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
						$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
					} elseif (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] == '') and ($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] > 0)){
						$transactionChargeType = "D";
						$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
					} else {
						$transactionType = "D";
						$transactionChargeType = "C";
						$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
						$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
	
						$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
						$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
					}
				
					$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_SELLER_ID']]));
	
					$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])-(24*60*60*4));
					$toDate = date("Y-m-d",time());
					
					//if ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] == $this->mapping['CHECK_CANCEL_PAYMENT']) {
					//	$ppid = $rowdata[$this->mapping['TRANSACTION_ORIGINAL_CODE']];
					//} else {
					if ($this->mt940param['extractTid']) {
						$ppidsplit = explode("-",$rowdata[$this->mapping['TRANSACTION_CODE']]);
						$ppid = $ppidsplit[1];
					} else {
						$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];
					}
	
					
					//}
					if(strlen($ppid)>2) {
						$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
					} else {
						$invoiceData = [];
					}
					
					$invoiceStr = '';
					foreach($invoiceData as $invoice) {
						$invoiceStr .= 'RG'.$invoice['invoice']."_"; 
					}
					
					if ($rowdata[$this->mapping['TRANSACTION_INFO']] != '--') {	$invoiceStr .= ' '.$rowdata[$this->mapping['TRANSACTION_INFO']]; }
					if ($rowdata[$this->mapping['TRANSACTION_ORIGINAL_CODE']] != '--') {	$invoiceStr .= ' '.$rowdata[$this->mapping['TRANSACTION_ORIGINAL_CODE']]; }
					isset($invoiceData[0]["invoice"]) ? $defaultInvoice = $invoiceData[0]["invoice"] : $defaultInvoice = 'NONREF';
					isset($invoiceData[0]["customer"]) ? $defaultCustomer = $invoiceData[0]["customer"] : $defaultCustomer = '';	
				
					$mt940 = [];
								
					if ((($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) or ($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] <> 0)) and (
						 ($rowdata[$this->mapping['TRANSACTION_STAT']] == $this->mapping["CHECK_FINISH_STAT"]) or 
						 (($this->mapping["CHECK_FINISH_STAT"] == '') and ($rowdata[$this->mapping['PAYOUT_DATE']] != '--' )) )
						)	{
						
						$mt940 = [
							'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'PAYMENT_TYPE' => $transactionType,
							'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']])),
							'PAYMENT_NDDT' => $defaultInvoice,
							'PAYMENT_TEXT00' => 'ADYEN',
							'PAYMENT_TEXT20' => 'ADYEN KD'.$defaultCustomer,
							'PAYMENT_TEXT21' => $invoiceStr,
						    'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']]." ".strtoupper($name),
						    'PAYMENT_TEXT23' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
							'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'CHARGE_TYPE' => $transactionChargeType,
							'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']])),
							'CHARGE_NDDT' => 'NONREF',
							'CHARGE_TEXT00' => 'ADYEN',
							'CHARGE_TEXT20' => 'ADYEN GEB.',
						    'CHARGE_TEXT21' => strtoupper($name),
						    'CHARGE_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'PAYMENT_STATE' =>  'S'
						];
					} elseif (($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] <> 0) and ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] == '') 
							   and ($rowdata[$this->mapping['TRANSACTION_STAT']] == $this->mapping["CHECK_FINISH_STAT"])) {
						
						$mt940 = [
							'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'PAYMENT_TYPE' => $transactionChargeType,
							'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']])),
							'PAYMENT_NDDT' => 'NONREF',
							'PAYMENT_TEXT00' => 'ADYEN',
							'PAYMENT_TEXT20' => 'ADYEN GEB. ',
							'PAYMENT_TEXT23' => '',
							'PAYMENT_TEXT21' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
							'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
							'CHARGE_DATE' => '',
							'CHARGE_TYPE' => '',
							'CHARGE_AMOUNT' => '',
							'CHARGE_NDDT' => '',
							'CHARGE_TEXT00' => '',
							'CHARGE_TEXT20' => '',
							'CHARGE_TEXT21' => '',
							'CHARGE_TEXT22' => '',
							'PAYMENT_STATE' =>  'S'
						];
					} elseif  ($rowdata[$this->mapping['TRANSACTION_STAT']] <> $this->mapping["CHECK_FINISH_STAT"]) {
						$mt940 = [
							'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'PAYMENT_TYPE' => $transactionType,
							'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']])),
							'PAYMENT_NDDT' => '',
							'PAYMENT_TEXT00' => '',
							'PAYMENT_TEXT20' => '',
							'PAYMENT_TEXT21' => '',
							'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'PAYMENT_TEXT23' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']]." ".strtoupper($name),
							'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
							'PAYMENT_STATE' =>  $rowdata[$this->mapping['TRANSACTION_STAT']],
							'CHARGE_DATE' => '',
							'CHARGE_TYPE' => '',
							'CHARGE_AMOUNT' => '',
							'CHARGE_NDDT' => '',
							'CHARGE_TEXT00' => '',
							'CHARGE_TEXT20' => '',
							'CHARGE_TEXT21' => '',
							'CHARGE_TEXT22' => ''
						];
					}
					
					$this->data[] = $mt940;
			
					$this->dataCount++;
					
					//count sum of day and export
					if (($this->mt940param['payout']) and ($payoutdate == $rowdata[$this->mapping['PAYOUT_DATE']] )) {
						$sumOfDay += $rowdata[$this->mapping['TRANSACTION_NETAMOUNT']];
					} elseif (($this->mt940param['payout']) and (strtotime($payoutdate) > 0) ) {
						 
						$this->createPayoutData($sumOfDay,$payoutdate);
						
						$sumOfDay = $rowdata[$this->mapping['TRANSACTION_NETAMOUNT']];
						$payoutdate = $rowdata[$this->mapping['PAYOUT_DATE']]; 
					} else {
						$sumOfDay = $rowdata[$this->mapping['TRANSACTION_NETAMOUNT']];
						$payoutdate = $rowdata[$this->mapping['PAYOUT_DATE']]; 
					}
	
				}
				$this->mt940param['startdate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];
		 	} catch (TypeError $e) {
		 		$this->errorlist .= "Typfehler ". $e->getMessage()." in Datenzeile ".$rowcnt." der Importdatei<br/>\n";
		 	}
		}
		
		if (($this->mt940param['payout']) and (strtotime($payoutdate) > 0) ) {
			$this->createPayoutData($sumOfDay,$payoutdate);
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
	
	public function getErrors() {
		return $this->errorlist;
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

	private function createPayoutData($sumOfDay,$payoutdate) {
		$mt940 = [
			'PAYMENT_DATE' => date("ymd",strtotime($payoutdate)),
			'PAYMENT_TYPE' => 'D',
			'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$sumOfDay)),
			'PAYMENT_NDDT' => '',
			'PAYMENT_TEXT00' => 'ADYEN',
			'PAYMENT_TEXT20' => 'ADYEN PAYOUT '.$payoutdate,
			'PAYMENT_TEXT21' => '',
			'PAYMENT_TEXT22' => '',
			'PAYMENT_TEXT23' => '',
			'PAYMENT_CODE' => 'PayOut',
			'CHARGE_DATE' => '',
			'CHARGE_TYPE' => '',
			'CHARGE_AMOUNT' => '',
			'CHARGE_NDDT' => '',
			'CHARGE_TEXT00' => '',
			'CHARGE_TEXT20' => '',
			'CHARGE_TEXT21' => '',
			'CHARGE_TEXT22' => '',
			'PAYMENT_STATE' =>  'S'
		];
		$this->data[] = $mt940;
	}
}

?>