<?php
 include_once './intern/autoload.php';
 include ("./intern/config.php");
 
 $konverterName = 'Amazon Payment CSV';
 $fileVar = 'csvfile';
 include('./intern/views/mt940_upload_view.php');
 
 if (isset($_POST["uploadFile"]) or (isset($argv) and in_array("/csvfile", $argv))) {

	if (isset($_FILES['csvfile']['tmp_name']) and (is_uploaded_file($_FILES['csvfile']['tmp_name'])))  {

		$uploadFile = new myFile($docpath.'AMAZON_UP_'.uniqid().".csv", "newUpload");
		$uploadFile->moveUploaded($_FILES['csvfile']['tmp_name']);
		$opdata =  new amazonPayment($uploadFile->getCheckedPathName());
		$opdata->importData();
		$parameter = $opdata->getParameter();

	}
	$result = $opdata->getAllData();

	$mt940data = new mt940();
	$mt940data->generateMT940($result, $parameter);

	$filename = $mt940data->writeToFile($docpath.'Amazon_Payment_MT940_'.date("Ymd",strtotime($parameter['startdate']))."_".uniqid().".pcc");
	$rowCount = $mt940data->getDataCount();
	$exportfile = $docpath.$filename;
	
	unlink($uploadFile->getCheckedPathName());
	
	include('./intern/views/mt940_result_view.php');


 }



?>