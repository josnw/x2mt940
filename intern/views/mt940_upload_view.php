<h2><?php print $konverterName; ?> konvertieren</h2>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
<?php 
	if ( $fileVar == 'ebavis') { print '
		<div class="DSFeld4 smallBorder">
				Buchdatum: <br/><input name="paymentDate" value="'.$paymentDate.'"type=date>
		</div>
		'; 
	}
?>
		<div class="DSFeld4 smallBorder">
				Datei bitte auswählen: <br/><input name="<?php print $fileVar; ?>" type=file>
		</div>
		<div class="DSFeld1 right" style="background: #AA5555;"><input type="submit" name="uploadFile" value="Upload" onclick="wartemal('on')"></div>
	</div>
</form>