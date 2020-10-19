<?php

class MT940_wwsFacto {

	private $pg_pdo;
	
	
	public function __construct() {

		include './intern/config.php';

		// initialize variables
		
		// connect to wws/erp fpr invoice details
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
	}
	
	public function getInvoiceData($ppid, $fromDate = '1999-12-31', $toDate = '2999-12-31', $fromCustomer = 0, $toCustomer = 999999) {
	
		$sql = "select distinct k.fnum as invoice, k.fxnr as customer from archiv.auftr_kopf k inner join archiv.auftr_pos p using (fblg) 
					where k.fdtm between :fromdate and :todate and k.fxnr between :fromCustomer and :toCustomer
						and (p.qnve = :ppid or fabl like 'TB_PAYMENT_TRANSACTION_ID=' || :ppid or qsbz like :ppid )
						and k.ftyp in (5,6)
					order by k.fnum desc";
		print $sql." ".$ppid;			

		$row_qry = $this->pg_pdo->prepare($sql);
		$row_qry->bindValue(':fromdate', $fromDate);
		$row_qry->bindValue(':todate', $toDate);
		$row_qry->bindValue(':fromCustomer', $fromCustomer);
		$row_qry->bindValue(':toCustomer', $toCustomer);
		$row_qry->bindValue(':ppid', $ppid);
		
		$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
		$row = $row_qry->fetchAll( PDO::FETCH_ASSOC );
		
		return $row;

	}
	
	
	
}