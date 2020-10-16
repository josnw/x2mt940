<?php
 include './intern/autoload.php';
 include ("./intern/config.php");
 
 $konverterName = 'Paypal Transaktionsdatei (TTR)';
 include('./intern/views/mt940_upload_view.php');
 
 if (isset($_POST["uploadFile"]) or (isset($argv) and in_array("/csvfile", $argv))) {

	if (is_uploaded_file($_FILES['csvfile']['tmp_name']))  {

		$uploadFile = new myFile($docpath.'PPUP_'.uniqid().".csv", "newUpload");
		$uploadFile->moveUploaded($_FILES['csvfile']['tmp_name']);
		
		$ppdata =  new paypalcsv($uploadFile->getCheckedPathName());
		$ppdata->importData();
		$parameter = $ppdata->getParameter();
		
		$mt940data = new mt940();
		$mt940data->generateMT940($ppdata->getAllData(), $parameter);

		$filename = $mt940data->writeToFile($docpath.'Paypal_MT940_'.date("Ymd",$parameter['startdate'])."_".uniqid().".pcc");
		$rowCount = $mt940data->getDataCount();
		$exportfile = $docpath.$filename;
		
		unlink($uploadFile->getCheckedPathName());
		
		include('./intern/views/mt940_result_view.php');

	}	  
 }



?>