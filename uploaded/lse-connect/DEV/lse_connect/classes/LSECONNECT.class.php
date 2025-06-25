<?php
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

    public static function showIncompleteProducts(){
        $collections = LSEPRODUCTS::getCollections();
        //var_dump($collections);
        $collectionLinks = "<ul>";
        foreach ($collections as $collection) {
            $collectionLinks .= '<li><a href="?page=lse-woo-lse&type=checkValidity&COLLECTIONID='.$collection['Artikel_Kollektion'].'">'.$collection['Artikel_Kollektion'].'</a></li>';
        }
        $collectionLinks .= '</ul>';
        $collectionID = $_GET['COLLECTIONID']??$collections[0]['Artikel_Kollektion'];
        $products = LSEPRODUCTS::getIncompleteProducts($collectionID);
        $table = '<style>.Red  {background-color:red;}.Green{background-color: green}</style>';
        $table .= $collectionLinks;
        $table .= '<table class="wp-list-table widefat fixedU">';
        $table .= '<tr>';
        $table .= '<th>Artikel_ID</th>';
        $table .= '<th>Artikel_Kurztext</th>';
        $table .= '<th>Artikel_Kurztext_EN</th>';
        $table .= '<th>Artikel_Text</th>';
        $table .= '<th>Artikel_Text_en</th>';
        $table .= '<th>Bild OK</th>';
        $table .= '</tr>';
        foreach($products as $productRow){
            $showRow = 0;
            $row = "";
            $row .= '<tr>';
            $row .= '<td>'.$productRow['Artikel_ID'].'</td>';
            $row .= '<td class="'.(empty($productRow['Artikel_Kurztext'])      ? 'Red' : 'Green').'">' . $productRow['Artikel_Kurztext']   . '</td>';
            $showRow = empty($productRow['Artikel_Kurztext']) ? +1 : $showRow;
            $row .= '<td class="'.(empty($productRow['Artikel_Kurztext_EN'])   ? 'Red' : 'Green').'">' . $productRow['Artikel_Kurztext_EN']   . '</td>';
            $showRow = empty($productRow['Artikel_Kurztext_EN'])? +1 : $showRow;;
            $row .= '<td class="'.(empty($productRow['Artikel_Text'])          ? 'Red' : 'Green').'">' . strlen( $productRow['Artikel_Text'])  . '</td>';
            $showRow = empty($productRow['Artikel_Text'])? +1 : $showRow;;
            $row .= '<td class="'.(empty($productRow['Artikel_Text_en'])       ? 'Red' : 'Green').'">' . strlen($productRow['Artikel_Text_en'])    . '</td>';
            $showRow = empty($productRow['Artikel_Text_en'])? +1 : $showRow;
            $row .= '<td>';
            $productID    = explode('-',$productRow['Artikel_ID']);
            $picCount = 0;
            for($i=1;$i<6;$i++){
                $name = "/".$productID[1]."/".$productID[2]."_".$productID[3]."/".$productID[2]."_".$productID[3]."_".$i.".jpg";
                $exists = file_exists("/chroot/home/maisonde/maisoncommon.com/html/wp-content/uploads/lse-import/bilder/".$name);
                //$table .= $name . ": ". ($exists?"Y":"N");
                $picCount = $exists? $picCount+1 : $picCount;
                $row .= "<br>";
            }
            $row .= $picCount . "/5 images Found<br>";
            $row .= '</td>';
            $row .= '<tr>';
            if($showRow>0 && $picCount !== 0){
                $table .= $row;
            }
        }
        $table .= '</table>';
        return $table;
    }
    public static function export_products($shop,$type,$set="all"): string
    {

        $timeStart = time();
        //ini_set('display_errors',1);
        $filename = 'products_'.$shop.'_'.$type.($set === "all"?'':'_'.$set).'.csv';
        $csvFile  = LSE::$CSV_LOCAL_PATH.$filename;
        $minusDays = 30;
        $dateTime =  mktime(0, 0, 0, date("m"), date("d"),   date("Y")) - ($minusDays * 86400);
        $date     = date('Y-m-d\Th:i:s',$dateTime);
        $products = LSEPRODUCTS::getLSE($shop,$type,$set,$date);
        $priceList = LSETOOLS::getPriceListType($shop,$type);
        $prices = LSEPRODUCTS::getLSEPrices($priceList);
        $file = fopen($csvFile, 'wb');
        $productCounter = 0;
        $collectionItemCount=0;
        $variationCounter  = 0;
        $previousID        = 0;
        $prevCollection = '';
        $csvHeader   = "SortierDatum,Aenderungsdatum,Artikel_Artikelgruppe_Bezeichnung,Artikel_Artikelgruppe_Bezeichnung_DE,Artikel_Artikelgruppe_Nr,Artikel_Artikelgruppe_S1_Bezeichnung,Artikel_Artikelgruppe_S1_Nr,Artikel_Artikelgruppe_S2_Bezeichnung,Artikel_Artikelgruppe_S2_Nr,Artikel_Artikelgruppe_S3_Bezeichnung,Artikel_Artikelgruppe_S3_Nr,Artikel_Artikelgruppe_S4_Bezeichnung,Artikel_Artikelgruppe_S4_Nr,Artikel_Artikelgruppe_S5_Bezeichnung,Artikel_Artikelgruppe_S5_Nr,Artikel_Artikelnr,Artikel_Bezeichnung,Artikel_EAN,Artikel_Einheit,Artikel_Farbe,Artikel_Farbschema,Artikel_Farbtext,Artikel_FutterKennzeichen,Artikel_FutterKennzeichen_DE,Artikel_Gewicht,Artikel_Groesse,Product_type,Artikel_ID,Bild_ID,Artikel_Kategorie_ID,Artikel_Kollektion,Artikel_Kollektion_Bezeichnung,Artikel_Kurztext,Artikel_Laenge,Artikel_Lieferstatus,Artikel_MaterialKennziffer,Artikel_MaterialKennziffer_DE,Artikel_Menge,Artikel_MetaDescription,Artikel_MetaKeywords,Artikel_MetaTitle,Artikel_Pflegesymbole,Artikel_Sortierung,Artikel_Startseite,Artikel_Status,Artikel_Steuersatz,Artikel_Text,Artikel_Text_en,Artikel_Text_fr,Artikel_TextLanguage,Artikel_Ursprungsland,Artikel_Variante_Von,Artikel_Warennummer,Artikel_Warennummer_Beschreibung,Artikel_Menge_Lager_0,stock_status,allow_backorder,lager_last_changed,manage_inventory,Artikel_Preis";
        fputcsv($file, explode(',' , $csvHeader));
        $sortDate = '';
        foreach($products as $key=>$productRow){
            $currentCollection =  $productRow['Artikel_Kollektion'];
            $currentID = $productRow['Artikel_ID'];
            $productRow['Artikel_Preis']=LSETOOLS::findPriceByArtikleNr($prices,$productRow['Artikel_Artikelnr']);
            if($currentID !== $previousID) {
                $productCounter ++;
                $productRow['stockstatus'] = '';
                $sortDate = LSETOOLS::get_sort_date_by_productID($productRow['Artikel_ID'],$productCounter,$collectionItemCount);
                fputcsv($file, LSEDATA::makeVariation($productRow,$sortDate,true,$products,$key,$collectionItemCount,$productCounter));
                if(strtoupper($productRow['Artikel_Groesse']) === 'PCS'){
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
            $collectionItemCount = $prevCollection === $currentCollection ? $collectionItemCount+1 : 0;
            $prevCollection = $currentCollection;
        }
        fclose($file);
        $timeDiff = time() - $timeStart;
        $dlPath = "https://".LSE::$mc_user_pass."@".LSE::$CSV_DL_PATH.$filename;
        LSETOOLS::lse_log( "PRODUCT CSV ".$shop.'_'.$type.($set === "all"?'':'_'.$set)." generated (".$timeDiff."sec)".$filename);
        if($timeDiff>180){
            wp_mail("monitoring@deinhardt.com", "US MC File Export ". $filename, $log." (".$timeDiff."sec) ".$filename);
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
        if(LSE::$LSE_UPDATE_IS_RUNNING){
            LSETOOLS::lse_log("LSE_UPDATE already running");
            return false;
        }
        LSE::$LSE_UPDATE_IS_RUNNING = true;
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
        if(LSE::$ENV === 'DEV'){
            unset($stores);
            $stores[0] = 10;
        }
        foreach($stores as $store_id ){
            switch_to_blog( $store_id );
            if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
                continue;
            }
            if(function_exists('wp_all_import_get_import_id')) {
                $importID = wp_all_import_get_import_id();
                if ($importID) {
                    LSETOOLS::lse_log("Aborting Stock_update " . $site[$store_id] . " import " . $importID . " ist running");
                }
            }
            $memory = memory_get_peak_usage(true)/1024/1024/1024;
            $wooStock = self::get_woo_stocklevel();
            if(count($wooStock)===0){
                LSETOOLS::lse_log( "No woo stock for site  stock level for:" .$site[$store_id],'error');
                continue;
            }
            $log.= "[SHOP:".$site[$store_id].":\n";
            $stockstatusUpdate = array();
            $preOrderUpdate = array();
            $count = 0;
            $preOrderCount = 0;
            $shopProdCount = 0;
            foreach($products as $productRow){
                // get Product by SKU here,
                //$variation_id = wc_get_product_id_by_sku($productRow['Artikel_Artikelnr']);
                $LSE_SKU =  $productRow['Artikel_Artikelnr'];
                $variation_id = $wooStock[$LSE_SKU]['id'];
                $LSE_Stock = (int)$productRow['Artikel_Menge_Lager_0'];
                //if($variation_id){
                if(isset($wooStock[$variation_id])){
                    $shopProdCount ++;
                    if($productRow['Artikel_Lieferstatus'] === '1'){
                        $product = wc_get_product($variation_id);
                        $wooStock = $product->get_stock_quantity();// PreOrder Product:
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
                        $product = null;
                    } else if( $wooStock !== $LSE_Stock ) {
                        $newStock = wc_update_product_stock($variation_id, $LSE_Stock);
                        $status = $newStock <= 0  ? 'outofstock':'instock';
                        wc_update_product_stock_status($variation_id,$status);
                        $newId = wp_get_post_parent_id($variation_id);
                        $product_id = $newId === 0 ? $variation_id : $newId;
                        $stockstatusUpdate[$product_id]=$variation_id;
                        $log .=  $LSE_SKU . ": " .$wooStock."->".$newStock." | " ;
                        $count ++;
                    }
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
                echo('Update product:' . $keyId .'->'.$quantity.' - '. $status . ' - ' . wc_update_product_stock_status($keyId,$status).'<br/>');
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
            //$wooStock = null;
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
        LSE::$LSE_UPDATE_IS_RUNNING = false;
        return $allCounts . ' products Stock updated. Took '.($timeDiff) .'sec ' .$log;
    }
    public static function export_prices($type=''): string {
        $preisliste[0]='B2B_EUR';
        $preisliste[1]='B2B_USD';
        $preisliste[2]='B2B_INT';
        $preisliste[3]='B2C_EUR';
        $preisliste[4]='B2C_USD';
        $return = '';
        if($type===''){
            foreach($preisliste as $key=>$value){
                $products = LSEPRODUCTS::getLSEPrices($value);
                $filepath = LSE::$CSV_LOCAL_PATH.'prices_'.$value.'.csv';
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
            $filepath = LSE::$CSV_LOCAL_PATH.'prices_'.$type.'.csv';
            $file = fopen($filepath, 'wb');
            $csvHeader   = "Artikel_Artikelnr,Artikel_ID,Artikel_EAN,Artikel_Preis";
            fputcsv($file, explode(',' , $csvHeader));
            foreach($products as $productRow){
                fputcsv($file, $productRow);
            }
            fclose($file);
            $return = "https://".LSE::$mc_user_pass."@".LSE::$CSV_DL_PATH.'prices_'.$type.'.csv';
            LSETOOLS::lse_log( "PRICES generated " .LSE::$CSV_DL_PATH.'prices_'.$type.'.csv');
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