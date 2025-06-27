# Systemmuster - Version 1.5.0 Entwicklung
Version: 2.11
Timestamp: 2025-06-26 13:53 CET

## Entwicklungsstandards
- Funktionen mit Präfix 'mc_' versehen (Maison Common)
- Kommentare auf Deutsch
- Code-Dokumentation auf Deutsch
- AJAX-Endpoints mit 'mc_' Präfix
- Nonce-Security für alle AJAX-Requests

## Fehlerbehandlung
- Robuste Prüfung, ob wir uns auf einer Shop- oder Kategorie-Seite befinden
- Fallback für den Fall, dass keine Kategorie ausgewählt ist
- User-freundliche Fehlermeldungen bei AJAX-Requests
- Debug-Ausgaben für Entwicklung (console.log)

## AJAX-Patterns (Version 1.4.0+)
- **Tabellen-Reinitialisierung:** Vollständige Neuinitialisierung nach AJAX-Content-Updates
- **Loading-Animationen:** User-Feedback während AJAX-Requests
- **Error-Handling:** Graceful Degradation bei AJAX-Fehlern
- **Browser-History:** URL-Updates für bessere UX

## EAN-Scanner Patterns (Version 1.4.0+)
- **HID-Device-Optimierung:** Automatische EAN-Erkennung über Keyboard-Events
- **Toggle-Funktionalität:** Ein-/Ausschaltbarer Scanner-Modus
- **Parent-Produkt-Lookup:** EAN → Variante → Parent-Produkt Ermittlung
- **Dual-Mode:** Scanner + Tabelle parallel nutzbar

## Größensortierung (Version 1.5.0)
- **Numerische Größen:** 34, 36, 38, 40, 42, 44, 46, 48, 50
- **Buchstaben-Größen:** XS, S, M, L, XL
- **Sortier-Logik:** Numerisch aufsteigend, dann alphabetisch
- **Implementation:** Custom Comparator-Funktion

## Quick Order vs. Sampling Unterschiede
### Quick Order System:
- **Varianten-Anzeige:** Alle Größen einzeln für Multi-Größen-Bestellung
- **EAN-Scanner:** Filtert Tabelle nach Parent-Produkt (alle Größen anzeigen)
- **Add to Cart:** Standard WooCommerce mit Größenauswahl

### Sampling System:
- **Varianten-Anzeige:** Nur Parent-Produkte (DisableSingleVariations)
- **EAN-Scanner:** Direkter Add to Cart ohne Größe
- **Add to Cart:** Parent-Produkt ohne Größenauswahl

## AJAX-Endpoints (Version 1.4.0)
- **`mc_load_collection`:** Collections-Navigation mit Tabellen-Reinitialisierung
- **`mc_search_product_by_ean`:** EAN-Scanner für Sampling-System
- **`mc_get_cart_totals`:** Live-Preisberechnung
- **`mc_search_customers`:** User Switching Integration

## Geplante AJAX-Endpoints (Version 1.5.0)
- **`mc_scan_ean_quick_order`:** EAN-Scanner für Quick Order (Tabellen-Filter)
- **`mc_load_parent_variants`:** Parent-Produkt → alle Varianten laden

## JavaScript-Patterns
- **Event-Delegation:** Für dynamisch geladene Inhalte
- **Debouncing:** Für EAN-Scanner Input-Events
- **Promise-basierte AJAX:** Für bessere Error-Handling
- **Modular-Struktur:** Getrennte JS-Dateien für verschiedene Systeme

## CSS-Patterns
- **BEM-Notation:** Block__Element--Modifier für CSS-Klassen
- **Responsive-First:** Mobile-optimierte Layouts
- **Loading-States:** Visuelle Feedback-Systeme
- **Touch-Optimierung:** Größere Touch-Targets für Mobile

## Änderungsverfolgung
- Änderungen in der Memory Bank dokumentieren
- Versionsnummern und Zeitstempel aktualisieren
- CHANGELOG.md für jede Version aktualisieren
- Debug-Logs für kritische AJAX-Operations

## Testing-Patterns (Version 1.5.0)
- **Header-Reinitialisierung:** Nach jedem AJAX-Kollektionswechsel prüfen
- **Größensortierung:** Alle Größen-Kombinationen testen
- **EAN-Scanner:** Verschiedene EAN-Formate und Edge-Cases
- **Cross-Browser:** Chrome, Firefox, Safari, Edge
- **Mobile-Testing:** iOS Safari, Android Chrome

