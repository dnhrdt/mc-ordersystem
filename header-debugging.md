# Header Debugging - Collection Wechsel Problem
Version: 1.00
Timestamp: 2025-06-25 21:58 CET

## PROBLEM
Nach AJAX Collection-Wechsel verschwinden die DataTable Such- und Filter-Header (dataTables_filter, dataTables_length, dataTables_info).

## SYMPTOME
- Tabelle wird korrekt geladen (37.458 Zeichen, 101 Produkte)
- Tabelle existiert im DOM (`#woocommerce-quick-order`)
- Tabelle hat korrekte CSS-Klassen: `class="woocommerce-quick-order datatables nowrap"`
- DataTable initComplete Event wird NIE gefeuert
- Header-Elemente haben immer count: 0

## VERSUCHTE LÖSUNGEN (ALLE FEHLGESCHLAGEN)

### 1. ID-Mismatch Fix
**Problem:** JavaScript suchte nach `#woocommerce-quick-order-table`, Tabelle hat aber ID `#woocommerce-quick-order`
**Fix:** Alle Selektoren korrigiert
**Ergebnis:** Tabelle wird erkannt, aber initComplete feuert nicht

### 2. Timing-Fixes
**Problem:** DOM nicht bereit für Reinitialisierung
**Versuche:**
- 50ms Delay vor Reinitialisierung
- 100ms und 500ms Checks nach Reinitialisierung
- DOM readyState Checks
**Ergebnis:** Timing ist korrekt, Problem liegt woanders

### 3. Plugin-Initialisierung Varianten
**Versuche:**
- `$("body").quickOrder()` (Standard)
- `quickOrderTable.quickOrder()` (direkt auf Tabelle)
- Fallback zwischen beiden Methoden
**Ergebnis:** Alle Methoden schlagen fehl

### 4. CSS-Klassen Fix
**Problem:** Plugin sucht nach `#woocommerce-quick-order.datatables`
**Fix:** Automatisches Hinzufügen der `datatables` Klasse
**Ergebnis:** Klasse ist bereits vorhanden (PHP generiert sie), Problem liegt woanders

### 5. DataTable Destroy/Reinit
**Versuche:**
- Bestehende DataTable-Instanz zerstören vor Neuinitialisierung
- Prüfung auf `$.fn.DataTable.isDataTable()`
**Ergebnis:** Keine bestehende Instanz gefunden, Problem liegt woanders

### 6. initComplete Hook Modifikation
**Versuch:** Eigene initComplete Funktion mit Debugging
**Ergebnis:** Hook wird nie aufgerufen, Plugin initialisiert DataTable nicht

## AKTUELLE ERKENNTNISSE

### Was funktioniert:
- AJAX lädt korrekt (PHP-seitig alles OK)
- Tabelle wird ins DOM eingefügt
- Tabelle hat korrekte ID und CSS-Klassen
- WooCommerce Quick Order Plugin ist verfügbar
- DataTable Plugin ist verfügbar

### Was nicht funktioniert:
- `initComplete` Event wird nie gefeuert
- DataTable wird nicht initialisiert
- Header-Elemente werden nicht erstellt

## DEBUGGING LOGS (Konsistente Ausgabe)
```
MC Debug: Table exists after HTML update: true ✅
MC Debug: Table exists before reinit: true ✅
MC Debug BEFORE reinit - DataTable instance exists: false ❌
MC Debug: Initializing quickOrder plugin on body (standard method)
MC Debug AFTER reinit - DataTable instance exists: false ❌
MC Debug: DataTable initComplete fired - WIRD NIE AUSGEGEBEN ❌
```

## Finale Analyse & Lösung (2025-06-25 22:15 CET)

**Eindeutige Ursache:**
Die Analyse der Datei `woocommerce-quick-order/public/js/woocommerce-quick-order-public.js` hat die genaue Ursache aufgedeckt. Das Plugin verhindert aktiv eine Re-Initialisierung auf einem Element, auf dem es bereits läuft.

Der relevante Code-Abschnitt ist der Plugin-Konstruktor:
```javascript
$.fn[ pluginName ] = function( options ) {
    return this.each( function() {
        if ( !$.data( this, "plugin_" + pluginName ) ) {
            $.data( this, "plugin_" +
                pluginName, new Plugin( this, options ) );
        }
    } );
};
```
Beim ersten Seitenaufruf wird `$("body").quickOrder()` ausgeführt und eine Plugin-Instanz wird via `$.data` an das `<body>`-Element gebunden. Bei unserem AJAX-Reload rufen wir `$("body").quickOrder()` erneut auf. Die `if`-Bedingung `!$.data( this, "plugin_" + pluginName )` ist nun `false`, da die Daten bereits existieren. Folglich wird `new Plugin( this, options )` **nicht** erneut ausgeführt und die Tabelle wird nicht initialisiert.

**Empfohlene Lösung:**
Die sauberste Lösung ist, die vom Plugin gesetzte Markierung vor der Re-Initialisierung manuell zu entfernen. Dies zwingt das Plugin zu einer sauberen, neuen Initialisierung auf dem aktualisierten Inhalt.

**Implementierung:**
In `assets/js/mc-collections-ajax.js`, innerhalb der `success`-Callback-Funktion des AJAX-Aufrufs, muss **vor** dem Aufruf `$("body").quickOrder(...)` folgende Zeile eingefügt werden:
```javascript
$("body").removeData('plugin_quickOrder');
```
Dies löst das Problem, ohne das Kern-Plugin modifizieren zu müssen.
