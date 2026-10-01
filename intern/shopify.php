<?php
 include_once './intern/autoload.php';
 include ("./intern/config.php");
 
 $konverterName = 'Shopify Payment CSV';
 $fileVar = 'csvfile';
 include('./intern/views/mt940_upload_view.php');
 
 if (isset($_POST["uploadFile"]) or (isset($argv) and in_array("/csvfile", $argv))) {

	if (isset($_FILES['csvfile']['tmp_name']) and (is_uploaded_file($_FILES['csvfile']['tmp_name'])))  {

		$uploadFile = new myFile($docpath.'SHOPIFY_UP_'.uniqid().".csv", "newUpload");
		$uploadFile->moveUploaded($_FILES['csvfile']['tmp_name']);
		$opdata =  new shopifyCSV($uploadFile->getCheckedPathName());
		$opdata->importData();
		$parameter = $opdata->getParameter();

	}
	$result = $opdata->getAllData();

	if ($_POST["format"] == 'camt053') {
		$mt940data = new camt053();
		$filename = 'Shopify_CAMT053_'.date("Ymd",strtotime($parameter['startdate']))."_".uniqid().".xml";
	} else {
		$mt940data = new mt940();
		$filename = 'Shopify_MT940_'.date("Ymd",strtotime($parameter['startdate']))."_".uniqid().".pcc";
	}
	
	$mt940data->generateMT940($result, $parameter);
	
	$filename = $mt940data->writeToFile($docpath.$filename);
	$rowCount = $mt940data->getDataCount();
	$exportfile = $docpath.$filename;
	
	unlink($uploadFile->getCheckedPathName());
	
	include('./intern/views/mt940_result_view.php');


 }



?>