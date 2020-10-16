<?php

foreach (glob("./intern/classes/*.php") as $filename)
{
    include_once $filename;
}
foreach (glob("../intern/classes/*.php") as $filename)
{
    include_once $filename;
}
?>