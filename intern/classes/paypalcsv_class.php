<?php

class paypalcsv {

	private $mt940param;
	private	$data;
	private $inFile;
	private $ppHeader;
	private $amountTotal;
	private $wwsInvoices;
	private $dataPos;
	private $dataCount;

	
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

			if ($rowdata["Transaktionsereigniscode"] <> 'T0400') {
			
				if (substr($rowdata["Transaktion  Gutschrift oder Belastung"],0,1) == "C") {
					$this->amountTotal += $rowdata["Bruttotransaktionsbetrag"]/100;
				} else {
					$this->amountTotal -= $rowdata["Bruttotransaktionsbetrag"]/100;
				}
				if (substr($rowdata["Gebühr Soll oder Haben"],0,1) == "C") {
					$this->amountTotal += $rowdata["Gebührenbetrag"]/100;
				} else {
					$this->amountTotal -= $rowdata["Gebührenbetrag"]/100;
				}
				$name = strtoupper(preg_replace( '/[^a-z0-9 ]/i', '_', $rowdata["Käufer-ID"]));

				$fromDate = date("Y-m-d",strtotime($rowdata["Transaktionseinleitungsdatum"])-(24*60*60*4));
				$toDate = date("Y-m-d",time());
				$ppid = $rowdata["Transaktionscode"];
				
				$invoiceData = $this->wwsInvoices->getInvoiceData($ppid, $fromDate, $toDate, $this->mt940param['fromCustomer'], $this->mt940param['toCustomer']);
				
				$invoiceStr = '';
				foreach($invoiceData as $invoice) {
					$invoiceStr .= 'RG'.$invoice['invoice']; 
				}
			
				$mt940 = [];
				
				if (($rowdata["Bruttotransaktionsbetrag"] <> 0) and ($rowdata["Transaktionsstatus"] == "S")) {
					
					$mt940 = [
						'PAYMENT_DATE' => date("ymd",strtotime($rowdata["Transaktionseinleitungsdatum"])),
						'PAYMENT_TYPE' => substr($rowdata["Transaktion  Gutschrift oder Belastung"],0,1),
						'PAYMENT_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata["Bruttotransaktionsbetrag"]/100)),
						'PAYMENT_NDDT' => $invoiceData->fnum[0]["invoice"],
						'PAYMENT_TEXT00' => 'PAYPAL',
						'PAYMENT_TEXT20' => 'KD'.$invoiceData->fnum[0]["customer"],
						'PAYMENT_TEXT21' => $invoiceStr,
						'PAYMENT_TEXT22' => $rowdata["Transaktionscode"],
						'PAYMENT_TEXT23' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata["Nachname"]." ".$rowdata["Käufer-ID"])),
						'PAYMENT_CODE' => $rowdata["Transaktionsereigniscode"],
						'CHARGE_DATE' => date("ymd",strtotime($rowdata["Transaktionseinleitungsdatum"])),
						'CHARGE_TYPE' => substr($rowdata["Gebühr Soll oder Haben"],0,1),
						'CHARGE_AMOUNT' => str_replace(".",",",sprintf("%01.2f",$rowdata["Gebührenbetrag"]/100)),
						'CHARGE_NDDT' => 'NONREF',
						'CHARGE_TEXT00' => 'PAYPAL',
						'CHARGE_TEXT20' => 'PAYPAL GEBUEHR',
						'CHARGE_TEXT21' => $rowdata["Transaktionscode"],
						'CHARGE_TEXT22' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata["Nachname"]." ".$rowdata["Käufer-ID"])),
						'PAYMENT_STATE' =>  $rowdata["Transaktionsstatus"]
					];
				} elseif  ($rowdata["Transaktionsstatus"] <> "S") {
					$mt940 = [
						'PAYMENT_NDDT' => $invoiceData->fnum[0]["invoice"],
						'PAYMENT_TEXT22' => $rowdata["Transaktionscode"],
						'PAYMENT_TEXT23' => preg_replace ( '/[^a-z0-9 ]/i', '', strtoupper($rowdata["Nachname"]." ".$rowdata["Käufer-ID"])),
						'PAYMENT_CODE' => $rowdata["Transaktionsereigniscode"],
						'PAYMENT_STATE' =>  $rowdata["Transaktionsstatus"]
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