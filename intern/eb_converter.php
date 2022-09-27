<?php
 include_once './intern/autoload.php';
 include ("./intern/config.php");
 
 $konverterName = 'Eurobaustoff Avis';
 $fileVar = 'ebavis';
 
 if (date("N") < 4) { 
	$paymentDate = date("Y-m-d", time()+2*24*60*60); 
 } else { 
	$paymentDate = date("Y-m-d", time()+4*24*60*60); 
}
 
 include('./intern/views/mt940_upload_view.php');
 
 if (isset($_POST["uploadFile"]) or (isset($argv) and in_array("/ebavis", $argv))) {

	if (isset($_FILES['ebavis']['tmp_name']) and (is_uploaded_file($_FILES['ebavis']['tmp_name'])))  {

		$uploadFile = new myFile($docpath.'EBUP_'.uniqid().".eba", "newUpload");
		$uploadFile->moveUploaded($_FILES['ebavis']['tmp_name']);
		$ebdata =  new eurobaustoffAvis($uploadFile->getCheckedPathName());
		
		$paymentDate = date("Y-m-d",strtotime(preg_replace("[^0-9\-\.]","",$_POST["paymentDate"])));
		$ebdata->importData($paymentDate);
		$parameter = $ebdata->getParameter();
		
	}
	
	$result = $ebdata->getAllData();
	
	$mt940data = new mt940(date("Ymd",strtotime(preg_replace("[^0-9\-\.]","",$_POST["paymentDate"]))));
	$mt940data->generateMT940($result, $parameter);

	$filename = $mt940data->writeToFile($docpath.'Eurobaustoff_MT940_'.date("Ymd",strtotime($parameter['startdate']))."_".uniqid().".pcc");
	$rowCount = $mt940data->getDataCount();
	$exportfile = $docpath.$filename;
	
	unlink($uploadFile->getCheckedPathName());

	include('./intern/views/mt940_result_view.php');


 }



?>