<?php

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

# EUROBAUSTOFF
$eurobaustoff['blz'] = "90000002";  // dummy BLZ for Import
$eurobaustoff['konto'] = "9000000201"; // Default account
$eurobaustoff['AccountMaxLength'] = 12; // Length for Bank account number
$eurobaustoff['grossPosting'] = true; // customer range 
$eurobaustoff['alternateSeller'] = true; // customer range 
$eurobaustoff['currency'] = "EUR"; // Default Currency 


# WWS config
$wwsClassName = "MT940_wwsFacto";
$wwsserver	= "pgsql:host=dbhost;port=5432;dbname=dbname";
$wwsuser='dbuser';
$wwspass='dbpass';
$options  = null;

$wwsAdminUsers = [ 999];

if ($_SESSION['user'] == 999) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);
}


$docpath = "./docs/";

date_default_timezone_set("Europe/Berlin");

######## menu config ##############
$menu_name['root']['Startseite']  = './home.php';
$menu_name['root']['Test']  = './test.php';
$menu_name['root']['PayPal']  = './pp_converter.php';
$menu_name['root']['Otto Payment']  = './ottopayment.php';
$menu_name['root']['Eurobaustoff']  = './eb_converter.php';
$menu_name['root']['Logout']  = './logout.php';

$menu_name['user']['Startseite']  = './home.php'; 
if (isset($_SESSION["uid"])) {
	if ($_SESSION['level'] >= 0) { $menu_name['user']['PayPal']  = './pp_converter.php'; }
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Otto Payment']  = './ottopayment.php'; }
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Eurobaustoff']  = './eb_converter.php'; }
}

$menu_name['user']['Logout']  = './logout.php';


?>
