<?php

class camt053 {

	private $posparam;
	private	$dataset;
	private $inFile;
	private $ppHeader;
	private $amountTotal;
	private $wwsInvoices;
	private $dataPos;
	private $dataCount;
	private $avisNumber;
	private $xmlns = "urn:iso:std:iso:20022:tech:xsd:camt.053.001.02";
	
	
	public function __construct($avisNumber = 0) {

		include './intern/config.php';
		
		// initialize variables
		$this->dataset = null;
		$this->avisNumber = $avisNumber;
		
	}
	
	private function camt053Header($parameter) {
		$header = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
		$header .= '<Document xmlns="'.$this->xmlns.'">'."\n";
		$header .= '  <BkToCstmrStmt>'."\n";
		$header .= '    <GrpHdr>'."\n";
		$header .= '      <MsgId>PP'.date("YmdHis").'</MsgId>'."\n";
		$header .= '      <CreDtTm>'.date("Y-m-d\TH:i:s", strtotime($parameter['startdate'])).'</CreDtTm>'."\n";
		$header .= '    </GrpHdr>'."\n";
		$header .= '    <Stmt>'."\n";
		$header .= '      <Id>'.$this->avisNumber.'</Id>'."\n";
		
		// Account information
		$header .= '      <Acct>'."\n";
		$header .= '        <Id>'."\n";
		$header .= '          <Othr>'."\n";
		$header .= '            <Id>'.$parameter['blz'].'/'.$parameter['konto'].'</Id>'."\n";
		$header .= '          </Othr>'."\n";
		$header .= '        </Id>'."\n";
		$header .= '        <Ccy>'.$parameter['currency'].'</Ccy>'."\n";
		$header .= '      </Acct>'."\n";
		
		// Opening balance
		if (!empty($parameter["balanceDate"])) {
			$balanceDate = $parameter["balanceDate"];
		} else {
			$balanceDate = date("Ymd", strtotime($parameter['startdate']));
		}
		$header .= '      <Bal>'."\n";
		$header .= '        <Tp>'."\n";
		$header .= '          <CdOrPrtry>'."\n";
		$header .= '            <Cd>OPBD</Cd>'."\n";
		$header .= '          </CdOrPrtry>'."\n";
		$header .= '        </Tp>'."\n";
		$header .= '        <Amt Ccy="'.$parameter['currency'].'">0.00</Amt>'."\n";
		$header .= '        <CdtDbtInd>CRDT</CdtDbtInd>'."\n";
		$header .= '        <Dt>'."\n";
		$header .= '          <Dt>'.$balanceDate.'</Dt>'."\n";
		$header .= '        </Dt>'."\n";
		$header .= '      </Bal>'."\n";
		
		return $header;
	}
	
