<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="./css/master.css" media=screen>
	
<title>Konverter x2mt940</title>
</head>
<body >

<header>
	x2MT940 Konverter
    <a href="index.php" title="Zur Startseite"><img src="./css/logo.png"></a>
</header>
<main>
<h2>WWS/ERP Datenbank Setup </h2>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld4 ">
			<div class="DSFeld4 ">
					Datenbank Host IP: <br/><input name=wwsServerHost type=text pattern="^[0-9A-Za-z\-\._]{3;50}$">
			</div>
			<div class="DSFeld4 ">
					Datenbank Host Port: <br/><input name=wwsServerPort type=text value="5432"  pattern="^[0-9]{1;6}$">
			</div>
			<div class="DSFeld4 ">
					Datenbank Name: <br/><input name=wwsDBName type=text pattern="^[0-9A-Za-z\-_]{1;20}$">
			</div>
		</div>
		<div class="DSFeld4 smallBorder">
			<div class="DSFeld4 ">
					Datenbank Admin: <br/><input name=wwsAdmin type=text value="postgres"  pattern="^[0-9A-Za-z\-\._]{1,20}$">
			</div>
			<div class="DSFeld4 ">
					Admin Password: <br/><input name=wwsAdminPassword type=password minlength=1>
			</div>
		</div>
		<div class="DSFeld4 smallBorder">
			<div class="DSFeld4 ">
					neuer Datenbank User: <br/><input name=wwsUser type=text  pattern="[0-9A-Za-z\-\.\_]{3,20}">
			</div>
			<div class="DSFeld4 ">
					User Password: <br/><input name=wwsUserPassword type=password minlength=8>
			</div>
		</div>
		<div class="DSFeld1 right" style="background: #AA5555;"><input type="submit" name="setup" value="SetUp Database" onclick="wartemal('on')"></div>
	</div>
</form>
