<html>
<body>
<H1>EB Zahlungsavis konvertieren</h1>
<BR/>
<form action=# method=POST enctype="multipart/form-data">
Zahlungsavis: <input name="stldatei" type="file" label="EB Avis-Datei" required><BR>
Buchdatum (TT.MM.JJ): 
<?php
  if (date("N") < 4) { $time = time()+2*24*60*60; }
  if (date("N") >= 4) { $time = time()+4*24*60*60; }
?>
<input name="tag" type="text" required pattern="[0-9]{1,2}" size=2 value="<?php print(date("d",$time)); ?>"> . 
<input name="monat" type="text" required pattern="[0-9]{1,2}" size=2 value="<?php print(date("m",$time)); ?>"> . 
<input name="jahr" type="text" required pattern="[0-9]{2}" size=2 value="<?php print(date("y",$time)); ?>">
<input name="import" type="submit">
</form>
<BR>
<?php

 if ( (isset($_POST['import'])) and (is_uploaded_file($_FILES['stldatei']['tmp_name'])) ) {
	 
	$tag = preg_replace ( '/[^0-9]/i', '',$_POST["tag"]); 
	$monat = preg_replace ( '/[^0-9]/i', '',$_POST["monat"]); 
	$jahr = preg_replace ( '/[^0-9]/i', '',$_POST["jahr"]); 
	$buchdatum = sprintf("%02d%02d%02d",$jahr,$monat,$tag);
	 
	move_uploaded_file($_FILES['stldatei']['tmp_name'],$ebfilename);
	
  include_once("./config.php");

  //Facto DB Connect 
	$pg_connect = pg_connect($factopg);
	
	$datei = fopen($ebfilename,"r");
	$vonzeit = time();
	$biszeit = time();
	
		
	$row = fgets($datei, 2048);
	if (substr($row,0,2) == "10") {
			$vonzeit = substr($row,10,8);
			$biszeit = substr($row,10,8);
			$ebkonto = substr($row,31,13);
	}	

	$mt940 = "";
	$mt940 .= ":20:PP".date("ymdhis")."\n";
	$mt940 .= ":25:".$ebblz."/".$ebkonto."\n";
	$mt940 .= ":28C:0\n";
	$mt940 .= ":60F:C".$vonzeit."EUR0,00"."\n";
	$summe = 0;
	$rcnt = 0;
	$errcss = ' style="font-color:red; font-weight:heavy;" ';
	print "<table width=80%><tr><th>Belegnummer</th><th>Lieferant</th><th>EBLieferant</th><th>Buchwert</th><th>Skonto (19 / 7%)</th><th>BelegDatum</th></tr>";
	while ((($row = fgets($datei, 2048)) !== FALSE) and (substr($row,0,2) == "50")) {
		$daten = array();
		$daten["Buchdatum"] = preg_replace ( '/[^0-9\.]/i', '',substr($row,10,2).".".substr($row,12,2).".".substr($row,16,2));
		$daten["Belegdatum"] = preg_replace ( '/[^0-9]/i', '',substr($row,53,8)); 
		$daten["Beleg"] =  preg_replace ( '/[^0-9]/i', '',substr($row,42,9));
		$daten["brutto"] =  ltrim(substr($row,96,12),"0");
		$daten["vz"] =  substr($row,108,1);
		$daten["skonto"] =  ltrim(substr($row,109,12),"0");
		$daten["zahlung"] =  ltrim(substr($row,122,12),"0");
		$daten["eblieferant"] =  preg_replace ( '/[^0-9]/i', '',substr($row,153,5));
		 
		if ($daten["vz"] == "-") {
			$daten["sollhaben"] = 'C';
		} else {
			$daten["sollhaben"] = 'D';
		}
		if ($daten["vz"] == "-") {
			$daten["sohaskonto"] = 'D';
		} else {
			$daten["sohaskonto"] = 'C';
		}
		$ibqry = "select linr from public.lif_0 where qsco = '".$daten["eblieferant"]."'";
		$pg_query = pg_query($pg_connect, $ibqry);
		$liefrow = pg_fetch_object($pg_query);

		$belqry = "select fskz, sum(fprl) as wrt from archiv.fremd_pos where fnum = '".$daten["Beleg"]."' and fskz = 7 group by fskz";
		$bel_query = pg_query($pg_connect, $belqry);
		$sk7 = 0.00;
		while ($belst = pg_fetch_object($bel_query)) {
			if ($belst->fskz == 7) { 
				$sk7 = round(($belst->wrt*1.07/str_replace(",",".",$daten["brutto"]))*str_replace(",",".",$daten["skonto"]),2); }
		}
		$sk19 = str_replace(",",".",$daten["skonto"]) - $sk7;
		$sk7 = str_replace(".",",","".$sk7);
		$sk19 = str_replace(".",",","".$sk19);
		
		print "\n<tr>";
		//Zahlung Brutto
		$mt940 .= ":61:".$buchdatum;
		$mt940 .= $daten["sollhaben"];
		//$mt940 .= $daten["zahlung"];
		$mt940 .= $daten["brutto"];
		$mt940 .= "NDDT".$daten["Beleg"]."\n";
		$mt940 .= ":86:166?00EBAVIS vom ".$daten["Buchdatum"]." ".$daten["Beleg"]."\n";
		$mt940 .= "?20LI".$liefrow->linr." \n";
		$mt940 .= "?21FB".$daten["Beleg"]." \n";
		$mt940 .= "?22BR".$daten["brutto"]."\n";
		$mt940 .= "?23SK".$daten["skonto"]."\n"; 
		//$mt940 .= ":86:166?00EB".$liefrow->linr."_".$daten["Beleg"]."\n";
		//$mt940 .= "?20EREF+".$daten["Beleg"]."\n";
		
		print "<td>".$daten["Beleg"]."</td><td>".$liefrow->linr."</td><td>".$daten["eblieferant"]."</td>";
		print "<td>".$daten["sollhaben"]." ".sprintf("%01.2f",str_replace(",",".",$daten["brutto"]))." € </td>";
		print "<td>".$sk19." / ".$sk7." </td>";
		print "<td>".$daten["Belegdatum"]." </td>";
		print "</tr>";

		//Buchung Skonto
		if (str_replace(",",".",$sk19) > 0) {
			$mt940 .= ":61:".$buchdatum;
			$mt940 .= $daten["sohaskonto"];
			//$mt940 .= $daten["zahlung"];
			$mt940 .= $sk19;
			$mt940 .= "NDDTSKONTO19BUCHUNG\n";
			$mt940 .= ":86:166?00SKONTO19% fuer ".$daten["Beleg"]."\n";
			$mt940 .= "?20LI".$liefrow->linr." \n";
			$mt940 .= "?21FB".$daten["Beleg"]."\n";
			//$mt940 .= "?22BR".$daten["brutto"]."_\n";
			//$mt940 .= "?23SK".$daten["skonto"]."\n"; 
			//$mt940 .= ":86:166?00EB".$liefrow->linr."_".$daten["Beleg"]."\n";
			//$mt940 .= "?20EREF+".$daten["Beleg"]."\n";
		}
		if (str_replace(",",".",$sk7) > 0) {
			$mt940 .= ":61:".$buchdatum;
			$mt940 .= $daten["sohaskonto"];
			//$mt940 .= $daten["zahlung"];
			$mt940 .= $sk7;
			$mt940 .= "NDDTSKONTO7BUCHUNG\n";
			$mt940 .= ":86:166?00SKONTO7% fuer ".$daten["Beleg"]."\n";
			$mt940 .= "?20LI".$liefrow->linr." \n";
			$mt940 .= "?21FB".$daten["Beleg"]."\n";
			//$mt940 .= "?22BR".$daten["brutto"]."_\n";
			//$mt940 .= "?23SK".$daten["skonto"]."\n"; 
			//$mt940 .= ":86:166?00EB".$liefrow->linr."_".$daten["Beleg"]."\n";
			//$mt940 .= "?20EREF+".$daten["Beleg"]."\n";
		}
	}

	if (substr($row,0,2) == "90") {
		$summe = substr($row,96,12) * 1;
		$svz = substr($row,108,1);
	}	
	
	while ((($row = fgets($datei, 2048)) !== FALSE) and (substr($row,0,2) != "90")) {
			if (substr($row,0,2) == "90") {
				$summe = substr($row,96,12) * 1;
				$svz = substr($row,108,1);
			}	
	}
	// negiert da gegenbuchung, wenn als Summe wendet wird, umdrehen
	if ($svz == "-") {
		$svz = 'D';
	} else {
		$svz = 'C';
	}
	$mt940 .= ":62F:".$svz.$vonzeit."EUR".$summe."\n";

  $mt940 .= "-\n";
  fclose($datei);
  
	$mt940datei = "./docs/EBAVIS".date("ymdhis").".pcc";
	file_put_contents($mt940datei, $mt940); 
	print "<BR><h3><a href='".$basepath.$mt940datei."'>[Download MT940 Datei]</a></h3><BR></table>";
 
 }

?>
</body>
</html>
