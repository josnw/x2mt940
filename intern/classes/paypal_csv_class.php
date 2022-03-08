<?php

class paypalCSV {

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

		if (file_exists("./intern/mapping/".$mapping_prefix."paypal_csv.json")) {
			$mapping = new myfile("./intern/mapping/".$mapping_prefix."paypal_csv.json","readfull");
		} else {
			$mapping = new myfile("./intern/mapping/paypal_csv.json","readfull");
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
		
		while (($row = $this->infile->readCSV(',')) !== FALSE) {
			$rowdata = [];
			$rowdata = array_combine($this->ppHeader,$row);

			if ($this->mt940param['startdate'] == null) {
				$this->mt940param['startdate']	= $rowdata[$this->mapping['TRANSACTION_DATE']];
			}
			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);

			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);

			if  ( ! in_array($rowdata[$this->mapping['TRANSACTION_EVENTCODE']], $this->mapping['CHECK_EXCLUDECODE']) or 
				  ($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] <> 0 )  
				) {
			
				$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
				$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);

				if ($rowdata[$this->mapping['TRANSACTION_TYPE']] == $this->mapping['CHECK_CR_TYPE']) {
					$transactionType = "D";
					$transactionChargeType = "C";
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
				} else {
					$transactionType = "C";
					$transactionChargeType = "D";
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']];
				}
				
				$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_SELLER_ID']]));

				$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])-(24*60*60*4));
				$toDate = date("Y-m-d",time());
				
				if ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] == $this->mapping['CHECK_CANCEL_PAYMENT']) {
					$ppid = $rowdata[$this->mapping['TRANSACTION_ORIGINAL_CODE']];
				} else {
					$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];
				}
				
				$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				if (count($invoiceData) == 0) {
				    $invoiceData = $this->wwsInvoices->getInvoiceData($rowdata[$this->mapping['TRANSACTION_INVOICE']], $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				}
				$invoiceStr = '';
				foreach($invoiceData as $invoice) {
					$invoiceStr .= 'RG'.$invoice['invoice']." "; 
				}

				isset($invoiceData[0]["invoice"]) ? $defaultInvoice = $invoiceData[0]["invoice"] : $defaultInvoice = 'NONREF';
				isset($invoiceData[0]["customer"]) ? $defaultCustomer = $invoiceData[0]["customer"] : $defaultCustomer = '';	
			
				$mt940 = [];
				
				if (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) and ($rowdata[$this->mapping['TRANSACTION_STAT']] == $this->mapping["CHECK_FINISH_STAT"])) {
					
					$mt940 = [
						'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
						'PAYMENT_TYPE' => $transactionType,
						'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']])),
						'PAYMENT_NDDT' => $defaultInvoice,
						'PAYMENT_TEXT00' => 'PAYPAL',
						'PAYMENT_TEXT20' => 'PAYPAL KD'.$defaultCustomer,
						'PAYMENT_TEXT21' => $invoiceStr,
						'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'PAYMENT_TEXT23' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']]." ".strtoupper($name),
						'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
						'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
						'CHARGE_TYPE' => $transactionChargeType,
						'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']])),
						'CHARGE_NDDT' => 'NONREF',
						'CHARGE_TEXT00' => 'PAYPAL',
						'CHARGE_TEXT20' => 'PAYPAL GEB.',
						'CHARGE_TEXT21' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'CHARGE_TEXT22' => strtoupper($name),
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
			}

			$this->mt940param['enddate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];

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