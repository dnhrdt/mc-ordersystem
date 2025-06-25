<?php
class LSE{
    public static string $ENV     = 'DEV';
    public static string $server_name     = 'DEV.MAISONCOMMON.COM';
    public static string $CSV_DL_PATH   = "cloud.maisoncommon.de/remote.php/webdav/importe_de/";
    public static string $CSV_LOCAL_PATH   = "/chroot/home/DEV/importe_de/";
    public static string $mc_user_pass = 'MCImport:dfj4JFd4fe.GD4';
    public static int $mainSiteId = 10;

    public static array $site = [
        7 => 'B2C', //EU shop
        8 => 'B2B', //EU B2B
        9 => 'POS',//DE POS
        10 => 'B2C']; //DE B2C

    public static string $header = '<h1>LSE Connector</h1><table class="form-table" role="presentation"><tr><th scope="row"></th><td>';
    public static string $footer = '</td></tr></table>';
    public static bool $LSE_UPDATE_IS_RUNNING = false;
}
include('classes/LSECONNECT.class.php');
include('classes/LSEDATA.class.php');
include('classes/LSEORDERS.class.php');
include('classes/LSEPRODUCTS.class.php');
include('classes/LSETOOLS.class.php');