<?php
$LSE_server = 'MAISONCOMMON.COM';
$LSE_savePath = "cloud.maisoncommon.de/remote.php/webdav/importe_de/";
$LSE_tempPath = "/chroot/home/maisonde/importe_de/";
$LSE_mc_user_pass = 'MCImport:dfj4JFd4fe.GD4';
$mainSiteId = 10;
$fileMaxAge = 240; //max age of generated export files in minutes.
$site = array(); //Shop Sites:
$site[7]='B2C'; //EU shop
$site[8]='B2B'; //EU B2B
$site[9]='POS'; //DE POS
$site[10]='B2C'; //DE B2C

$header = '<h1>LSE Connector '.$LSE_server.'</h1><table class="form-table" role="presentation"><tr><th scope="row"></th><td>';
$footer = '</td></tr></table>';
$LSE_UPDATE_IS_RUNNING = false;