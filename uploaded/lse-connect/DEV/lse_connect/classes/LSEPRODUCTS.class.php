<?php
class LSEPRODUCTS {
    public static function getIncompleteProducts($collection_id){
        $conn = LSEDATA::DBLSE();
        $select = "SELECT DISTINCT Artikel_ID, Artikel_Kurztext,Artikel_Kurztext_EN,Artikel_Text,Artikel_Text_en FROM dmcArtikel WHERE Artikel_ID LIKE '%-".$collection_id."-%' ; ";//AND (Artikel_Kurztext = '' OR Artikel_Kurztext_EN = '' OR Artikel_Text = '' OR Artikel_Text_en  = '')
        $prod_stmt =  $conn->query($select);
        $prod_stmt->execute();
        return $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getCollections(){
        $conn = LSEDATA::DBLSE();
        $select = "SELECT DISTINCT Artikel_Kollektion FROM dmcArtikel ORDER BY Artikel_Kollektion DESC";
        $prod_stmt =  $conn->query($select);
        $prod_stmt->execute();
        return $prod_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public static function getLSE($shop,$type,$set,$time): false|Array
    {
        $where = '';
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
    public static function getLSEStock($showLieferStatus = false,$lastDayOnly=false): false|Array
    {
        $conn = LSEDATA::DBLSE();

        $select = $showLieferStatus === false ? "SELECT 
            Artikel_Artikelnr,Artikel_Menge_Lager_0 
            from dmcLagerMengen ORDER BY Artikel_Artikelnr DESC" : "SELECT dmcLagermengen.Artikel_Artikelnr as Artikel_Artikelnr,dmcLagermengen.Artikel_Menge_Lager_0 as Artikel_Menge_Lager_0, dmcArtikel.Artikel_Lieferstatus as Artikel_Lieferstatus 
            from dmcLagerMengen INNER JOIN dmcArtikel ON dmcLagermengen.Artikel_Artikelnr = dmcArtikel.Artikel_Artikelnr ORDER BY dmcLagerMengen.Artikel_Artikelnr DESC";

        if($lastDayOnly){
            $minusDays = 1;
            $dateTime =  mktime(0, 0, 0, date("m"), date("d"),   date("Y")) - ($minusDays * 86400);
            $date     = date('Y-m-d\Th:i:s',$dateTime);
            $select .= ' WHERE  >= Convert(datetime2,\''.$date.'\')';
        }
        wc_get_logger('');
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