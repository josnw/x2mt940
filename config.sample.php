<?php

$ebfilename = "./docs/ZAVIS.DAT";
$ebblz = "DUMMYBLZ4EB";
$ebkonto = "DUMMYKONTO4EB";

$basepath="http://yourintranet/converter/";
$factopg="host='IPADDRESS SQL SERVER' port='5432' dbname='FACTO5-001' user='guest' password='XXXXXX'";

$docpath = "./docs/";

date_default_timezone_set("Europe/Berlin");

######## Menüeinträge ##############
$menu_name['user'][1] = 'EB 2 MT940';


######## spezifische Dateien ###########
$menu_file['user'][1] = './eb_mt940.php';

?>
