<?php
/**
 * @package LSE_CONNECT
 * @var $LSE_server
Plugin Name: Woocommerce LSE Connector
Plugin URI:
Description: LSE DB Connector & Data Exporter
Author: Christian Stanner
Version: 1.1.0
Author URI: https://christian.stanner.de
*/

include('config.php');
$header = '<h1>LSE Connector</h1><h2>'.$LSE_server.'</h2><table class="form-table" role="presentation"><tr><th scope="row"></th><td>';
$footer = '</td></tr></table>';

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
    global $mainSiteId;
    if (get_current_blog_id() === $mainSiteId) {
       add_action('init', 'addCronJobs', 99);
       add_action('lse_half', 'cron_lseHalfhourly', 100);
       add_filter('cron_schedules', 'add_lse_30');
    }
    add_action('admin_menu', 'lse_connect_menu');
    add_action('woocommerce_new_order', 'LSE_create_order', 1, 3);
    add_action('woocommerce_order_status_changed', 'LSE_create_order', 10, 3);
    //add_action('save_post_shop_order', 'LSE_create_order', 10, 3);
    //add_action('woocommerce_payment_complete', 'LSE_create_order', 10, 3);
}
add_action('init', 'setSiteActions');
add_action( 'rest_api_init', static function(){
    register_rest_route( 'lse/v1', '/check', array(
        'methods' => 'GET',
        'callback' => 'returnLSESBStatus',
        'permission_callback' => '__return_true'
    ));
});
function returnLSESBStatus() {
    echo LSETOOLS::getDBStatus();
}
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

    global $header,$footer;
    echo $header;
    echo '<h3>LSE to WOO and back.</h3>';
    if(is_admin()):
        echo '<h3>Generate:</h3>';
        echo '<div><a class="button button-import" href="?page=lse-woo-lse&type=productsB2C">Products B2C</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2CRecent">Recent Products B2C</a> ';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2B">Products B2B</a> ';
        //echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsB2BRecent">Recent Products B2B</a> ';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsPOS">Products POS</a> ';
       //  echo '<a class="button button-import" href="?page=lse-woo-lse&type=productsPOSRecent">Recent Products POS</a> ';
       // echo '<a class="button button-import" href="?page=lse-woo-lse&type=prices">Price</a> ';
       // echo '<a class="button button-import" href="?page=lse-woo-lse&type=stock">Stock</a></div>';
        echo '<div><h3>Tools</h3><a class="button button-import" href="?page=lse-woo-lse&type=fixStockStatus_B2C">Fix Stock Status EU_B2C</a>';
        echo '<a class="button button-import" href="?page=lse-woo-lse&type=fixStockStatus_B2B">Fix Stock Status EU_B2B</a></div>';
    endif;
    echo '<div><h3>Order an LSE senden</h3><form method="get" action=""><input type="hidden" name="page" value="lse-woo-lse"><input type="text" value="" placeholder="Woo Order ID eingeben &quot;123456&quot;" name="orderID"><input name="type" value="orderAdd" type="hidden"><select type="select" name="shopID"><option value="7">EN-B2C (ID:7)</option><option value="8">DE-B2B (ID:8)</option><option value="9" selected>DE-POS (ID:9)</option><option value="10">DE-B2C (ID:10)</option></select><input type="submit" class="button button-import" value="Order an LSE senden"></form></div>';
    echo '<div><p>Links</p><a class="button button-import" href="https://shop.maisoncommon.com/de/wp-admin/admin.php?page=wc-status&tab=logs">Logs</a></div>';
    if(isset($_GET['type']) && $_GET['type']==='productsB2B'){
        echo LSECONNECT::export_products('EU',"B2B");
        //echo '<br>';
        //echo LSECONNECT::export_products('US',"B2B");
    }
    if(isset($_GET['type']) && $_GET['type']==='productsB2BRecent'){
        echo LSECONNECT::export_products("EU","B2B","recent");
        //echo '<br>';
        //echo LSECONNECT::export_products('US',"B2B","recent");
    }
    if(isset($_GET['type'])&& $_GET['type']==='productsB2C'){
        echo LSECONNECT::export_products('EU',"B2C");
       // echo LSECONNECT::export_products('US',"B2C");
    }
    if(isset($_GET['type'])&& $_GET['type']==='productsB2CRecent'){
        echo LSECONNECT::export_products("EU","B2C","recent");
        //echo '<br>';
       // echo LSECONNECT::export_products('US',"B2C","recent");
    }
    if(isset($_GET['type'])&& $_GET['type']==='productsPOS'){
        echo LSECONNECT::export_products('EU',"POS");
        //echo '<br>';
       // echo LSECONNECT::export_products('US',"B2C");
    }
    if(isset($_GET['type'])&& $_GET['type']==='productsPOSRecent'){
        echo LSECONNECT::export_products('EU',"POS","recent");
        // echo '<br>';
        //echo LSECONNECT::export_products('US',"B2C","recent");
    }
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
    echo $footer;
}

