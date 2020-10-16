

<h2>Updateinfos</h2>
<div class="DSEdit">

<table width=70% border=0>
<?php

 include './intern/autoload.php';
 include ("./intern/config.php");
 
 $updInfo = new myFile("./intern/updateinfo.txt","read");
 
 while($line = $updInfo->readCSV()) {
	if( strtotime($line[0]) > (time()-60*60*24*30)) {
		print "<tr>";
		print "<td valign=top><br/>".$line[0]."</td>";
		print "<td valign=top><h4>".$line[1]."</h4>";
		print $line[2]."</td>";
		print "</tr>";
	}
  }
  
  $updInfo->close(); 
 ?>
 </table>
 
 </div>