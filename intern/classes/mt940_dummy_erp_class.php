<?php

class MT940_dummyERP {

	private $pg_pdo;
	
	
	public function __construct() {

		// initialize variables
		
		// connect to wws/erp fpr invoice details
		// $this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
	}
	
	public function getInvoiceData($ppid, $fromDate = '1999-12-31', $toDate = '2999-12-31', $fromCustomer = 0, $toCustomer = 999999) {
	
		// invoiceType: 4 = invoice, 5 = cancellation
		//return [ "invoice" = null, "customer" = null, "invoiceType" = null];
		return [];
 
	}
	
	public function getSeller($alternateSeller) {
	
		//return ["seller" = null];
		return [];

	}

	public function getTaxInvoiceData($foreignInvoice) {
	
		//return ["percent" = null, grossPar = null];
		return [];

	}
	
}