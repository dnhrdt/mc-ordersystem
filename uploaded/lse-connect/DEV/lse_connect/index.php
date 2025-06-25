<?php
/**
 * @package LSE_CONNECT

Plugin Name: Woocommerce LSE Connector
Plugin URI:
Description: LSE DB Connector & Data Exporter
Author: Christian Stanner
Version: 2.0.0
Author URI: https://christian.stanner.de
*/

include('LSE.php');
//ini_set('display_errors',1);
function LSE_export_products($shop,$type,$recent='all'){
    return LSECONNECT::export_products($shop,$type,$recent);
}
function LSE_export_prices($shop,$type){
    return LSECONNECT::export_prices($shop."_".$type);
}
function addCronJobs(): void {
    $args = array( false );
    if (!wp_next_scheduled('lse_half', $args)) {
        LSETOOLS::lse_log('WP_SCHEDULE ADDED LSE_HALF');
        wp_schedule_event(time(), 'half', 'lse_half', $args);
    }
}
function setSiteActions(): void {
    if (get_current_blog_id() === LSE::$mainSiteId) {
       add_action('init', 'addCronJobs', 99);
       add_action('lse_half', 'cron_lseHalfhourly', 100);
       add_filter('cron_schedules', 'add_lse_30');
    }
        add_action('admin_menu', 'lse_connect_menu');
    if(LSE::$ENV === 'PROD'){
        add_action('woocommerce_new_order', 'LSE_create_order', 1, 3);
        add_action('woocommerce_order_status_changed', 'LSE_create_order', 10, 3);
    }

}
add_action('init', 'setSiteActions');

function add_lse_30($schedules)
{
    $schedules['half'] = array(
        'interval' => 1800,
        'display' => __('Every 30 mins')
    );
    return $schedules;
}
function cron_lseHalfhourly(): void {
    LSECONNECT::update_woo_stock();
}
function LSE_create_order(int $postId): void {
    $shopType = LSETOOLS::getSiteTypes(get_current_blog_id());
    $order_status = LSEORDERS::create_order($postId,$shopType);
}
function lse_connect_menu(): void {
    add_menu_page( 'LSE Connect'       , 'LSE Connect' , 'manage_woocommerce'       , 'lse-woo-lse'  , 'lse_init','/wp-content/uploads/2021/06/MaisonCommonLogo-heart.svg' );
}

function lse_init() {
    echo LSE::$header;
    if(is_admin()):
        echo '<h3>Generate:</h3>';
        echo '<div><a class="button button-import" href="?page=lse-woo-lse&type=productsB2C">Products B2C</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2CRecent">Recent Products B2C</a> ';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2B">Products B2B</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2BRecent">Recent Products B2B</a> ';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsPOS">Products POS</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsPOSRecent">Recent Products POS</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=prices">Price</a> ';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=stock">Stock</a></div>';
        //echo '<div><h3>Tools</h3><a class="button button-import" href="?page=lse-woo-lse&type=fixStockStatus_B2C">Fix Stock Status EU_B2C</a>';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=fixStockStatus_B2B">Fix Stock Status EU_B2B</a></div>';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=checkValidity">Daten Prüfung</a></div>';
    endif;
    echo '<div><h3>Order an LSE senden</h3><form method="get" action=""><input type="hidden" name="page" value="lse-woo-lse"><input type="text" value="" placeholder="Woo Order ID eingeben &quot;123456&quot;" name="orderID"><input name="type" value="orderAdd" type="hidden"><select type="select" name="shopID"><option value="7">EN-B2C (ID:7)</option><option value="8">DE-B2B (ID:8)</option><option value="9" selected>DE-POS (ID:9)</option><option value="10">DE-B2C (ID:10)</option></select><input type="submit" class="button button-import" value="Order an LSE senden"></form></div>';
    echo '<div><p>Links</p><a class="button button-import" href="'.get_site_url( LSE::$mainSiteId ).'/wp-admin/admin.php?page=wc-status&tab=logs">Logs</a></div>';
    if(isset($_GET['type']) && $_GET['type']==='productsB2B'){
        echo LSECONNECT::export_products('EU',"B2B");
        echo '<br>';
       // echo LSECONNECT::export_products('US',"B2B");
    }
    /*if(isset($_GET['type']) && $_GET['type']==='productsB2BRecent'){
        echo LSECONNECT::export_products("EU","B2B","recent");
        echo '<br>';
        //echo LSECONNECT::export_products('US',"B2B","recent");
    }*/
    if(isset($_GET['type'])&& $_GET['type']==='productsB2C'){
        echo LSECONNECT::export_products('EU',"B2C");
        //echo LSECONNECT::export_products('US',"B2C");
    }
    /*if(isset($_GET['type'])&& $_GET['type']==='productsB2CRecent'){
        echo LSECONNECT::export_products("EU","B2C","recent");
        echo '<br>';
        echo LSECONNECT::export_products('US',"B2C","recent");
    }*/
    if(isset($_GET['type'])&& $_GET['type']==='productsPOS'){
        echo LSECONNECT::export_products('EU',"POS");
        echo '<br>';
        //echo LSECONNECT::export_products('US',"B2C");
    }
    /*if(isset($_GET['type'])&& $_GET['type']==='productsPOSRecent'){
        echo LSECONNECT::export_products('EU',"POS","recent");
        echo '<br>';
       // echo LSECONNECT::export_products('US',"B2C","recent");
    }*/
    if(isset($_GET['type'])&& $_GET['type']==='prices'){
        echo LSECONNECT::export_prices();
    }
    if(isset($_GET['type'])&& $_GET['type']==='pricesB2B_EUR'){
        echo LSECONNECT::export_prices('B2B_EUR');
    }
    if(isset($_GET['type'])&& $_GET['type']==='stock'){
        echo '<div>'.LSECONNECT::update_woo_stock().'</div>';
    }
    if(isset($_GET['type'])&& $_GET['type']==='woostock'){
        var_dump(LSECONNECT::get_woo_stocklevel());
    }
    if(isset($_GET['type'])&& $_GET['type']==='checkValidity'){
        echo '<h2>Prüft Datenkonsitenz:</h2>';
        echo '<p>1. LSE Daten nicht komplett:</p>';
        echo LSECONNECT::showIncompleteProducts('242');




    }
    if(isset($_GET['type'])&& $_GET['type']==='orderAdd'){
        $orderID = str_replace('KA1-','',$_GET['orderID']);
        $shopType = LSETOOLS::getSiteTypes($_GET['shopID']);
        switch_to_blog($_GET['shopID']);
        $status = LSECONNECT::create_order($orderID,$shopType);
        if(!empty($status)){
            echo '<div class="notice notice-success">'.$shopType . " Order ". $orderID . " gesendet/updated, Status ". $status."</div>";
        } else {
            echo '<div class="notice notice-error">'. $shopType . " Order ". $orderID . " Fehler, nicht vorhanden oder kein Status</div>";
        }
        restore_current_blog();
    }
    echo '';
    echo LSE::$footer;
}