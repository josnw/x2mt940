<?php
ini_set('session.gc_maxlifetime', 36000);
session_set_cookie_params(36000);session_start();
 include_once './intern/autoload.php';
?>
<!DOCTYPE html>
<html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="./css/master.css" media=screen>
<link rel="stylesheet" type="text/css" href="./css/master5print.css" media=print>

<script src="./js/scripts.js" type="text/javascript"></script>
	
<title>Konverter x2mt940</title>
</head>
<body >

<header>
	x2MT940 Konverter
    <a href="index.php" title="Zur Startseite"><img src="./css/logo.png"></a>
</header>
<?php
include_once './intern/config.php';
 if ( (file_exists('./intern/config.json')) or (array_key_exists('PHP_AUTH_USER', $_SERVER) and ($strlen($_SERVER['PHP_AUTH_USER'] > 0))) ) {
	include_once './intern/auth.php';
} else {
	$_SESSION['typ'] = 'user';
	$_SESSION["uid"] = 0;
	$_SESSION['level'] = '1';
}
if (!empty($_SESSION['typ'])) {
	$usertyp = $_SESSION['typ'];
} else {
	$usertyp = 'logout';
}

#if ( $_SESSION['penr'] <> '999') {
# print "<BR><error>Wegen Wartungsarbeiten geschlossen!</error>";
# exit;
#}

print "<nav>\n";
print "<div class=navinfo>Sie sind eingelogt als:<BR>".$_SESSION['name']." (".$usertyp." L".$_SESSION['level'].")</div>";
print "<ul>\n";
$aktiv = '';
foreach($menu_name[$usertyp] as $menu => $file) {
	if (isset($_GET['menu']) and ($menu == $_GET['menu']) ) { $aktiv='"aktiv"'; } else { $aktiv = '""'; }
	print "<li>";
	print "<a class= ".$aktiv." href=\"./index.php?menu=".$menu."\" >".$menu."</a>\n";
	print "</li>";
};
print "</ul>\n";
print "</nav>\n";

print "<main>";
if (isset($_GET['menu']) and strlen($_GET['menu']) > 0) {
   foreach($menu_name[$usertyp] as $menu => $file) {
	   if ($menu == $_GET['menu']) {
		  include './intern/'.$file;
		  //Proto("Menüpunkt ".$file." gestartet. (".$_SERVER['REMOTE_ADDR'].")");
	   };
	}; 
} else {
	 include './intern/home.php';
}
?>
</main>

<footer>
<div id="wait"><div id="waittext"><BR><BR><BR>Bitte warten...<BR>Die Daten werden verarbeitet!<BR></div></div>
<div id="infobox">...</div>
<div><?php print date("Y-m-d h:i"); ?></div>
</footer>
</body>
</html>