	private function camt053Pos($data) {
		
		if (($data['PAYMENT_STATE'] == "S") and (preg_match('/[1-9]+/', $data['PAYMENT_AMOUNT']))){
			$pos = '';
			
			// Convert amount format from German to ISO (replace comma with dot)
			$amount = str_replace(",", ".", $data['PAYMENT_AMOUNT']);
			if (substr($data['PAYMENT_AMOUNT'], 0, 1) == ",") {
				$amount = "0" . $amount;
			}
			
			// Determine credit/debit indicator
			$cdtDbtInd = (substr($amount, 0, 1) == '-') ? 'DBIT' : 'CRDT';
			$amount = ltrim($amount, '-');
			
			$pos .= '      <Ntry>'."\n";
			$pos .= '        <Amt Ccy="'.$data['PAYMENT_CURRENCY'].'">'.$amount.'</Amt>'."\n";
			$pos .= '        <CdtDbtInd>'.$cdtDbtInd.'</CdtDbtInd>'."\n";
			$pos .= '        <Sts>BOOK</Sts>'."\n";
			
			// Booking date - convert from Ymd to Y-m-d
			$bookingDate = $data['PAYMENT_DATE'];
			if (strlen($bookingDate) == 6) {
				$bookingDate = substr($bookingDate, 0, 2) . '-' . substr($bookingDate, 2, 2) . '-' . substr($bookingDate, 4, 2);
			}
			$pos .= '        <BookgDt>'."\n";
			$pos .= '          <Dt>'.$bookingDate.'</Dt>'."\n";
			$pos .= '        </BookgDt>'."\n";
			
			// Value date
			if (isset($data['PAYMENT_NDDT']) && !empty($data['PAYMENT_NDDT'])) {
				$valueDate = $data['PAYMENT_NDDT'];
				if (strlen($valueDate) == 6) {
					$valueDate = substr($valueDate, 0, 2) . '-' . substr($valueDate, 2, 2) . '-' . substr($valueDate, 4, 2);
				}
				$pos .= '        <ValDt>'."\n";
				$pos .= '          <Dt>'.$valueDate.'</Dt>'."\n";
				$pos .= '        </ValDt>'."\n";
			}
			
			// Account servicer reference
			$pos .= '        <AcctSvcrRef>'.htmlspecialchars($data['PAYMENT_TYPE'] ?? 'TRF').'</AcctSvcrRef>'."\n";
			
			// Remittance information
			$pos .= '        <RmtInf>'."\n";
			$pos .= '          <Ustrd>';
			
			// Build remittance text from PAYMENT_TEXT fields
			$remittanceText = '';
			if (!empty($data['PAYMENT_TEXT00'])) {
				$remittanceText .= $data['PAYMENT_TEXT00'];
			}
			for ($i = 20; $i < 30; $i++) {
				if (!empty($data['PAYMENT_TEXT'.$i])) {
					if (!empty($remittanceText)) {
						$remittanceText .= ' ';
					}
					$remittanceText .= $data['PAYMENT_TEXT'.$i];
				}
			}
			$pos .= htmlspecialchars($remittanceText);
			$pos .= '</Ustrd>'."\n";
			$pos .= '        </RmtInf>'."\n";
			
			// Bank transaction code
			$pos .= '        <BkTxCd>'."\n";
			$pos .= '          <Domn>'."\n";
			$pos .= '            <Cd>PMNT</Cd>'."\n";
			$pos .= '            <Fmly>'."\n";
			$pos .= '              <Cd>RTPM</Cd>'."\n";
			$pos .= '              <SubFmlyCd>TRF</SubFmlyCd>'."\n";
			$pos .= '            </Fmly>'."\n";
			$pos .= '          </Domn>'."\n";
			$pos .= '        </BkTxCd>'."\n";
			
			$pos .= '      </Ntry>'."\n";
			
			// Process CHARGE entries
			if ((isset($data['CHARGE_AMOUNT'])) and (str_replace(",", ".", $data['CHARGE_AMOUNT']) <> 0) and (preg_match('/[1-9]+/', $data['CHARGE_AMOUNT'])) ) {
				$chargeAmount = str_replace(",", ".", $data['CHARGE_AMOUNT']);
				if (substr($data['CHARGE_AMOUNT'], 0, 1) == ",") {
					$chargeAmount = "0" . $chargeAmount;
				}
				
				$pos .= '      <Ntry>'."\n";
				$pos .= '        <Amt Ccy="'.$data['CHARGE_CURRENCY'].'">'.$chargeAmount.'</Amt>'."\n";
				$pos .= '        <CdtDbtInd>DBIT</CdtDbtInd>'."\n";
				$pos .= '        <Sts>BOOK</Sts>'."\n";
				
				// Charge booking date
				$chargeBookingDate = $data['CHARGE_DATE'];
				if (strlen($chargeBookingDate) == 6) {
					$chargeBookingDate = substr($chargeBookingDate, 0, 2) . '-' . substr($chargeBookingDate, 2, 2) . '-' . substr($chargeBookingDate, 4, 2);
				}
				$pos .= '        <BookgDt>'."\n";
				$pos .= '          <Dt>'.$chargeBookingDate.'</Dt>'."\n";
				$pos .= '        </BookgDt>'."\n";
				
				// Charge value date
				if (isset($data['CHARGE_NDDT']) && !empty($data['CHARGE_NDDT'])) {
					$chargeValueDate = $data['CHARGE_NDDT'];
					if (strlen($chargeValueDate) == 6) {
						$chargeValueDate = substr($chargeValueDate, 0, 2) . '-' . substr($chargeValueDate, 2, 2) . '-' . substr($chargeValueDate, 4, 2);
					}
					$pos .= '        <ValDt>'."\n";
					$pos .= '          <Dt>'.$chargeValueDate.'</Dt>'."\n";
					$pos .= '        </ValDt>'."\n";
				}
				
				$pos .= '        <AcctSvcrRef>'.htmlspecialchars($data['CHARGE_TYPE'] ?? 'CHAR').'</AcctSvcrRef>'."\n";
				
				// Charge remittance information
				$pos .= '        <RmtInf>'."\n";
				$pos .= '          <Ustrd>';
				
				$chargeRemittanceText = '';
				if (!empty($data['CHARGE_TEXT00'])) {
					$chargeRemittanceText .= $data['CHARGE_TEXT00'];
				}
				for ($i = 20; $i < 30; $i++) {
					if (!empty($data['CHARGE_TEXT'.$i])) {
						if (!empty($chargeRemittanceText)) {
							$chargeRemittanceText .= ' ';
						}
						$chargeRemittanceText .= $data['CHARGE_TEXT'.$i];
					}
				}
				$pos .= htmlspecialchars($chargeRemittanceText);
				$pos .= '</Ustrd>'."\n";
				$pos .= '        </RmtInf>'."\n";
				
				// Bank transaction code for charges
				$pos .= '        <BkTxCd>'."\n";
				$pos .= '          <Domn>'."\n";
				$pos .= '            <Cd>CHRG</Cd>'."\n";
				$pos .= '            <Fmly>'."\n";
				$pos .= '              <Cd>CHRG</Cd>'."\n";
				$pos .= '              <SubFmlyCd>FEES</SubFmlyCd>'."\n";
				$pos .= '            </Fmly>'."\n";
				$pos .= '          </Domn>'."\n";
				$pos .= '        </BkTxCd>'."\n";
				
				$pos .= '      </Ntry>'."\n";
			}
			
			// Process DISCOUNT entries
			if (isset($data['DISCOUNT']) and (is_array($data['DISCOUNT'])) ) {
				foreach ($data['DISCOUNT'] as $discount) {
					$discountAmount = str_replace(",", ".", $discount['DISCOUNT_AMOUNT']);
					if (substr($discount['DISCOUNT_AMOUNT'], 0, 1) == ",") {
						$discountAmount = "0" . $discountAmount;
					}
					
					$pos .= '      <Ntry>'."\n";
					$pos .= '        <Amt Ccy="'.$discount['DISCOUNT_CURRENCY'].'">'.$discountAmount.'</Amt>'."\n";
					$pos .= '        <CdtDbtInd>CRDT</CdtDbtInd>'."\n";
					$pos .= '        <Sts>BOOK</Sts>'."\n";
					
					// Discount booking date
					$discountBookingDate = $discount['DISCOUNT_DATE'];
					if (strlen($discountBookingDate) == 6) {
						$discountBookingDate = substr($discountBookingDate, 0, 2) . '-' . substr($discountBookingDate, 2, 2) . '-' . substr($discountBookingDate, 4, 2);
					}
					$pos .= '        <BookgDt>'."\n";
					$pos .= '          <Dt>'.$discountBookingDate.'</Dt>'."\n";
					$pos .= '        </BookgDt>'."\n";
					
					// Discount value date
					if (isset($discount['DISCOUNT_NDDT']) && !empty($discount['DISCOUNT_NDDT'])) {
						$discountValueDate = $discount['DISCOUNT_NDDT'];
						if (strlen($discountValueDate) == 6) {
							$discountValueDate = substr($discountValueDate, 0, 2) . '-' . substr($discountValueDate, 2, 2) . '-' . substr($discountValueDate, 4, 2);
						}
						$pos .= '        <ValDt>'."\n";
						$pos .= '          <Dt>'.$discountValueDate.'</Dt>'."\n";
						$pos .= '        </ValDt>'."\n";
					}
					
					$pos .= '        <AcctSvcrRef>'.htmlspecialchars($discount['DISCOUNT_TYPE'] ?? 'DISC').'</AcctSvcrRef>'."\n";
					
					// Discount remittance information
					$pos .= '        <RmtInf>'."\n";
					$pos .= '          <Ustrd>';
					
					$discountRemittanceText = '';
					if (!empty($discount['DISCOUNT_TEXT00'])) {
						$discountRemittanceText .= $discount['DISCOUNT_TEXT00'];
					}
					if (!empty($discount['DISCOUNT_TEXT20'])) {
						if (!empty($discountRemittanceText)) {
							$discountRemittanceText .= ' ';
						}
						$discountRemittanceText .= $discount['DISCOUNT_TEXT20'];
					}
					if (!empty($discount['DISCOUNT_TEXT21'])) {
						if (!empty($discountRemittanceText)) {
							$discountRemittanceText .= ' ';
						}
						$discountRemittanceText .= $discount['DISCOUNT_TEXT21'];
					}
					if (!empty($discount['DISCOUNT_TEXT22'])) {
						if (!empty($discountRemittanceText)) {
							$discountRemittanceText .= ' ';
						}
						$discountRemittanceText .= $discount['DISCOUNT_TEXT22'];
					}
					$pos .= htmlspecialchars($discountRemittanceText);
					$pos .= '</Ustrd>'."\n";
					$pos .= '        </RmtInf>'."\n";
					
					// Bank transaction code for discounts
					$pos .= '        <BkTxCd>'."\n";
					$pos .= '          <Domn>'."\n";
					$pos .= '            <Cd>DISC</Cd>'."\n";
					$pos .= '            <Fmly>'."\n";
					$pos .= '              <Cd>DISC</Cd>'."\n";
					$pos .= '              <SubFmlyCd>DISC</SubFmlyCd>'."\n";
					$pos .= '            </Fmly>'."\n";
					$pos .= '          </Domn>'."\n";
					$pos .= '        </BkTxCd>'."\n";
					
					$pos .= '      </Ntry>'."\n";
				}
			}
			
			return $pos;
			
		} else {
			
			return false;
			
		}
	}
	
