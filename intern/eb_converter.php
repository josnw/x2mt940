<?php
 include_once './intern/autoload.php';
 include ("./intern/config.php");
 
 $konverterName = 'Eurobaustoff Avis';
 $fileVar = 'ebavis';
 include('./intern/views/mt940_upload_view.php');
 
 if (isset($_POST["uploadFile"]) or (isset($argv) and in_array("/ebavis", $argv))) {

	if (isset($_FILES['ebavis']['tmp_name']) and (is_uploaded_file($_FILES['ebavis']['tmp_name'])))  {

		$uploadFile = new myFile($docpath.'EBUP_'.uniqid().".eba", "newUpload");
		$uploadFile->moveUploaded($_FILES['ebavis']['tmp_name']);
		$ebdata =  new eurobaustoffAvis($uploadFile->getCheckedPathName());
		$ebdata->importData();
		$parameter = $ebdata->getParameter();
		
	}

	$mt940data = new mt940();
	$mt940data->generateMT940($ebdata->getAllData(), $parameter);

	$filename = $mt940data->writeToFile($docpath.'Eurobaustoff_MT940_'.date("Ymd",strtotime($parameter['startdate']))."_".uniqid().".pcc");
	$rowCount = $mt940data->getDataCount();
	$exportfile = $docpath.$filename;
	
	unlink($uploadFile->getCheckedPathName());
	
	include('./intern/views/mt940_result_view.php');


 }



?>