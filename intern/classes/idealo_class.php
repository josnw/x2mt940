<?php

class idealoPayment {

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
		$this->mt940param = $idealo;
		$this->data = [];
		$this->amountTotal = 0.00;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();

		$this->infile = new myfile($fileName);

		if (file_exists("./intern/mapping/".$mapping_prefix."idealo.json")) {
			$mapping = new myfile("./intern/mapping/".$mapping_prefix."idealo.json","readfull");
		} else {
			$mapping = new myfile("./intern/mapping/idealo.json","readfull");
		}
		$this->mapping = $mapping->readJson();

		$this->mt940param['startdate'] = null;
		$this->mt940param['enddate'] = null;
		$row = $this->infile->readCSV(';');
		$row[0] = trim($row[0],"\"\xEF\xBB\xBF");
		$this->ppHeader = $row;
		
	}
	
	public function importData() {
		if (count($this->data) > 0) {
			return true;
		}
		
		while (($row = $this->infile->readCSV(';')) !== FALSE) {
			$rowdata = [];
			$rowdata = array_combine($this->ppHeader,$row);

			if ($this->mt940param['startdate'] == null) {
				$this->mt940param['startdate']	= $rowdata[$this->mapping['TRANSACTION_DATE']];
			}
			//$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			//$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);

			if (! in_array($rowdata[$this->mapping['TRANSACTION_EVENTCODE']], $this->mapping['CHECK_EXCLUDECODE'])) {
			
				if ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] > 0) {
					$transactionType = "C";
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
				} else {
					$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = abs($rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
					$transactionType = "D";
					$this->amountTotal -= $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
				}
				
				$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_INVOICE_ID']]));

				$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])-(24*60*60*4));
				$toDate = date("Y-m-d",time());
				
				$ppid = $rowdata[$this->mapping['TRANSACTION_INVOICE_ID']];
				$invoiceStr = '';
				if (strlen($ppid) > 0) {
					$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
					
					if (count($invoiceData) == 0) {
						$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];
						$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
					}

					foreach($invoiceData as $invoice) {
						$invoiceStr .= 'RG'.$invoice['invoice']." "; 
					}
				} 
				isset($invoiceData[0]["invoice"]) ? $defaultInvoice = $invoiceData[0]["invoice"] : $defaultInvoice = 'NONREF';
				isset($invoiceData[0]["customer"]) ? $defaultCustomer = $invoiceData[0]["customer"] : $defaultCustomer = '';	
			
				$mt940 = [];
				
				if ($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) {
					
					$mt940 = [
						'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
						'PAYMENT_TYPE' => $transactionType,
						'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata[$this->mapping['TRANSACTION_AMOUNT']])),
						'PAYMENT_NDDT' => $defaultInvoice,
						'PAYMENT_TEXT00' => 'IDEALO PAYMENT',
						'PAYMENT_TEXT20' => 'IDEALO PAYMENT KD'.$defaultCustomer,
						'PAYMENT_TEXT21' => $invoiceStr,
						'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
						'PAYMENT_TEXT23' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']]." ".strtoupper($name),
						'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
						'CHARGE_DATE' => '',
						'CHARGE_TYPE' => '',
						'CHARGE_AMOUNT' => '',
						'CHARGE_NDDT' => '',
						'CHARGE_TEXT00' => '',
						'CHARGE_TEXT20' => '',
						'CHARGE_TEXT21' => '',
						'CHARGE_TEXT22' => '',
						'PAYMENT_STATE' => 'S'
					];
				} 
				
				$this->data[] = $mt940;
				
				$this->dataCount++;
			}

			$this->mt940param['enddate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];

		}
		
		if ($this->amountTotal < 0) {
			$SH = "D";
			$payoutType = "C";
			$this->amountTotal = $this->amountTotal * (-1);
		} else {
			$SH = "C";
			$payoutType = "D";
			$this->amountTotal = $this->amountTotal;
		}
		
		$this->data[] = [
				'PAYMENT_DATE' => date("ymd",strtotime($this->mt940param['enddate'])),
				'PAYMENT_TYPE' => $payoutType,
				'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$this->amountTotal)),
				'PAYMENT_NDDT' => 'NONREF',
				'PAYMENT_TEXT00' => 'IDEALO PAYMENT',
				'PAYMENT_TEXT20' => 'IDEALO PAYMENT PAYOUT',
				'PAYMENT_TEXT21' => 'Auszahlung',
				'PAYMENT_TEXT22' => '',
				'PAYMENT_TEXT23' => '',
				'PAYMENT_CODE' => 'PAYOUT',
				'CHARGE_DATE' => '',
				'CHARGE_TYPE' => '',
				'CHARGE_AMOUNT' => '',
				'CHARGE_NDDT' => '',
				'CHARGE_TEXT00' => '',
				'CHARGE_TEXT20' => '',
				'CHARGE_TEXT21' => '',
				'CHARGE_TEXT22' => '',
				'PAYMENT_STATE' => 'S'
		];		
		$this->dataCount++;
		
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