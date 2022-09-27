<?php

$mapping_prefix = "";    // filename prefix for customized mapping files

# MT940 parameter
# paypal
$paypal['blz'] = "90000001";  // dummy BLZ for Import
$paypal['konto'] = "9100000101"; // Default account
$paypal['fromCustomer'] = "1"; // customer range 
$paypal['toCustomer'] = "999999"; // customer range 
$paypal['currency'] = "EUR"; // Default Currency 

# otto payment
$otto['blz'] = "90000001";  // dummy BLZ for Import
$otto['konto'] = "9100000102"; // Default account
$otto['fromCustomer'] = "100000"; // customer range 
$otto['toCustomer'] = "100010"; // customer range 
$otto['currency'] = "EUR"; // Default Currency 

# real.de payment
$real['blz'] = "90000001";  // dummy BLZ for Import
$real['konto'] = "9100000103"; // Default account
$real['fromCustomer'] = "100000"; // customer range 
$real['toCustomer'] = "100010"; // customer range 
$real['currency'] = "EUR"; // Default Currency 

# EUROBAUSTOFF
$eurobaustoff['blz'] = "90000002";  // dummy BLZ for Import
$eurobaustoff['konto'] = "9000000201"; // Default account
$eurobaustoff['AccountMaxLength'] = 12; // Length for Bank account number
$eurobaustoff['grossPosting'] = true; // customer range 
$eurobaustoff['alternateSeller'] = true; // customer range 
$eurobaustoff['currency'] = "EUR"; // Default Currency 
$eurobaustoff['bdateIsAdate'] = true; // set avis date to booking date

# adyen payment
$adyen['blz'] = "90000001";  // dummy BLZ for Import
$adyen['konto'] = "9100000104"; // Default account
$adyen['fromCustomer'] = "100000"; // customer range 
$adyen['toCustomer'] = "100010"; // customer range 
$adyen['extractTid'] = false; // extract real TransactionID without itemID
$adyen['payout'] = true; // Generate payout mt940 data 


# WWS config
$wwsClassName = "MT940_dummyERP";
//$wwsClassName = "MT940_wwsFacto";

if (file_exists('./intern/config.json')) {
	
	$configFile = new myFile('./intern/config.json', 'readfull');
	$parameter = $configFile->readJson();
}

$wwsAdminUsers = [ 1 ];

if ($_SESSION['user'] == 999) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}


$docpath = "./docs/";

date_default_timezone_set("Europe/Berlin");

######## menu config ##############
$menu_name['root']['Startseite']  = './home.php';
$menu_name['root']['PayPal']  = './pp_converter.php';
$menu_name['root']['Otto Payment']  = './ottopayment.php';
$menu_name['root']['Real.de Payment']  = './realpayment.php';
$menu_name['root']['Adyen']  = './adyen.php';
//$menu_name['root']['Eurobaustoff']  = './eb_converter.php';
$menu_name['root']['Logout']  = './logout.php';

$menu_name['user']['Startseite']  = './home.php'; 
if (isset($_SESSION["uid"])) {
	if ($_SESSION['level'] >= 0) { $menu_name['user']['PayPal']  = './pp_converter.php'; }
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Otto Payment']  = './ottopayment.php'; }
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Real.de Payment']  = './realpayment.php'; }
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Adyen']  = './adyen.php'; }
	if ($_SESSION['level'] >= 9) { $menu_name['user']['Eurobaustoff']  = './eb_converter.php'; }
}

$menu_name['user']['Logout']  = './logout.php';


?>