class LSECONNECT {
    public static function get_woo_stocklevel() {
        $args = array(
            'post_type'			=> 'product',
            'post_status' 		=> 'publish',
            'posts_per_page' 	=> -1,
            'orderby'			=> 'title',
            'order'				=> 'ASC',
            'meta_query' 		=> array(
                array(
                    'key' 	=> '_manage_stock',
                    'value' => 'yes'
                )
            ),
            'tax_query' => array(
                array(
                    'taxonomy' 	=> 'product_type',
                    'field' 	=> 'slug',
                    'terms' 	=> array('simple'),
                    'operator' 	=> 'IN'
                )
            )
        );
        $loop = new WP_Query( $args );
        $return = array();
        while ( $loop->have_posts() ) : $loop->the_post();
            global $product;
            //$row = array( $product->get_sku(), $product->stock );
            $return[$product->get_sku()]['stock']=$product->stock;
            $return[$product->get_sku()]['id']=$product->get_id();
        endwhile;
        $args = array(
            'post_type'			=> 'product_variation',
            'post_status' 		=> 'publish',
            'posts_per_page' 	=> -1,
            'orderby'			=> 'title',
            'order'				=> 'ASC',
            'meta_query' => array(
                array(
                    'key' 		=> '_stock',
                    'value' 	=> array('', false, null),
                    'compare' 	=> 'NOT IN'
                )
            )
        );
        $loop = new WP_Query( $args );
        while ( $loop->have_posts() ) : $loop->the_post();
            $product = new WC_Product_Variation( $loop->post->ID );
            $return[$product->get_sku()]['stock']=$product->stock;
            $return[$product->get_sku()]['id']=$loop->post->ID;
        endwhile;
        return $return;
    }
    public static function export_products($shop,$type,$set="all"): string
    {
        global $fileMaxAge;
        $log = 'PRODUCT CSV '.$shop.'_'.$type.($set === "all"?'':'_'.$set);
        $timeStart = time();
        //ini_set('display_errors',1);
        global $LSE_savePath, $LSE_tempPath, $LSE_mc_user_pass;
        $filename = 'products_' . $shop . '_' . $type . ($set === "all" ? '' : '_' . $set) . '.csv';
        $csvFile = $LSE_tempPath . $filename;
        $filectime = filectime($csvFile);
        $file_age=round(($timeStart-$filectime)/60,0);
        //$log .= "[".$timeStart."]-[" .$filectime. "]=[". var_export($file_age,true)."]";
        if($file_age>$fileMaxAge||!file_exists($csvFile)){
            $log.=" New file ";
            $minusDays = 30;
            $dateTime = mktime(0, 0, 0, date("m"), date("d"), date("Y")) - ($minusDays * 86400);
            $date = date('Y-m-d\Th:i:s', $dateTime);
            $products = LSEPRODUCTS::getLSE($shop, $type, $set, $date);
            $priceList = LSETOOLS::getPriceListType($shop, $type);
            $prices = LSEPRODUCTS::getLSEPrices($priceList);
            $file = fopen($csvFile, 'wb');
            $productCounter = 0;
            $collectionItemCount = 0;
            $variationCounter = 0;
            $previousID = 0;
            $prevCollection = '';
            $csvHeader = "SortierDatum,Recent,Aenderungsdatum,Artikel_Artikelgruppe_Bezeichnung,Artikel_Artikelgruppe_Bezeichnung_DE,Artikel_Artikelgruppe_Nr,Artikel_Artikelgruppe_S1_Bezeichnung,Artikel_Artikelgruppe_S1_Nr,Artikel_Artikelgruppe_S2_Bezeichnung,Artikel_Artikelgruppe_S2_Nr,Artikel_Artikelgruppe_S3_Bezeichnung,Artikel_Artikelgruppe_S3_Nr,Artikel_Artikelgruppe_S4_Bezeichnung,Artikel_Artikelgruppe_S4_Nr,Artikel_Artikelgruppe_S5_Bezeichnung,Artikel_Artikelgruppe_S5_Nr,Artikel_Artikelnr,Artikel_Bezeichnung,Artikel_EAN,Artikel_Einheit,Artikel_Farbe,Artikel_Farbschema,Artikel_Farbtext,Artikel_FutterKennzeichen,Artikel_FutterKennzeichen_DE,Artikel_Gewicht,Artikel_Groesse,Product_type,Artikel_ID,Bild_ID,Artikel_Kategorie_ID,Artikel_Kollektion,Artikel_Kollektion_Bezeichnung,Artikel_Kurztext,Artikel_Kurztext_EN,Artikel_Laenge,Artikel_Lieferstatus,Artikel_MaterialKennziffer,Artikel_MaterialKennziffer_DE,Artikel_Menge,Artikel_MetaDescription,Artikel_MetaKeywords,Artikel_MetaTitle,Artikel_Pflegesymbole,Artikel_Sortierung,Artikel_Startseite,Artikel_Status,Artikel_Steuersatz,Artikel_Text,Artikel_Text_en,Artikel_Text_fr,Artikel_TextLanguage,Artikel_Ursprungsland,Artikel_Variante_Von,Artikel_Warennummer,Artikel_Warennummer_Beschreibung,Artikel_Menge_Lager_0,stock_status,allow_backorder,lager_last_changed,manage_inventory,Artikel_Preis";
            fputcsv($file, explode(',', $csvHeader));
            fputcsv($file, array('','','','','','','','','','','','','','','','',date('Y.m.d H:i:s'),'','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','','',''));
            foreach ($products as $key => $productRow) {
                $currentCollection = $productRow['Artikel_Kollektion'];
                $currentID = $productRow['Artikel_ID'];
                $productRow['Artikel_Preis'] = LSETOOLS::findPriceByArtikleNr($prices, $productRow['Artikel_Artikelnr']);
                if ($currentID !== $previousID) {
                    $productCounter++;
                    $productRow['stockstatus'] = '';
                    $sortDate = LSETOOLS::get_sort_date_by_productID($productRow['Artikel_ID'],$productCounter,$collectionItemCount);
                    fputcsv($file, LSEDATA::makeVariation($productRow,$sortDate,true,$products,$key,$collectionItemCount,$productCounter));
                    if (strtoupper($productRow['Artikel_Groesse']) === 'PCS') {
                        //skip variation if size = pcs,
                        continue;
                    }
                    //First Variation:
                    fputcsv($file, LSEDATA::makeVariation($productRow,$sortDate));
                    $variationCounter++;
                } else {
                    $variationCounter++;
                    fputcsv($file, LSEDATA::makeVariation($productRow,$sortDate));
                }
                $previousID = $productRow['Artikel_ID'];
                $previousSortDate = $sortDate;
                $collectionItemCount = $prevCollection === $currentCollection ? $collectionItemCount + 1 : 0;
                $prevCollection = $currentCollection;
            }
            fclose($file);
        }else{
            $log.=" Skip file gen ".$file_age."min-";
        }
        $timeDiff = time() - $timeStart;
        $dlPath = "https://".$LSE_mc_user_pass."@".$LSE_savePath.$filename;
        LSETOOLS::lse_log( $log." (".$timeDiff."sec) ".$filename);
        if($timeDiff>179){
            wp_mail("monitoring@deinhardt.com", $shop . " MC File Export ". $filename, $log." (".$timeDiff."sec) ".$filename);
        }
        return $dlPath;
    }
    public static function export_stock() : string {
        global $savePath,$tempPath;
        $wooStock = self::get_woo_stocklevel();
        $stockFile = $tempPath.'stock.csv';
        $useDBcall = (time() - filemtime($stockFile) > 900) ;
        if($useDBcall){
            $products = LSEPRODUCTS::getLSEStock();
        } else {
            /* Map Rows and Loop Through Them */
            $rows   = array_map('str_getcsv', file($stockFile));
            $header = array_shift($rows);
            $products    = array();
            foreach($rows as $row) {
                $products[] = array_combine($header, $row);
            }
        }

        $filepath = $tempPath.'stock.csv';
        $file = fopen($filepath, 'wb');
        foreach($products as $key=>$productRow){
            if($key === 0){
                fputcsv($file,array_keys($productRow));
            }
            $stock = (int)$productRow['Artikel_Menge_Lager_0'];
            if(isset($wooStock[$productRow['Artikel_Artikelnr']])
                && (int)$wooStock[$productRow['Artikel_Artikelnr']]['stock'] !== $stock
            ){
                fputcsv($file,array($productRow['Artikel_Artikelnr'],$stock));
            }
        }
        fclose($file);
        return true; //LSETOOLS::sendCurl($savePath.'stock.csv',$filepath);
    }
    public static function update_woo_stock() : string {
        $memory = 0;
        $hour = (int) date('G');
        if(($hour>=21 && $hour<=23 )||($hour>=0 && $hour<8)){
            LSETOOLS::lse_log("LSE_UPDATE Skipping in hour ".$hour);
            return false;
        }
        global $LSE_UPDATE_IS_RUNNING;
        if($LSE_UPDATE_IS_RUNNING){
            LSETOOLS::lse_log("LSE_UPDATE already running");
            return false;
        }
        $LSE_UPDATE_IS_RUNNING = true;
        $timeStart = time();
        $products = LSEPRODUCTS::getLSEStock(true);
        $sites = get_sites();
        $site = LSETOOlS::getSiteTypes();

        $stores = array();
        $allCounts = 0;
        $log = '';
        foreach ( $sites as $site_ID ) {
            $stores[] = $site_ID->blog_id;
        }

        foreach($stores as $store_id ){
            switch_to_blog( $store_id );
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
                continue;
            }
            if(function_exists('wp_all_import_get_import_id')) {
                $importID = wp_all_import_get_import_id();
                if ($importID) {
                    LSETOOLS::lse_log("Aborting Stock_update " . $site[$store_id] . " import " . $importID . " is running");
                }
            }
            $memory = memory_get_peak_usage(true)/1024/1024/1024;
            $wooStock = self::get_woo_stocklevel();
            if(count($wooStock)===0){
                LSETOOLS::lse_log( "No woo stock for site  stock level for:" .$site[$store_id],'error');
                continue;
            }
            $log.= "[SHOP:".$site[$store_id].": ";
            $stockstatusUpdate = array();
            $preOrderUpdate = array();
            $count = 0;
            $preOrderCount = 0;
            $shopProdCount = 0;
            foreach($products as $productRow){
                //$variation_id = wc_get_product_id_by_sku($productRow['Artikel_Artikelnr']);
                $LSE_SKU =  $productRow['Artikel_Artikelnr'];
                $variation_id = $wooStock[$LSE_SKU]['id'];
                $LSE_Stock = (int)$productRow['Artikel_Menge_Lager_0'];
                if(isset($wooStock[$productRow['Artikel_Artikelnr']])){
                    $variation_id = $wooStock[$productRow['Artikel_Artikelnr']]['id'];
                    $shopProdCount ++;
                    if($productRow['Artikel_Lieferstatus'] === '1'){
                        // PreOrder Product:
                        $product = wc_get_product($variation_id);
                        $status = 'onbackorder';
                        $wooStockStatus = $product->get_stock_status();
                        if($wooStockStatus !== $status){
                            $product->set_stock_status($status);
                            if($product->backorders_allowed() === false){
                                $product->set_backorders('yes');
                            }
                            $product->save();
                            $newId = wp_get_post_parent_id($variation_id);
                            $product_id = $newId === 0 ? $variation_id : $newId;
                            $preOrderUpdate[$product_id]=$variation_id;
                            $log .=  $LSE_SKU . " ". $wooStockStatus . "->".$status." | " ;
                            $preOrderCount ++;
                        }
                    } else if( (int)$wooStock[$productRow['Artikel_Artikelnr']]['stock'] !== $LSE_Stock ) {
                        $newStock = wc_update_product_stock($variation_id, $LSE_Stock);
                        $status = $newStock <= 0  ? 'outofstock':'instock';
                        wc_update_product_stock_status($variation_id,$status);
                        $newId = wp_get_post_parent_id($variation_id);
                        $product_id = $newId === 0 ? $variation_id : $newId;
                        $stockstatusUpdate[$product_id]=$variation_id;
                        $log .=  $productRow['Artikel_Artikelnr'] . ": " .(int)$wooStock[$productRow['Artikel_Artikelnr']]['stock']."->".$newStock." |" ;
                        $count ++;
                    }
                    $product = null;
                }
            }
            //var_dump($stockstatusUpdate);
            foreach($stockstatusUpdate as $keyId => $value){
                $quantity = LSETOOLS::get_total_combined_stock_quantity($keyId);
                $status = $quantity <= 0  ? 'outofstock':'instock';
                wc_update_product_stock_status($keyId,$status);
                $product = wc_get_product( $keyId);
                if($product->backorders_allowed() === true){
                    $product->set_backorders('no');
                }
                //$log .='Update product:' . $keyId .'->'.$quantity.' - '. $status . ' - ' . wc_update_product_stock_status($keyId,$status).'|';
                $product = null;
            }
            foreach($preOrderUpdate as $keyId => $value){
                $status = 'onbackorder';
                wc_update_product_stock_status($keyId,$status);
                $product = wc_get_product($keyId);
                if($product->backorders_allowed() === false){
                    $product->set_backorders('yes');
                }
                $product = null;
            }
            reset($products);
            $log .= $shopProdCount. " variations in store /  Updated:".count($stockstatusUpdate)."/".$count." prods/vars,".count($preOrderUpdate). "/".$preOrderCount." pre-order Prods/Vars]\n";
            $allCounts += $count;
            //unset($wooStock, $stockstatusUpdate, $preOrderUpdate);
            $wooStock = null;
            $stockstatusUpdate = null;
            $preOrderUpdate = null;
            $memNow = (memory_get_peak_usage(true)/1024/1024/1024);
            echo "END : " .$memNow . "GB ( " .( $memNow - $memory )."GB in loop)<hr>";
        }
        restore_current_blog();
        $timeDiff = time() - $timeStart;
        if($allCounts > 0){
            LSETOOLS::lse_log( "Updated stock level for: " . $allCounts . " products(".($timeDiff) ."sec):\n$log");
        } else {
            LSETOOLS::lse_log( "No Stock Updates(".($timeDiff) ."sec)");
        }
        $LSE_UPDATE_IS_RUNNING = false;
        return $allCounts . ' products Stock updated. Took '.($timeDiff) .'sec ' .$log;
    }
    public static function export_prices($type=''): string {
        global $LSE_mc_user_pass,$LSE_savePath,$LSE_tempPath;
        $preisliste[0]='B2B_EUR';
        $preisliste[1]='B2B_USD';
        $preisliste[2]='B2B_INT';
        $preisliste[3]='B2C_EUR';
        $preisliste[4]='B2C_USD';
        $return = '';
        if($type===''){
            foreach($preisliste as $key=>$value){
                $products = LSEPRODUCTS::getLSEPrices($value);
                $filepath = $LSE_tempPath.'prices_'.$value.'.csv';
                $file = fopen($filepath, 'wb');
                $csvHeader   = "Artikel_Artikelnr,Artikel_ID,Artikel_EAN,Artikel_Preis";
                fputcsv($file, explode(',' , $csvHeader));
                //reset($products);
                foreach($products as $productRow){
                    fputcsv($file, $productRow);
                }
                fclose($file);
                $return .= $value."|";
            }
            LSETOOLS::lse_log( "PRICES generated ". $return);
        } else {
            $products = LSEPRODUCTS::getLSEPrices($type);
            $filepath = $LSE_tempPath.'prices_'.$type.'.csv';
            $file = fopen($filepath, 'wb');
            $csvHeader   = "Artikel_Artikelnr,Artikel_ID,Artikel_EAN,Artikel_Preis";
            fputcsv($file, explode(',' , $csvHeader));
            foreach($products as $productRow){
                fputcsv($file, $productRow);
            }
            fclose($file);
            $return = "https://".$LSE_mc_user_pass."@".$LSE_savePath.'prices_'.$type.'.csv';
            LSETOOLS::lse_log( "PRICES generated " .$LSE_savePath.'prices_'.$type.'.csv');
        }

        return $return;
    }
    public static function check_wp_file_exists($filename): int
    {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value LIKE '%/$filename'");
    }
    public static function create_order($orderID,$shopType): false|string
    {
        if(empty($orderID)){
            return false;
        }
        return LSEORDERS::create_order($orderID,$shopType);
    }
}
class LSEDATA {
    public static function DBLSE(): PDO|string
    {
        $serverName = "mcommon.ddns.net";
        $database = "tegra";
        $uid = 'webshop';
        $pwd = 'P1gGsr9J';
        try {
            return new PDO(
                "sqlsrv:server=$serverName;Database=$database",
                $uid,
                $pwd,
                array(
                    //PDO::ATTR_PERSISTENT => true,
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                )
            );
        } catch (PDOException $e) {
            error_log("LSE Error connecting to SQL Server: ". $e->getMessage());
            return("Error connecting to SQL Server: " . $e->getMessage());
        }
    }
    public static function makeVariation($productRow, $sortDate ,$isParent=false,$allProducts = array(),$itemCount=1,$collectionCount=0,$productCounter=0): array
    {
        if ($isParent && $productRow['Artikel_Lieferstatus'] !== '1' ){
            $stock_status = LSETOOLS::get_stock_status_for_parent($productRow['Artikel_Variante_Von'],$allProducts);
        }
        else {
            $stock_status = $productRow['Artikel_Menge_Lager_0'] > 0 ? 'instock' : 'outofstock';
        }
        if($productRow['Artikel_Lieferstatus'] === '1' ){
            $stock_status = 'onbackorder';
        }
        $changeDate = new \DateTime($productRow['Aenderungsdatum']);
        $DateTimeNow = new \DateTime();
        $isVariableProduct = strtoupper($productRow['Artikel_Groesse'])!=='PCS';
        $isSimpleProduct   = strtoupper($productRow['Artikel_Groesse'])==='PCS';
        $csvBody = [];
        $csvBody[] = $sortDate;
        $csvBody[] = ($changeDate->diff($DateTimeNow)->days <= 30) ? 'true' : 'false';
        $csvBody[] = $isParent && $isVariableProduct ? '' : $productRow['Aenderungsdatum'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_Bezeichnung_DE'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_Nr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S1_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S1_Nr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S2_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S2_Nr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S3_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S3_Nr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S4_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S4_Nr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S5_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Artikelgruppe_S5_Nr'];
        $csvBody[] = $isParent && $isVariableProduct ? $productRow['Artikel_ID'] : $productRow['Artikel_Artikelnr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Bezeichnung'];
        $csvBody[] = $isParent && $isVariableProduct? '' : $productRow['Artikel_EAN'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Einheit'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Farbe'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Farbschema'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Farbtext'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_FutterKennzeichen'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_FutterKennzeichen_DE'];
        $csvBody[] = $isParent && $isVariableProduct? '':$productRow['Artikel_Gewicht'];
        $csvBody[] = $isParent ? ($isVariableProduct ? 'variable':'simple') : '';
        $csvBody[] = $isParent && $isVariableProduct? '':$productRow['Artikel_Groesse'];
        $csvBody[] = $isParent || $isSimpleProduct ? $productRow['Artikel_ID'] :'';
        $csvBody[] = !$isParent ? '' : str_replace("-","_",substr($productRow['Artikel_ID'],6));
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Kategorie_ID'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Kollektion'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Kollektion_Bezeichnung'];
        $csvBody[] = !$isParent ? '' : (!empty($productRow['Artikel_Kurztext']) ? ucfirst($productRow['Artikel_Kurztext']) : '');
        $csvBody[] = !$isParent ? '' : (!empty($productRow['Artikel_Kurztext_EN']) ? ucfirst($productRow['Artikel_Kurztext_EN']) : '');
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Laenge'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Lieferstatus'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_MaterialKennziffer'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_MaterialKennziffer_DE'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Menge'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_MetaDescription'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_MetaKeywords'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_MetaTitle'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Pflegesymbole'];
        $csvBody[] = !$isParent ? '' : $productCounter;
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Startseite'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Status'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Steuersatz'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Text'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Text_en'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Text_fr'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_TextLanguage'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Ursprungsland'];
        $csvBody[] = $isParent ? '0' : $productRow['Artikel_Variante_Von'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Warennummer'];
        $csvBody[] = !$isParent ? '' : $productRow['Artikel_Warennummer_Beschreibung'];
        $csvBody[] = $isParent && $isVariableProduct ? '' : $productRow['Artikel_Menge_Lager_0'];
        $csvBody[] = $stock_status;
        $csvBody[] = $productRow['Artikel_Lieferstatus'] === '1' ?'yes':'no';
        $csvBody[] = $isParent && $isVariableProduct ? '' : $productRow['lager_last_changed'];
        $csvBody[] = !$isParent || $isSimpleProduct ? 'yes' : '';
        $csvBody[] = $isParent && $isVariableProduct? '' : $productRow['Artikel_Preis'];
        return $csvBody;
    }
}
class LSEPRODUCTS {
    public static function getLSE($shop,$type,$set,$time): false|Array
    {
        $where = " AND dmcArtikel.Artikel_Kollektion <> '991'";
        $excludePreorder = ($type==="B2C" || $type==="POS");
        switch($shop){
            case 'EU':
            case 'US':
                $where .= $excludePreorder ? ' AND dmcArtikel.Artikel_Lieferstatus != 1':'';
                $where .= $set==="all"  ? '' : ' AND dmcArtikel.Aenderungsdatum >= Convert(datetime2,\''.$time.'\')';
                break;
            default:
                break;
        }
        $conn = LSEDATA::DBLSE();
        $select = "SELECT
Aenderungsdatum,
Artikel_Artikelgruppe_Bezeichnung,
Artikel_Artikelgruppe_Bezeichnung_DE,
Artikel_Artikelgruppe_Nr,
Artikel_Artikelgruppe_S1_Bezeichnung,
Artikel_Artikelgruppe_S1_Nr,
Artikel_Artikelgruppe_S2_Bezeichnung,
Artikel_Artikelgruppe_S2_Nr,
Artikel_Artikelgruppe_S3_Bezeichnung,
Artikel_Artikelgruppe_S3_Nr,
Artikel_Artikelgruppe_S4_Bezeichnung,
Artikel_Artikelgruppe_S4_Nr,
Artikel_Artikelgruppe_S5_Bezeichnung,
Artikel_Artikelgruppe_S5_Nr,
dmcArtikel.Artikel_Artikelnr as Artikel_Artikelnr,
Artikel_Bezeichnung,
dmcArtikel.Artikel_EAN as Artikel_EAN,
Artikel_Einheit,
dmcArtikel.Artikel_Farbe as Artikel_Farbe,
Artikel_Farbschema,
Artikel_Farbtext,
Artikel_FutterKennzeichen,
Artikel_FutterKennzeichen_DE,
Artikel_Gewicht,
dmcArtikel.Artikel_Groesse as Artikel_Groesse,
dmcArtikel.Artikel_ID as Artikel_ID,
Artikel_Kategorie_ID,
Artikel_Kollektion,
Artikel_Kollektion_Bezeichnung,
Artikel_Kurztext,
Artikel_Kurztext_EN,
dmcArtikel.Artikel_Laenge as Artikel_Laenge,
Artikel_Lieferstatus,
Artikel_MaterialKennziffer,
Artikel_MaterialKennziffer_DE,
Artikel_Menge,
Artikel_MetaDescription,
Artikel_MetaKeywords,
Artikel_MetaTitle, 
Artikel_Pflegesymbole,
Artikel_Sortierung,
Artikel_Startseite,
Artikel_Status,
Artikel_Steuersatz,
Artikel_Text,
Artikel_Text_en,
Artikel_Text_fr,
Artikel_TextLanguage,
Artikel_Ursprungsland,
Artikel_Variante_Von,
Artikel_Warennummer,
Artikel_Warennummer_Beschreibung,
dmcLagerMengen.Artikel_Menge_Lager_0 AS Artikel_Menge_Lager_0,
dmcLagerMengen.date_last_changed AS lager_last_changed
FROM dmcArtikel
LEFT JOIN dmcLagerMengen ON dmcArtikel.Artikel_Artikelnr = dmcLagerMengen.Artikel_Artikelnr 
WHERE dmcArtikel.Artikel_EAN<>'' AND dmcArtikel.Artikel_Kurztext <>'' ". $where ."
ORDER BY Artikel_Kollektion DESC,Artikel_Farbschema ASC";
        $prod_stmt =  $conn->query($select);
        $prod_stmt->execute();
        return $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getLSEStock($showLieferStatus = false): false|Array
    {
        $conn = LSEDATA::DBLSE();
        $select = $showLieferStatus === false ? "SELECT 
            Artikel_Artikelnr,Artikel_Menge_Lager_0 
            from dmcLagerMengen ORDER BY Artikel_Artikelnr DESC" : "SELECT dmcLagermengen.Artikel_Artikelnr as Artikel_Artikelnr,dmcLagermengen.Artikel_Menge_Lager_0 as Artikel_Menge_Lager_0, dmcArtikel.Artikel_Lieferstatus as Artikel_Lieferstatus 
            from dmcLagerMengen INNER JOIN dmcArtikel ON dmcLagermengen.Artikel_Artikelnr = dmcArtikel.Artikel_Artikelnr ORDER BY dmcLagerMengen.Artikel_Artikelnr DESC";
        $stmt =  $conn->query($select);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }
    public static function getLSEPrices($type): false|Array
    {
        $conn = LSEDATA::DBLSE();
        $preisliste['B2B_EUR'] = 1;
        $preisliste['B2B_USD'] = 2;
        $preisliste['B2B_INT'] = 4;
        $preisliste['B2C_EUR'] = 9;
        $preisliste['B2C_USD'] = 10;
        if(!isset($preisliste[$type])){
            $type = 'B2C_EUR';
        }
        $select = "SELECT 
                Artikel_Artikelnr,
                Artikel_ID,
                Artikel_EAN,
                Artikel_Preis
                from  dmcArtikelPreise 
                WHERE Artikel_EAN<>'' AND Artikel_Preisliste=".$preisliste[$type] ." ORDER BY Artikel_Artikelnr DESC";
        $price_stmt = $conn->query($select);
        $price_stmt->execute();
        return $price_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
class LSEORDERS {
    public static function create_order($orderID,$shopType): string
    {
        $order                  = wc_get_order( $orderID );
        if(!$order){
            LSETOOLS::lse_log($shopType . ' '. $orderID . ' Not found');
            return false;
        }
        $blog_id = get_current_blog_id();
        $orderItems             = $order->get_items();
        //$order_id             = $order->get_id(); // Get the order ID
        //$parent_id            = $order->get_parent_id(); // Get the parent order ID (for subscriptions…)
        //$user_id                = $order->get_user_id(); // Get the customer ID
        //$user                   = $order->get_user(); // Get the WP_User object
        $order_status           = $order->get_status(); // Get the order status (see the conditional method has_status() below)
        $order_key              = $order->get_order_key(); // Get the order status (see the conditional method has_status() below)
        $currency               = $order->get_currency(); // Get the currency used
        $payment_method         = $order->get_payment_method(); // Get the payment method ID
        $payment_title          = $order->get_payment_method_title(); // Get the payment method title
        //$date_created           = $order->get_date_created(); // Get date created (WC_DateTime object)
        $billing_last_name      = $order->get_billing_last_name();
        $billing_first_name     = $order->get_billing_first_name();
        $billing_email          = $order->get_billing_email();
        $billing_address_1      = $order->get_billing_address_1();
        $billing_address_2      = $order->get_billing_address_2();
        $billing_city           = $order->get_billing_city();
        //$billing_state          = $order->get_billing_state();
        $billing_postcode       = $order->get_billing_postcode();
        $billing_country        = $order->get_billing_country(); // Customer billing country
        //$billing_company        = $order->get_billing_company();
        $shipping_first_name    = $order->get_shipping_first_name();
        $shipping_last_name     = $order->get_shipping_last_name();
        //$shipping_company       = $order->get_shipping_company();
        $shipping_address_1     = $order->get_shipping_address_1();
        $shipping_address_2     = $order->get_shipping_address_2();
        $shipping_city          = $order->get_shipping_city();
        //$shipping_state         = $order->get_shipping_state();
        $shipping_postcode      = $order->get_shipping_postcode();
        $shipping_country       = $order->get_shipping_country();
        $shipping_total         = $order->get_shipping_total();
        $date_created           = wc_format_datetime($order->get_date_created(),'Y-m-d H:i:s');
        //$billing_company        = $order->get_billing_company();
        $bill_total             = $order->get_total();
        $total_discount         = $order->get_total_discount() ?? 0.00;
        $isDiscounted           = ($total_discount > 0)? 1 : 0;
        $discountText           = '';
        if(empty($order_key)){
            LSETOOLS::lse_log($shopType . ' '. $orderID . ' Order_key emtpy, skipping');
            return false;
        }

        if($order_status==='pending' || $order_status==='failed' || $order_status==='cancelled'){
            LSETOOLS::lse_log($shopType .  ' ORDER '. $orderID . ' skipping *'.$order_status.'* status.');
            return false;
        }
        if($isDiscounted){
            $coupon_amount = 0;
            $discountTextArray = array();
            foreach( $order->get_coupon_codes() as $coupon_code ) {
                $coupon = new WC_Coupon($coupon_code);
                $discountTextArray[] = $coupon->get_discount_type(); // Get coupon discount type
                $coupon_amount += $coupon->get_amount(); // Get coupon amount
            }
            $discountText = implode(',', $discountTextArray );
        }


        /*
        'line_items.quantity': 'Menge',
        'line_items.sku': 'eannr',
        'line_items.name':'Bezeichnung',
        'line_items.subtotal': 'Einzelpreis',
        'BelID':'BelID',
        'Belegnummer2':"SHOP_BelPosID",
        'Mandant':'Mandant'}
    */
        /*
            b2b: "Preiskennzeichen": False,
                    "Mandant": 1,
                    "RabattAbsolut1": False,
                    "Belegkennzeichen":"RE",
                    "Belegnummer":orderNumber
                    "Belegart": "Rechnung",
                    "ShopName": "B2B"}
            b2c ={"Preiskennzeichen": False,
                    "Mandant": 1,
                    "RabattAbsolut1": False,
                    "Belegkennzeichen":"RE",
                    "Belegnummer":orderNumber
                    "Belegart": "Rechnung",
                    "ShopName": ShopName }
            coupons:
              if len(order["coupon_lines"])==1:
                Rabatt1 = None
                if type(Rabatt1) is str and Rabatt1 =='':
                    Rabatt1 = None
                if type(Rabatt1) is str and Rabatt1 !='':
                    Rabatt1 = float(Rabatt1)
                add_coupon = {"RabattAbsolut1": True,
                "Rabatttext1": order["coupon_lines"][0]["code"],
                "Rabattbetrag1": order["coupon_lines"][0]["discount"],
                "Rabatt1": Rabatt1,
                "RechnungsEndbetrag": str(int(order["coupon_lines"][0]["discount"])+int(order["RechnungsEndbetrag"]))
                }


        */
        $kunde = "";
        $orderPrefix = "";
        $inc_tax = false;
        switch(strtoupper($shopType)){
            case 'POS':
                $kunde = 12679;
                $inc_tax = true;
                $orderPrefix = "KA1-".$blog_id."-";
                break;
            case 'B2B':
                $userData = get_userdata(  $order->get_user_id() );
                $kunde  = $userData->user_login;
                $orderPrefix = "B2B-".$blog_id."-";
                break;
            case 'B2C':
                $kunde = 12459;
                $inc_tax = true;
                $orderPrefix = "WEB-".$blog_id."-";
                break;
            default:
                LSETOOLS::lse_log($shopType . ' not recognized ShopType.', 'error');
        }
        $conn = LSEDATA::DBLSE();
        $selGetBelID = 'SELECT TOP 1 BelID FROM DMCShopBelege ORDER BY BelID DESC';
        $stmt = $conn->query($selGetBelID);
        $stmt->execute();
        $belIDs =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        $newBelID = (int) $belIDs[0]['BelID'];
        $newBelID ++;
        $select = "IF EXISTS (SELECT BelID FROM DMCShopBelege WHERE Belegnummer2='".$orderPrefix.$orderID."')
BEGIN
UPDATE DMCShopBelege SET Belegstatus = '".$order_status."' WHERE Belegnummer2='".$orderPrefix.$orderID."'
END
ELSE
BEGIN
INSERT INTO DMCShopBelege (BelId,Mandant,Belegkennzeichen,Shopname,Belegnummer2,Belegstatus,
            RechnungsEMail,WKz,Belegdatum,Belegart,Zahlungsart,RechnungsEndbetrag,kunde,RechnungsName1,
            RechnungsName2,RechnungsStrasse,RechnungsZusatz,RechnungsLand,RechnungsPLZ,
            RechnungsOrt,LieferName1,LieferName2,LieferStrasse,LieferZusatz,
            LieferLand, LieferPLZ,LieferOrt,Versandkosten,Preiskennzeichen,
            RabattAbsolut1,Rabattbetrag1,Rabatttext1,Fusstext) 
                VALUES
            (".$newBelID.", 1, 'RE', '".$shopType."','".$orderPrefix.$orderID."','".$order_status."','".$billing_email."',
            '".$currency."',convert(datetime2,'".$date_created."'),'Rechnung','".($payment_title??$payment_method)."',". $bill_total .",'".$kunde."','".$billing_first_name."',
            '".$billing_last_name."','".$billing_address_1."','".$billing_address_2."','".$billing_country."',
            '".$billing_postcode."','".$billing_city."','".$shipping_first_name."','".$shipping_last_name."',
            '".$shipping_address_1."','".$shipping_address_2."','".$shipping_country."','".$shipping_postcode."',
            '".$shipping_city."','".$shipping_total."','0','".$isDiscounted."','".$total_discount."','".$discountText."','".$order_key."'); 
            \n";
            /*'coupon_lines':
            coupon_lines = '',*/
            /*'number': */
        /*try{
            echo('<pre>');
            echo $select;
            echo('</pre>');
            $stmtSave = $conn->query($select);
            $stmtSave->execute();
            $insertID = $conn->lastInsertId();
            echo $insertID;
        } catch (PDOException $e) {
            LSETOOLS::lse_log($e .' - '. $select, 'error');
        }*/
        $selectItems = "INSERT INTO DMCShopBelegePositionen 
    (BelPosID,SHOP_BelPosID,Mandant,BelID,Bezeichnung,Menge,Einzelpreis,
     Rabattbetrag,eannr,gb,koll,artikelnr,raster,farbe ) VALUES ";
        $itemArray = array();
        if($newBelID){
            //LSETOOLS::lse_log('Trying BLid '.$newBelID);
            $selGetBelPOSID = 'SELECT TOP 1 BelPosID FROM DMCShopBelegePositionen ORDER BY BelPosID DESC';
            $stmt = $conn->query($selGetBelPOSID);
            $stmt->execute();
            $belPosIDs =$stmt->fetchAll(PDO::FETCH_ASSOC);
            //LSETOOLS::lse_log('items: '.var_export($orderItems,true));
            $newBelPOSID = (int) $belPosIDs[0]['BelPosID'];

            foreach($orderItems as $item_id=>$orderItem){
                $newBelPOSID ++;
                $product        = $orderItem->get_product();
                $artikelID      = $product->get_sku();
                //LSETOOLS::lse_log('Trying Item '. $product->get_id());
                $ean            = get_post_meta(  $product->get_id(), '_alg_ean', true );
                $productInfo    = explode('-',$artikelID);
                if(count($productInfo) === 7){
                    list($gb,$koll,$artikelnr,$raster,$farbe,$unknown,$size)=$productInfo;
                } else {
                    //Try to match as much as possible from manually entered product.
                    preg_match('/\d+/',$orderItem->get_name(),$matches);
                    $gb = null;
                    $koll=null;
                    $artikelnr = $matches[0];
                    $raster = null;
                    $farbe=null;
                    $unknown=null;
                    $size=null;
                    if(empty($ean)){
                        $ean = $product->get_sku();
                    }
                }
                $item_cost_excl_disc = $order->get_item_subtotal( $orderItem, $inc_tax);
                $item_cost_incl_disc = $order->get_item_total($orderItem, $inc_tax);
                $discountAmount =  $item_cost_excl_disc - $item_cost_incl_disc;
                //LSETOOLS::lse_log($shopType . ' COST: orig ' .$item_cost_excl_disc . ' net | discounted: ' . $item_cost_incl_disc . ' net | discount: ' . $discountAmount) ;
                $lineItem = "(";
                $lineItem .= "$newBelPOSID,";
                $lineItem .= "'".$orderPrefix.$orderID."',";
                $lineItem .= "1,";
                $lineItem .= $newBelID.",";
                $lineItem .= "'".$orderItem->get_name()."',";
                $lineItem .= $orderItem->get_quantity().",";
                $lineItem .= $item_cost_excl_disc.",";
                $lineItem .= ($discountAmount > 0 ? $discountAmount : "NULL").",";
                $lineItem .= ($ean       !== null ?"'".$ean."'":"NULL").",";
                $lineItem .= ($gb        !== null ?"'".$gb."'":"NULL").",";
                $lineItem .= ($koll      !== null ?"'".$koll."'":"NULL").",";
                $lineItem .= ($artikelnr !== null ?"'".$artikelnr."'":"NULL").",";
                $lineItem .= ($raster    !== null ?"'".$raster ."'":"NULL").",";
                $lineItem .= ($farbe     !== null ?"'".$farbe. "'":"NULL");
                $lineItem .= ")";
                $itemArray[] = $lineItem;

            }
        } else {
            LSETOOLS::lse_log($shopType . ' ORDER '.$orderPrefix.$orderID.' failed to create, no belID' );
            return false;
        }
        $itemsLines = implode(',',$itemArray);
        $selectItems .= $itemsLines . "\nEND";
        //LSETOOLS::lse_log($select.$selectItems);
        try{
            $stmt = $conn->query($select.$selectItems);
            $stmt->execute();
            LSETOOLS::lse_log($shopType . ' ORDER '.$orderID.' created/updated [' . $stmt->rowCount() . ' rows affected] - Status *'.$order_status.'*' );
            $order->add_order_note('LSE ORDER created/updated -> ' . $order_status);
        }catch (PDOException $e) {
            LSETOOLS::lse_log($e .' - '. $select.$selectItems, 'error');
        }
        return $order_status;

    }
}
class LSETOOLS {
    public static function sendCurl($url,$file=false,$data=false, $headercontent=false): bool|string
    {
        global $mc_user_pass;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT , 60);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if(is_array($headercontent)){

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headercontent);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type:text/csv'
            ));
        }
        if($data){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }elseif($file != false){
            $handle = fopen($file, 'rb');
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_USERPWD, $mc_user_pass);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_UPLOAD, 1);
            curl_setopt($ch, CURLOPT_INFILE,$handle );
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
        }
        else {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }
        // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);// this should be set to true in production
        // if(!$verifyhost){
        //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        // }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $return = curl_exec($ch);
        $info = curl_getinfo($ch);
        $return .=  'Took '. $info['total_time'].  " sec, HTTP STATUS: " . $info['http_code']. "<br>";
        $return .= var_export($info,true);
        if(curl_errno($ch)) {
            $return .= '<b>ERROR</b> ' .curl_error($ch);
        }
        curl_close($ch);
        if($handle){
            fclose($handle);
        }
        return $return;
    }
    public static function findPriceByArtikleNr($prices,$artikelNr){
        foreach ($prices as $key => $val) {
            if ($val['Artikel_Artikelnr'] === $artikelNr) {
                return $val['Artikel_Preis'];
            }
        }
        return null;

    }
    public static function get_sort_date_by_productID($Artikel_ID,$itemCount,$collectionItemCount): ?string
    {   // Expample ID = 1-241-1589105-1-420
        //var_dump($itemCount);
        $itemCount *= 60;
        $collectionItemCount *=10;
        $delete = $itemCount + $collectionItemCount;
        $parts = explode('-',$Artikel_ID);
        $ts = (float) $parts[2] / 86400;
        $collectionYear = (int) substr($parts[1],0,2);
        $displayYear = $collectionYear - 1;
        $floorTS = (int) floor($ts);
        $year = '20'.$displayYear;
        $month = substr($parts[1],2);
        $day = "28";
        //$time = (int) strtotime($year. '-'. $month. '-'. $day .'00:00:00');
        $time = (int) mktime(23,59,0, $month,$day,$year);
        //echo('<br>'.$time.'-'.$itemCount.'-'.$collectionItemCount);
        $time -= $delete;
        //echo('<br>'.$time.'<br>');
        return date('Y-m-d H:i:s',$time);
    }

    public static function get_total_combined_stock_quantity($productId): ?int
    {
        if($productId === 0 ){ return 0;}
        $product = wc_get_product($productId);
        if (!$product->is_type('variable')) {
            return $product->get_stock_quantity();
        }
        //Stock management is enabled at product level
        if ($product->managing_stock()) {
            return $product->get_stock_quantity();
        }
        $total = 0;
        if ($product->is_type('variable')) {
            foreach ($product->get_visible_children() as $variationId) {
                $variation = wc_get_product($variationId);
                $total += $variation->get_stock_quantity();
            }
        }
        return $total;
    }
    public static function getPriceListType($shop,$type){
        $shopArr = array ('EU'=>'EUR', 'US' => 'USD', 'INT' => 'USD');
        return $type .'_'.($shopArr[$shop]??'EUR');

    }
    public static function getSiteTypes($id=false){
        global $site;
        if($id){
            return $site[$id]??false;
        }
        return $site;
    }
    public static function get_stock_status_for_parent($Artikel_Variante_Von,$allProducts): string{
        $sum = 0;
        foreach($allProducts as $key=>$row){
            if($row['Artikel_Variante_Von']===$Artikel_Variante_Von){
                $sum += $row['Artikel_Menge_Lager_0'];
            }
        }
        return $sum > 0 ? 'instock':'outofstock';
    }
    public static function lse_log($text, $type='info'){
        $return = false;
        if (function_exists('wc_get_logger')) {
            switch_to_blog(10);
            $return = wc_get_logger()->$type($text, array('source' => 'LSE_CONNECT'));
            restore_current_blog();
        }
        return $return;
    }
    public static function getDBStatus($echo=true){
        $serverName = "mcommon.ddns.net";
        $database = "tegra";
        $uid = 'webshop';
        $pwd = 'P1gGsr9J';
        try {
             $dbh =  new PDO("sqlsrv:server=$serverName;Database=$database", $uid, $pwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
             if($echo) {echo '{"STATUS":"OK","MESSAGE":"All is good"}';} else {return 'OK';}
        } catch (PDOException $e) {
            if($echo) {echo '{"STATUS":"FAIL","MESSAGE":"('.$e->getCode() . '):'.  $e->getMessage() . '"}';} else {return $e->getCode() . " " . $e->getMessage();}
            error_log("LSE DB STATUS FAIL connecting to SQL Server: ".$e->getCode() . " " . $e->getMessage());
        }
    }
}