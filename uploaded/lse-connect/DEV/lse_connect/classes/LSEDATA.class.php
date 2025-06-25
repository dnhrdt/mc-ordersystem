<?php
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
            return("Error connecting to SQL Server: " . $e->getMessage());
        }
    }
    public static function makeVariation($productRow, $sortDate ,$isParent=false,$allProducts = array(),$itemCount=1,$collectionCount=0,$productCounter=0): array
    {
        echo (!$isParent ? '' : $productRow['Artikel_Farbschema']).' - '.$productRow['Artikel_ID']. ' ' . $sortDate.'</br>';

        if ($isParent && $productRow['Artikel_Lieferstatus'] !== '1' ){
            //TODO: check memory consumption for this. (all products array)
            $stock_status = LSETOOLS::get_stock_status_for_parent($productRow['Artikel_Variante_Von'],$allProducts);
        }
        else {
            $stock_status = $productRow['Artikel_Menge_Lager_0'] > 0 ? 'instock' : 'outofstock';
        }
        if($productRow['Artikel_Lieferstatus'] === '1' ){
            $stock_status = 'onbackorder';
        }
        $isVariableProduct = strtoupper($productRow['Artikel_Groesse'])!=='PCS';
        $isSimpleProduct   = strtoupper($productRow['Artikel_Groesse'])==='PCS';
        $csvBody = [];
        $csvBody[] = $sortDate;
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
        $csvBody[] = !$isParent ? '' : ucfirst($productRow['Artikel_Kurztext']);
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