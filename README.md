# x2mt940
Modul to convert textfiles from some payment provider to MT940 format
erp connector for check reference code

#### paypal transaction files
 * supports paypal transaction (ttr) files oder csv files from paypal portal
 * charge as extra posting
 * erp connector modul to add addional data in mt940
 * mapping file for german table header and easy change for other languages
 
#### eb cooperation avis  
 * erp connector modul to add addional data in mt940
 * discount as extra posting
 
#### otto payment files
 * supports paypal transaction (ttr) files oder csv files from paypal portal
 * charge as extra posting
 * erp connector modul to add addional data in mt940
 * mapping file for german table header and easy change for other languages
 
#### real payment files
 * supports paypal transaction (ttr) files oder csv files from paypal portal
 * charge as extra posting
 * erp connector modul to add addional data in mt940
 * mapping file for german table header and easy change for other languages

## Setup

 * download the repo 
 * copy intern/config.sample.php to intern/config.php 
 * edit mt904 parameter in config.php
 
 If you want add an erp connection to get reference codes i.g. your internal invoice number, make a copy of intern/classes/mt940_dummy_erp_class.php.
 Add your code for database or api requests and fill the return array.
 
 If you use the ERP Software Facto 5.x you can use mt940_wwsFacto_class.php and start the setup.php for Database connection. 
