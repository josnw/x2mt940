# x2mt940
Web App to converts textfiles from differnet payment provider to MT940 format
erp connector for add a reference code

#### paypal transaction files
 * supports paypal transaction (ttr) files oder csv files from paypal portal
 * charge as extra posting
 * erp connector modul to add addional data in mt940
 * mapping file for german table header included
 
#### eb cooperation avis  
 * erp connector modul to add addional data in mt940
 * cash discount as extra posting
 
#### otto.de payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940

#### real.de payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940

#### adyen / ebay payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940

#### amazon payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940

#### check24 payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940

#### idealo payment files
 * charge as extra posting
 * erp connector modul to add addional data in mt940



## Setup

 * download the repo 
 * copy intern/config.sample.php to intern/config.php 
 * edit mt904 parameters in config.php
 * optionaly add .htpasswd and .htaccess for user auth

In the directory intern/mapping/ you will find mapping files for different file header. Maybe, you must modify it for your own language. 
 
 If you want add an erp connection to get a reference codes ( e.g. an internal invoice number), make a copy of intern/classes/mt940_dummy_erp_class.php.
 Add your code for database or api requests and modify the return array.
 
 If you use the ERP Software Facto 5.x you can use mt940_wwsFacto_class.php and user auth from Facto Database. Just start the setup.php for Database connection. 
