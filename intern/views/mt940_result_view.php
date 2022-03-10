<div class="DSEdit">
	<div class="DSFeld4 smallBorder">
		<?php

		print $rowCount." Datensätze exportiert!<br/>";
		print "<a href=".$exportfile.">[Download ".$filename."]</a>";

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
					if (is_array($value)) {
						$value = print_r($value,1);
					}
					print "<td>".$value."</td>";
				}
				print "</tr>";
			}
		?>
	</table>
</div>