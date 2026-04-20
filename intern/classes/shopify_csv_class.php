<?php

class shopifyCSV {
	
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
		$this->mt940param = $shopify;
		$this->data = [];
		$this->amountTotal = 0;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();
		
		$this->infile = new myfile($fileName);
		
		if (file_exists("./intern/mapping/".$mapping_prefix."shopifycsv.json")) {
			$mapping = new myfile("./intern/mapping/".$mapping_prefix."shopifycsv.json","readfull");
		} else {
			$mapping = new myfile("./intern/mapping/shopifycsv.json","readfull");
		}
		$this->mapping = $mapping->readJson();
		
		$this->mt940param['startdate'] = null;
		$this->mt940param['enddate'] = null;
		$row = $this->infile->readCSV(',');
		$row[0] = trim($row[0],"\"\xEF\xBB\xBF");
		$this->ppHeader = $row;
		
	}
	
	public function importData() {
		if (count($this->data) > 0) {
			return true;
		}
		$sumOfDay = 0;
		
		while (($row = $this->infile->readCSV(',')) !== FALSE) {
			$rowdata = [];
			$rowdata = array_combine($this->ppHeader,$row);
			if ($this->mt940param['startdate'] == null) {
				$this->mt940param['startdate']	= $rowdata[$this->mapping['TRANSACTION_DATE']];
			}
			/*
			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
			
			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
			*/
			
			if (! in_array($rowdata[$this->mapping['TRANSACTION_STATE']], $this->mapping['CHECK_EXCLUDECODE'])) {
				
				if ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] > 0) {
					$transactionType = "C";
					$transactionChargeType = "D";
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
				} else {
					$transactionType = "D";
					$transactionChargeType = "C";
					$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
					$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
					
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
				}
				
				//$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_SELLER_NAME']]));
				
				$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])-(24*60*60*4));
				$toDate = date("Y-m-d",time());
				
				/*
					 if ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] == $this->mapping['CHECK_CANCEL_PAYMENT']) {
						$ppid = $rowdata[$this->mapping['TRANSACTION_ORIGINAL_CODE']];
					} else {
						$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];
					}
				 */
				$ppid = $rowdata[$this->mapping['ORDER_ID']];
				
				$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				
				$invoiceStr = '';
				foreach($invoiceData as $invoice) {
					$invoiceStr .= 'RG'.$invoice['invoice']." ";
				}
				isset($invoiceData[0]["invoice"]) ? $defaultInvoice = $invoiceData[0]["invoice"] : $defaultInvoice = 'NONREF';
				isset($invoiceData[0]["customer"]) ? $defaultCustomer = $invoiceData[0]["customer"] : $defaultCustomer = '';
				
				$spacePos = null; 
				//$spacePos = strpos($rowdata[$this->mapping['TRANSACTION_EVENTCODE']]," ",10);
				if (! $spacePos) { $spacePos = 30; }
				$event = substr($rowdata[$this->mapping['TRANSACTION_EVENTCODE']],0,$spacePos);
				
				
				
				$mt940 = [];
				
				//				if (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) and ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] == $this->mapping["CHECK_FINISH_STAT"])) {
				if ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) {
					
					$mt940 = [
							'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'PAYMENT_TYPE' => $transactionType,
							'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']])),
							'PAYMENT_NDDT' => $defaultInvoice,
							'PAYMENT_TEXT00' => 'SHOPIFY',
							'PAYMENT_TEXT20' => 'SHOPIFY KD'.$defaultCustomer,
							'PAYMENT_TEXT21' => $invoiceStr,
							'PAYMENT_TEXT22' => $rowdata[$this->mapping['ORDER_ID']],
							'PAYMENT_TEXT23' => $rowdata[$this->mapping['TRANSACTION_ID']],
							'PAYMENT_CODE' => $event,
							'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'CHARGE_TYPE' => $transactionChargeType,
							'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']])),
							'CHARGE_NDDT' => 'NONREF',
							'CHARGE_TEXT00' => 'SHOPIFY',
							'CHARGE_TEXT20' => 'SHOPIFY GEB.',
							'CHARGE_TEXT21' => $rowdata[$this->mapping['ORDER_ID']],
							'CHARGE_TEXT22' => $rowdata[$this->mapping['TRANSACTION_ID']],
							'PAYMENT_STATE' =>  'S'
					];
				}
				
				$this->data[] = $mt940;
				
				$this->dataCount++;
				
				//count sum of day and export
				if ($this->mt940param['payout'])  {
					$sumOfDay += $rowdata[$this->mapping['TRANSACTION_AMOUNT_NET']];
					$payoutdate = $rowdata[$this->mapping['TRANSACTION_DATE']];
					
				}
			}
			
			$this->mt940param['enddate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];
		}
		
		if (($this->mt940param['payout']) and (strtotime($payoutdate) > 0) ) {
			$this->createPayoutData($sumOfDay,$payoutdate);
		} else {
			error_log(var_dump($this->mt940param['payout']). "| " .strtotime($payoutdate)) ; 
		}
		
		if ($this->amountTotal < 0) {
			$SH = "D";
			$this->amountTotal = $this->amountTotal * (-1);
		} else {
			$SH = "C";
			$this->amountTotal = $this->amountTotal;
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
	
	private function createPayoutData($sumOfDay,$payoutdate) {
		$mt940 = [
				'PAYMENT_DATE' => date("ymd",strtotime($payoutdate)),
				'PAYMENT_TYPE' => 'D',
				'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$sumOfDay)),
				'PAYMENT_NDDT' => '',
				'PAYMENT_TEXT00' => 'SHOPIFY',
				'PAYMENT_TEXT20' => 'SHOPIFY PAYOUT '.$payoutdate,
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