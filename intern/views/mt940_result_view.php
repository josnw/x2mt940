<div class="DSEdit">
	<div class="DSFeld4 smallBorder">
		<?php

		print $rowCount." Datensätze exportiert!<br/>";
		print "<a href=".$exportfile.">[Download ".$filename."]</a>";
		if (!empty($importerrors)) {
			print "<error>".$importerrors."</error>";
		}

		?>
	</div>
</div>
<div class="DSEdit">
	<table class="kaltab">
		<?php
			$cnt = 0;
			foreach($result as $row) {
				if (!$cnt++) {
					print "<tr>";
					foreach(array_keys($row) as $key) {
						print "<th>".str_replace("_"," ",$key)."</th>";
					}
					print "</tr>";
				}
				
				print "<tr>";
				foreach($row as $value) {
					print "<td>".$value."</td>";
				}
				print "</tr>";
			}
		?>
	</table>
</div>