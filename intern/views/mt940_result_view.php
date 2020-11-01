<div class="DSEdit">
	<div class="DSFeld4 smallBorder">
		<?php

		print $rowCount." Datensätze exportiert!<br/>";
		print "<a href=".$exportfile.">[Download ".$filename."]</a>";

		?>
	</div>
</div>
<div class="DSEdit">
	<table width=90%>
		<?php
			$cnt = 0;
			foreach($result as $row) {
				if (!$cnt++) {
					print "<tr>";
					foreach(array_keys($row) as $key) {
						print "<th>".$key."</th>";
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