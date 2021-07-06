<?php

class eurobaustoffAvis {

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
		$this->mt940param = $eurobaustoff;
		$this->data = [];
		$this->amountTotal = 0;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();

		$this->infile = new myfile($fileName);

		$row = $this->infile->readLn();
		// $row = trim($row[0],"\"\xEF\xBB\xBF");

		if (substr($row,0,2) == "10") {
			$this->mt940param['startdate'] = substr($row,14,4)."-".substr($row,12,2)."-".substr($row,10,2);
			$this->mt940param['enddate'] = substr($row,14,4)."-".substr($row,12,2)."-".substr($row,10,2);
			$this->mt940param['konto'] =  substr($row,31,$this->mt940param['AccountMaxLength']);

		}	

		
	}
	
	public function importData( $transactionDate = NULL) {
		if (count($this->data) > 0) {
			return true;
		}
		
		while ( (($row = $this->infile->readLn()) !== FALSE) and (substr($row,0,2) == "50") ){
			$rowdata = [];
			
			if ( $transactionDate == NULL ) {
				$rowdata["TRANSACTION_DATE"] = preg_replace ( '/[^0-9\-]/i', '',substr($row,14,4)."-".substr($row,12,2)."-".substr($row,10,2));
			} else {
				$rowdata["TRANSACTION_DATE"] = date("ymd",strtotime($transactionDate));
			}

			$rowdata["INVOICE_DATE"] = preg_replace ( '/[^0-9]/i', '',substr($row,53,8)); 
			$rowdata["TRANSACTION_INVOICE"] =  preg_replace ( '/[^0-9]/i', '',substr($row,42,9));
			$rowdata["TRANSACTION_AMOUNT"] =  ltrim(substr($row,96,12),"0");
			$rowdata["TRANSACTION_PREFIX"] =  substr($row,108,1);
			$rowdata["TRANSACTION_DISCOUNTAMOUNT"] =  ltrim(substr($row,109,12),"0");
			$rowdata["TRANSACTION_NETAMOUNT"] =  ltrim(substr($row,122,12),"0");
			$rowdata["TRANSACTION_SELLER_ID"] =  preg_replace ( '/[^0-9]/i', '',substr($row,153,5));

			$grossValue = str_replace(",",".",$rowdata["TRANSACTION_AMOUNT"]);
			$discountValue = str_replace(",",".",$rowdata["TRANSACTION_DISCOUNTAMOUNT"]);
			
			if ($rowdata["TRANSACTION_PREFIX"] == "-") {
				$rowdata["TRANSACTION_TYPE"] = 'C';
				$rowdata["TRANSACTION_DISCOUNTTYPE"] = 'D';
				$this->amountTotal -= $grossValue;
			} else {
				$rowdata["TRANSACTION_TYPE"] = 'D';
				$rowdata["TRANSACTION_DISCOUNTTYPE"] = 'C';
				$this->amountTotal += $grossValue;
			}
			
			if ( $this->mt940param['alternateSeller'] ) {
				$SellerId = $this->wwsInvoices->getSeller($rowdata["TRANSACTION_SELLER_ID"]);
				$rowdata["TRANSACTION_SELLER_ID"] = $SellerId[0]["seller"];
			} 
		
			
			if ( $this->mt940param['grossPosting'] ) {
				
				//discount as single posting
				
				$invoiceTaxData = $this->wwsInvoices->getTaxInvoiceData($rowdata["TRANSACTION_INVOICE"]);

				$discount = [];
				if (count($invoiceTaxData) > 0) {
					foreach($invoiceTaxData as $tax) {
 
						$discount[$tax["percent"]] = round(($tax["grosspart"] * (1+($tax["percent"]/100)) / $grossValue) * $discountValue ,2); 
					}
					
					//diff correction
					if (array_sum($discount) <> $discountValue) {
						$discount[array_key_first($discount)] += array_sum($discount) - $discountValue;
					}		
				} else {
					$discount[0] = $discountValue; 
				}
					
			} else {
				
				$discount = [];
				$rowdata["TRANSACTION_AMOUNT"] = $rowdata["TRANSACTION_NETAMOUNT"];
			
			}
	
			$mt940 = [
				'PAYMENT_DATE' => $rowdata["TRANSACTION_DATE"],
				'PAYMENT_TYPE' => $rowdata["TRANSACTION_TYPE"],
				'PAYMENT_AMOUNT' => str_replace(".",",",$rowdata["TRANSACTION_AMOUNT"]),
				'PAYMENT_NDDT' => $rowdata["TRANSACTION_INVOICE"],
				'PAYMENT_TEXT00' => 'EBAVIS '. $rowdata["TRANSACTION_DATE"],
				'PAYMENT_TEXT20' => 'LI'.$rowdata["TRANSACTION_SELLER_ID"],
				'PAYMENT_TEXT21' => 'FB'.$rowdata["TRANSACTION_INVOICE"],
				'PAYMENT_TEXT22' => 'BR'.$rowdata["TRANSACTION_AMOUNT"],
				'PAYMENT_TEXT23' => 'SK'.$rowdata["TRANSACTION_DISCOUNTAMOUNT"],
				'PAYMENT_CODE' => '',
				'PAYMENT_STATE' =>  'S'
			];
			
			foreach($discount as $percent => $grossPart) {
				
				$mt940['DISCOUNT'][] = [
					'DISCOUNT_DATE' => $rowdata["TRANSACTION_DATE"],
					'DISCOUNT_TYPE' => $rowdata['TRANSACTION_DISCOUNTTYPE'],
					'DISCOUNT_AMOUNT' => str_replace(".",",",$grossPart),
					'DISCOUNT_NDDT' => 'NONREF',
					'DISCOUNT_TEXT00' => 'EBAVIS '. $rowdata["TRANSACTION_DATE"],
					'DISCOUNT_TEXT20' => 'SKONTO'. $percent." FB".$rowdata["TRANSACTION_INVOICE"],
					'DISCOUNT_TEXT21' => 'LI'.$rowdata["TRANSACTION_SELLER_ID"],
					'DISCOUNT_TEXT22' => 'FB'.$rowdata["TRANSACTION_INVOICE"],
					'DISCOUNT_TEXT23' => ''
				];
				
			}

			$this->data[] = $mt940;
			$this->dataCount++;

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