<?php
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
        $month = substr($parts[1],2)==="1" ? '03':'09';
        $day = "30";
        //$time = (int) strtotime($year. '-'. $month. '-'. $day .'00:00:00');
        $time = (int) mktime(23,59,0, $month,$day,$year);
        echo('<br>'.$time.'-'.$itemCount.'-'.$collectionItemCount);
        $time -= $delete;
        echo('<br>'.$time.'<br>');
        return date('Y-m-d H:i:s',$time);
    }

    public static function get_total_combined_stock_quantity($productId): ?int
    {
        if($productId === 0 ){ return 0;}
        $product = wc_get_product($productId);
        //var_dump($product);
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
        $site = array();
        $site[7]='B2C'; //EN shop
        $site[8]='B2B'; //EU B2B
        $site[9]='POS'; //DE POS
        $site[10]='B2C'; //DE B2C
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
}