	private function camt053footer($parameter) {
		$footer = '';
		
		// Closing balance
		$footer .= '      <Bal>'."\n";
		$footer .= '        <Tp>'."\n";
		$footer .= '          <CdOrPrtry>'."\n";
		$footer .= '            <Cd>CLBD</Cd>'."\n";
		$footer .= '          </CdOrPrtry>'."\n";
		$footer .= '        </Tp>'."\n";
		
		// Convert TotalAmount from German format
		$totalAmount = str_replace(",", ".", $parameter["TotalAmount"]);
		if (substr($parameter["TotalAmount"], 0, 1) == ",") {
			$totalAmount = "0" . $totalAmount;
		}
		
		$footer .= '        <Amt Ccy="'.$parameter["currency"].'">'.$totalAmount.'</Amt>'."\n";
		$footer .= '        <CdtDbtInd>'.($parameter["TotalSH"] == 'C' ? 'CRDT' : 'DBIT').'</CdtDbtInd>'."\n";
		
		$endDate = date("Y-m-d", strtotime($parameter["enddate"]));
		$footer .= '        <Dt>'."\n";
		$footer .= '          <Dt>'.$endDate.'</Dt>'."\n";
		$footer .= '        </Dt>'."\n";
		$footer .= '      </Bal>'."\n";
		
		$footer .= '    </Stmt>'."\n";
		$footer .= '  </BkToCstmrStmt>'."\n";
		$footer .= '</Document>'."\n";
		
		return $footer;
	}
	
	public function generateMT940($data, $parameter) {
		$this->dataset = $this->camt053Header($parameter);
		
		foreach($data as $line) {
			$this->dataset .= $this->camt053Pos($line); 	
		}

		$this->dataset .= $this->camt053Footer($parameter);
		
	}
		
	public function writeToFile($fileName) {
		if (strlen($this->dataset) > 0) {
			$this->outfile = new myfile($fileName, 'writefull');
			$this->outfile->putContent($this->dataset);
			$this->outfile->chmod(0664);
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
