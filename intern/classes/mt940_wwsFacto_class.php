<?php

class MT940_wwsFacto {

	private $pg_pdo;
	
	
	public function __construct() {

		include './intern/config.php';

		// initialize variables
		
		// connect to wws/erp fpr invoice details
		$this->pg_pdo = new PDO($parameter['wwsserver'], $parameter['wwsuser'], $parameter['wwspass'], null);
		//prevent Browser Timeout
		print ".";
	}
	
	public function getInvoiceData($ppid, $fromDate = '1999-12-31', $toDate = '2999-12-31', $fromCustomer = 0, $toCustomer = 999999) {
	
		if (($ppid == '') or ($ppid == null)) {
			return [];
		}
	
		$sql = "select distinct k.fnum as invoice, k.fxnr as customer, k.ftyp as invoiceType from archiv.auftr_kopf k inner join archiv.auftr_pos p using (fblg) 
					where k.fdtm between :fromdate and :todate and k.fxnr between :fromCustomer and :toCustomer
						and (p.qnve = :ppid or fabl like '%PAYMENT_TRANSACTION_ID=' || :ppid || '%' or k.qtxl ~ :ppid or qsbz ~ :ppid  
                        or ffbg ~ :ppid or k.qna2 ~ :ppid)
						and k.ftyp in (5,6)
					order by k.fnum desc";
					
		$row_qry = $this->pg_pdo->prepare($sql);
		$row_qry->bindValue(':fromdate', $fromDate);
		$row_qry->bindValue(':todate', $toDate);
		$row_qry->bindValue(':fromCustomer', $fromCustomer);
		$row_qry->bindValue(':toCustomer', $toCustomer);
		$row_qry->bindValue(':ppid', $ppid);
		
		$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
		$row = $row_qry->fetchAll( PDO::FETCH_ASSOC );
		
		if (count($row) < 1 ) {
			$row_qry->bindValue(':fromdate', date("Y-m-d",strtotime($fromDate) - (86400 * 60)));
			$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
			$row = $row_qry->fetchAll( PDO::FETCH_ASSOC );
		}

		return $row;

	}
	
	public function getSeller($alternateSeller) {
	
		$sql = "select linr as seller from public.lif_0 where qsco = :qsco";
					
		$row_qry = $this->pg_pdo->prepare($sql);
		$row_qry->bindValue(':qsco', $alternateSeller);
		
		$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
		$row = $row_qry->fetchAll( PDO::FETCH_ASSOC );

		return $row;

	}

	public function getTaxInvoiceData($foreignInvoice) {
	
		$sql = "select cast(fskz as numeric(8,2)) as percent, sum(fprl) as grossPart from archiv.fremd_pos where fnum = :fnum group by fskz order by grossPart desc";
					
		$row_qry = $this->pg_pdo->prepare($sql);
		$row_qry->bindValue(':fnum', $foreignInvoice);
		
		$row_qry->execute() or die (print_r($row_qry->errorInfo()));;
		$row = $row_qry->fetchAll( PDO::FETCH_ASSOC );

		return $row;

	}
	
}