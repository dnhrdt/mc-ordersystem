# Systemmuster - Version 1.5.0 Entwicklung
Version: 2.00
Timestamp: 2025-06-25 15:24 CET

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