## Performance-Patterns
- **AJAX-Caching:** Häufig geladene Collections cachen
- **Lazy-Loading:** Große Tabellen erst bei Bedarf laden
- **Debouncing:** EAN-Scanner Input-Events optimieren
- **Minimal-DOM-Updates:** Nur notwendige Bereiche aktualisieren

## 🚨 KRITISCHES TIMING-PROBLEM PATTERN (26.06.2025)
**IDENTIFIZIERT UND GELÖST - UNIVERSELLER LÖSUNGSANSATZ:**

### **Problem-Kategorie: AJAX-Reinitialisierung nach DOM-Updates**
- **Symptom:** JavaScript-Komponenten funktionieren nicht nach AJAX-Content-Reload
- **Ursache:** Timing-Race-Condition zwischen DOM-Update und JavaScript-Reinitialisierung
- **Häufigkeit:** Tritt bei allen AJAX-basierten Content-Updates auf

### **Root Cause Analysis:**
1. **DOM-Update-Timing:** Browser benötigt Zeit für vollständige DOM-Aktualisierung
2. **Plugin-State-Management:** Plugins (z.B. DataTables, WooCommerce) blockieren Re-Initialisierung
3. **Event-Handler-Verlust:** Dynamisch geladener Content verliert Event-Handler

### **UNIVERSELLE LÖSUNG - "TIMING-FIX PATTERN":**

#### **1. Plugin-State-Reset (KRITISCH):**
```javascript
// Entferne Plugin-Markierungen vor Re-Initialisierung
$("body").removeData('plugin_quickOrder');
$("element").removeData('plugin_dataTable');
```

#### **2. DOM-Update-Delay (ESSENTIAL):**
```javascript
// Warte auf vollständige DOM-Aktualisierung
setTimeout(function() {
    reinitializeComponents();
}, 50); // 50ms Minimum-Delay
```

#### **3. Event-Handler-Reinitialisierung:**
```javascript
// Re-initialisiere alle Event-Handler nach AJAX-Update
function reinitializeAfterAjax() {
    // 1. Plugin-State zurücksetzen
    resetPluginStates();
    
    // 2. DOM-Update abwarten
    setTimeout(function() {
        // 3. Komponenten reinitialisieren
        reinitializePlugins();
        reinitializeEventHandlers();
    }, 50);
}
```

### **Anwendungsbeispiele:**
- **DataTables:** Nach AJAX-Content-Update komplett neu initialisieren
- **WooCommerce Quick Order:** Plugin-State zurücksetzen vor Re-Init
- **Event-Handler:** Alle dynamischen Events nach DOM-Update neu binden
- **Custom Components:** Timing-Delay für alle JavaScript-Komponenten
- **EAN-Scanner Initialisierung (26.06.2025):**
    - **Problem:** EAN-Scanner wurde zu früh initialisiert und/oder statisch in PHP-Templates dupliziert, bevor DataTables-Elemente im DOM verfügbar waren.
    - **Lösung:** Implementierung einer `checkAndInitializeEanScanner()` Funktion in `mc-collections-ajax.js`, die rekursiv auf das Vorhandensein des `.dataTables_filter` Elements wartet, bevor der Scanner erstellt und Events gebunden werden. Entfernung des statischen HTML-Outputs aus `includes/class-mc-order-system.php`.

### **Debugging-Strategie:**
1. **Console-Logs:** Vor/nach DOM-Update und Re-Initialisierung
2. **Element-Existence-Check:** Prüfen ob Elemente nach Update existieren
3. **Plugin-State-Monitoring:** Plugin-Daten vor/nach Reset überprüfen
4. **Timing-Variation:** Delay-Zeit anpassen falls 50ms nicht ausreicht

### **Präventive Maßnahmen:**
- **Immer** Plugin-States vor Re-Initialisierung zurücksetzen
- **Immer** Timing-Delay nach DOM-Updates einbauen
- **Immer** Event-Handler nach AJAX-Updates reinitialisieren
- **Niemals** sofortige Re-Initialisierung ohne Delay

**MERKSATZ:** "AJAX-Update → Plugin-Reset → Timing-Delay → Re-Init"

### **SKU-Pattern-Matching (26.06.2025):**
**Für Maison Common SKU-System:**
- **Varianten-SKU:** `1-241-1299102-1-130-0-34`
- **Parent-SKU:** `1-241-1299102-130`
- **Transformation-Regex:** `/-1-(\d+)-0-\d+$/` → `-$1`
- **Anwendung:** EAN-Scanner Filterung, Parent-Produkt-Ermittlung
