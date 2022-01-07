<?php

class amazonPayment {

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
		$this->mt940param = $amazon;
		$this->data = [];
		$this->amountTotal = 0;
		$this->dataPos = 0;
		$this->dataCount = 0;
		
		// connect to wws/erp for invoice details
		// dynamically wws/erp connection in config.php with a new class
		$this->wwsInvoices = new $wwsClassName();

		$this->infile = new myfile($fileName);

		if (file_exists("./intern/mapping/".$mapping_prefix."amazon.json")) {
			$mapping = new myfile("./intern/mapping/".$mapping_prefix."amazon.json","readfull");
		} else {
			$mapping = new myfile("./intern/mapping/amazon.json","readfull");
		}
		$this->mapping = $mapping->readJson();

		$row = $this->infile->readCSV("\t");
		$row[0] = trim($row[0],"\"\xEF\xBB\xBF");
		$this->ppHeader = $row;
		$row = $this->infile->readCSV("\t");
		$rowdata = array_combine($this->ppHeader,$row);
		$this->mt940param['startdate'] = $rowdata[$this->mapping['SETTLEMENT_START']];
		$this->mt940param['enddate'] = $rowdata[$this->mapping['SETTLEMENT_END']];
	}
	
	public function importData() {
		if (count($this->data) > 0) {
			return true;
		}
		
		$transactionSumAmount = 0;
		$transactionSumCharge = 0;
		$orderNumber = '';
		
		while (($row = $this->infile->readCSV("\t")) !== FALSE) {
		    
			$rowdatanew = [];
			$rowdatanew = array_combine($this->ppHeader,$row);
			
			if ( ( $this->dataCount == 0 ) and ($orderNumber == '') ) {
			    $orderNumber = $rowdatanew[$this->mapping['TRANSACTION_CODE']];
			}

			if ( ( $rowdatanew[$this->mapping['TRANSACTION_CODE']] != $orderNumber ) or 
			    ( $rowdata[$this->mapping['TRANSACTION_TYPE']] != $this->mapping['TYPE_ORDER'] ) ) {
			    print $rowdata[$this->mapping['TRANSACTION_TYPE']]." -> ".$rowdata[$this->mapping['TRANSACTION_CODE']]." -> ".$orderNumber.": ".$transactionSumAmount."<br>";
				$mt940 = [];
				
				//				if (($rowdata[$this->mapping['TRANSACTION_AMOUNT']] <> 0) and ($rowdata[$this->mapping['TRANSACTION_EVENTCODE']] == $this->mapping["CHECK_FINISH_STAT"])) {
				if ($transactionSumAmount <> 0) {
					
				    $mt940 = [
							'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'PAYMENT_TYPE' => $transactionType,
							'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumAmount))),
							'PAYMENT_NDDT' => $defaultInvoice,
							'PAYMENT_TEXT00' => 'AMAZON',
							'PAYMENT_TEXT20' => 'AMAZON KD'.$defaultCustomer,
							'PAYMENT_TEXT21' => $invoiceStr,
							'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'PAYMENT_TEXT23' => $event,
							'PAYMENT_CODE' => $event,
							'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
							'CHARGE_TYPE' => $transactionChargeType,
							'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumCharge))),
							'CHARGE_NDDT' => 'NONREF',
							'CHARGE_TEXT00' => 'AMAZON',
							'CHARGE_TEXT20' => 'AMAZON GEB.',
							'CHARGE_TEXT21' => $rowdata[$this->mapping['TRANSACTION_CODE']],
							'CHARGE_TEXT22' => '', // strtoupper($name),
							'PAYMENT_STATE' =>  'S'
					];
				} elseif ( $transactionSumCharge <> 0 ) {
				    
				    if (in_array($rowdata[$this->mapping['AMOUNT_DESCRIPTION']], $this->mapping['TYPE_FEE'])) {
				        $type = "GEB.";
				    } else {
				        $type = $rowdata[$this->mapping['TRANSACTION_CODE']];
				    }
				    $mt940 = [
				        'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
				        'PAYMENT_TYPE' => $transactionType,
				        'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumCharge))),
				        'PAYMENT_NDDT' => $defaultInvoice,
				        'PAYMENT_TEXT00' => 'AMAZON',
				        'PAYMENT_TEXT20' => 'AMAZON '.$type,
				        'PAYMENT_TEXT21' => $rowdata[$this->mapping['AMOUNT_DESCRIPTION']],
				        'PAYMENT_TEXT22' => '',
				        'PAYMENT_TEXT23' => $event,
				        'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
				        'CHARGE_DATE' => '',
				        'CHARGE_TYPE' => '',
				        'CHARGE_AMOUNT' => '',
				        'CHARGE_NDDT' => '',
				        'CHARGE_TEXT00' => '',
				        'CHARGE_TEXT20' => '',
				        'CHARGE_TEXT21' => '',
				        'CHARGE_TEXT22' => '', // strtoupper($name),
				        'PAYMENT_STATE' =>  'S'
				    ];
				}
				
				$this->data[] = $mt940;
				
				$this->dataCount++;
				
			    $orderNumber = $rowdatanew[$this->mapping['TRANSACTION_CODE']];  
			    $transactionSumAmount = 0;
			    $transactionSumCharge = 0;
			}
			
			$rowdata = $rowdatanew;
			

			if ($this->mt940param['startdate'] == null) {
				$this->mt940param['startdate']	= $rowdata[$this->mapping['TRANSACTION_DATE']];
			}
			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(".","",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);

			$rowdata[$this->mapping['TRANSACTION_AMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_AMOUNT']]);
			$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']] = str_replace(",",".",$rowdata[$this->mapping['TRANSACTION_CHARGEAMOUNT']]);
			
			if (! in_array($rowdata[$this->mapping['TRANSACTION_EVENTCODE']], $this->mapping['CHECK_EXCLUDECODE'])) {

			    if ($rowdata[$this->mapping['AMOUNT_TYPE']] == $this->mapping['TYPE_PRICE']) {
			        $transactionSumAmount += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
			    } else {
				    $transactionSumCharge += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
			    }
			    
			    if ($transactionSumAmount > 0) {
					$transactionType = "C";
					$transactionChargeType = "D";
					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
				} else {
					$transactionType = "D";
					$transactionChargeType = "C";

					$this->amountTotal += $rowdata[$this->mapping['TRANSACTION_AMOUNT']];
				}
			
				//$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata[$this->mapping['TRANSACTION_SELLER_NAME']]));

				$fromDate = date("Y-m-d",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']]));
				$toDate = date("Y-m-d",time());
				
				$ppid = $rowdata[$this->mapping['TRANSACTION_CODE']];

				
				$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				
				$invoiceStr = '';
				foreach($invoiceData as $invoice) {
					$invoiceStr .= 'RG'.$invoice['invoice']." "; 
				}
				isset($invoiceData[0]["invoice"]) ? $defaultInvoice = $invoiceData[0]["invoice"] : $defaultInvoice = 'NONREF';
				isset($invoiceData[0]["customer"]) ? $defaultCustomer = $invoiceData[0]["customer"] : $defaultCustomer = '';	
			
				$spacePos = strpos($rowdata[$this->mapping['TRANSACTION_TYPE']]," ",10);
				if (! $spacePos) { $spacePos = 30; }
				$event = substr($rowdata[$this->mapping['TRANSACTION_TYPE']],0,$spacePos);
				


			
			}

			$this->mt940param['enddate'] = $rowdata[$this->mapping['TRANSACTION_DATE']];

		}
		
		if ($transactionSumAmount <> 0) {
		    
		    $mt940 = [
		        'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
		        'PAYMENT_TYPE' => $transactionType,
		        'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumAmount))),
		        'PAYMENT_NDDT' => $defaultInvoice,
		        'PAYMENT_TEXT00' => 'AMAZON',
		        'PAYMENT_TEXT20' => 'AMAZON KD'.$defaultCustomer,
		        'PAYMENT_TEXT21' => $invoiceStr,
		        'PAYMENT_TEXT22' => $rowdata[$this->mapping['TRANSACTION_CODE']],
		        'PAYMENT_TEXT23' => $event,
		        'PAYMENT_CODE' => $event,
		        'CHARGE_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
		        'CHARGE_TYPE' => $transactionChargeType,
		        'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumCharge))),
		        'CHARGE_NDDT' => 'NONREF',
		        'CHARGE_TEXT00' => 'AMAZON',
		        'CHARGE_TEXT20' => 'AMAZON GEB.',
		        'CHARGE_TEXT21' => $rowdata[$this->mapping['TRANSACTION_CODE']],
		        'CHARGE_TEXT22' => '', // strtoupper($name),
		        'PAYMENT_STATE' =>  'S'
		    ];
		} elseif ( $transactionSumCharge <> 0 ) {
		    
		    if (in_array($rowdata[$this->mapping['AMOUNT_DESCRIPTION']], $this->mapping['TYPE_FEE'])) {
		        $type = "GEB.";
		    } else {
		        $type = $rowdata[$this->mapping['TRANSACTION_CODE']];
		    }
		    $mt940 = [
		        'PAYMENT_DATE' => date("ymd",strtotime($rowdata[$this->mapping['TRANSACTION_DATE']])),
		        'PAYMENT_TYPE' => $transactionType,
		        'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",abs($transactionSumCharge))),
		        'PAYMENT_NDDT' => $defaultInvoice,
		        'PAYMENT_TEXT00' => 'AMAZON',
		        'PAYMENT_TEXT20' => 'AMAZON '.$type,
		        'PAYMENT_TEXT21' => $rowdata[$this->mapping['AMOUNT_DESCRIPTION']],
		        'PAYMENT_TEXT22' => '',
		        'PAYMENT_TEXT23' => $event,
		        'PAYMENT_CODE' => $rowdata[$this->mapping['TRANSACTION_EVENTCODE']],
		        'CHARGE_DATE' => '',
		        'CHARGE_TYPE' => '',
		        'CHARGE_AMOUNT' => '',
		        'CHARGE_NDDT' => '',
		        'CHARGE_TEXT00' => '',
		        'CHARGE_TEXT20' => '',
		        'CHARGE_TEXT21' => '',
		        'CHARGE_TEXT22' => '', // strtoupper($name),
		        'PAYMENT_STATE' =>  'S'
		    ];
		}
		
		$this->data[] = $mt940;
		
		$this->dataCount++;
		
		if (($this->mt940param['payout']) ) {
		    $this->createPayoutData($this->amountTotal, $this->mt940param['enddate']);
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
	        'PAYMENT_TEXT00' => 'AMAZON',
	        'PAYMENT_TEXT20' => 'AMAZON PAYOUT '.$payoutdate,
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