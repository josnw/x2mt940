<?php

#include_once './intern/auth.php';

$_SESSION['typ'] = 'ro';
$_SESSION['level'] = '0';
$_SESSION['user'] = '';
$_SESSION['name'] = '<B>Zum Bearbeiten bitte mit Factodaten einloggen!</B>';
$_SESSION['uid'] = 0;
setcookie("scandesk", base64_encode(serialize($_SESSION)), time());
session_destroy();

print "Sie sind ausgeloggt!<BR>";

#print <<<END

#<center>
#<form action="index.php" method="POST" target="_top">
#    <table id="tb2" width="300" height="">
#     <tr height="25">
#      <td>Username:</td>
#      <td><input type="TEXT" name="user"></td>
#     </tr>
#     <tr height="25">
#      <td>Passwort</td>
#      <td><input type="PASSWORD" name="passwort"></td>
#     </tr>
#    </table>
#    <input type="submit" value="Login"
#</form>
#</center>

#END;

include './intern/auth.php';

?>