<?php
class LSEORDERS {
    public static function create_order($orderID,$shopType): string
    {
        $order                  = wc_get_order( $orderID );
        if(!$order){
            LSETOOLS::lse_log($shopType . ' '. $orderID . ' Not found');
            return false;
        }

        $blog_id = get_current_blog_id();
        //$order_id             = $order->get_id(); // Get the order ID
        //$parent_id            = $order->get_parent_id(); // Get the parent order ID (for subscriptions…)
        //$user_id                = $order->get_user_id(); // Get the customer ID
        //$user                   = $order->get_user(); // Get the WP_User object
        $order_status           = $order->get_status(); // Get the order status (see the conditional method has_status() below)
        $kunde = "";
        $orderPrefix = "";
        $inc_tax = false;
        switch(strtoupper($shopType)){
            case 'POS':
                $kunde = 12679;
                $inc_tax = true;
                $orderPrefix = "KA1-".$blog_id ."-";
                break;
            case 'B2B':
                $userData = get_userdata(  $order->get_user_id() );
                $kunde  = $userData->user_login;
                $orderPrefix = "B2B-".$blog_id ."-";
                break;
            case 'B2C':
                $kunde = 12459;
                $inc_tax = true;
                $orderPrefix = "WEB-".$blog_id ."-";
                break;
            default:
                LSETOOLS::lse_log($shopType . ' not recognized ShopType.', 'error');
        }
        $orderItems             = $order->get_items();
        $order_key              = $order->get_order_key(); // Get the order status (see the conditional method has_status() below)
        $currency               = $order->get_currency(); // Get the currency used
        $payment_method         = $order->get_payment_method(); // Get the payment method ID
        $payment_title          = $order->get_payment_method_title(); // Get the payment method title
        $date_created           = $order->get_date_created(); // Get date created (WC_DateTime object)
        $billing_last_name      = $order->get_billing_last_name();
        $billing_first_name     = $order->get_billing_first_name();
        $billing_email          = $order->get_billing_email();
        $billing_address_1      = $order->get_billing_address_1();
        $billing_address_2      = $order->get_billing_address_2();
        $billing_city           = $order->get_billing_city();
        $billing_state          = $order->get_billing_state();
        $billing_postcode       = $order->get_billing_postcode();
        $billing_country        = $order->get_billing_country(); // Customer billing country
        $billing_company        = $order->get_billing_company();
        $shipping_first_name    = $order->get_shipping_first_name();
        $shipping_last_name     = $order->get_shipping_last_name();
        $shipping_company       = $order->get_shipping_company();
        $shipping_address_1     = $order->get_shipping_address_1();
        $shipping_address_2     = $order->get_shipping_address_2();
        $shipping_city          = $order->get_shipping_city();
        $shipping_state         = $order->get_shipping_state();
        $shipping_postcode      = $order->get_shipping_postcode();
        $shipping_country       = $order->get_shipping_country();
        $shipping_total         = $order->get_shipping_total();
        $date_created           = wc_format_datetime($order->get_date_created(),'Y-m-d H:i:s');
        $billing_company        = $order->get_billing_company();
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
        $conn = LSEDATA::DBLSE();
        $selGetBelID = 'SELECT TOP 1 BelID FROM DMCShopBelege ORDER BY BelID DESC';
        $stmt = $conn->query($selGetBelID);
        $stmt->execute();
        $belIDs =  $stmt->fetchAll(PDO::FETCH_ASSOC);
        $newBelID = (int) $belIDs[0]['BelID'];
        $newBelID ++;
        $select = "IF EXISTS (SELECT BelID FROM DMCShopBelege WHERE Belegnummer2='".$order_key."')
BEGIN
UPDATE DMCShopBelege SET Belegstatus = '".$order_status."' WHERE Belegnummer2='".$order_key."'
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
            (".$newBelID.", 1, 'RE', '".$shopType."','".$order_key."','".$order_status."','".$billing_email."',
            '".$currency."',convert(datetime2,'".$date_created."'),'Rechnung','".($payment_title??$payment_method)."',". $bill_total .",'".$kunde."','".$billing_first_name."',
            '".$billing_last_name."','".$billing_address_1."','".$billing_address_2."','".$billing_country."',
            '".$billing_postcode."','".$billing_city."','".$shipping_first_name."','".$shipping_last_name."',
            '".$shipping_address_1."','".$shipping_address_2."','".$shipping_country."','".$shipping_postcode."',
            '".$shipping_city."','".$shipping_total."','0','".$isDiscounted."','".$total_discount."','".$discountText."','".$orderPrefix.$orderID."'); 
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
                $lineItem .= "'".$order_key."',";
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
            LSETOOLS::lse_log($shopType . ' ORDER '.$orderID.' failed to create, no belID' );
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