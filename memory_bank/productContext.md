# Produktkontext - Maison Cormont Quick Order System
Version: 2.00
Timestamp: 2025-12-06 16:32 CET

## Projektzweck
- B2B Order-System für Maison Cormont (Modelabel)
- AJAX-basierte Collections-Navigation mit Quick Order Tabellen
- Ausschließlich für interne Mitarbeiter des Labels
- Direkter Einstieg zur Bestelltabelle ohne Kundeninformationen

## Komponenten
- WordPress + WooCommerce
- Astra Theme
- WooCommerce Quick Order Plugin (mit Collections-Support)
- PODS (für Collections-Taxonomie)
- Custom AJAX-System für nahtlose Navigation

## Collections-System
- Taxonomie: `collection` (mit PODS angelegt)
- Circa 10-12 Collections (zwei Kollektionen pro Jahr)
- Sortierung nach `menu_order`
- Aktuelle Collections: 1-200 Produkte, ältere deutlich weniger

## Anforderungen
- Schnelle AJAX-Navigation zwischen Collections
- Sidebar mit Collections-Liste (nach menu_order sortiert)
- Quick Order Tabellen mit erweitertem Shortcode
- Responsive Design für verschiedene Geräte
- Browser History Support
- Loading-Animationen für bessere UX

## Shortcode-Format
```
[woocommerce_quick_order_table taxonomy="collection" categories="ID" order="DESC" orderby="menu_order" only_on_stock="no"]
```

## Technische Architektur
- Hauptfunktionen: `mc-quick-order-functions.php`
- AJAX-Handler: JavaScript in `mc-collections-ajax.js`
- Styling: `mc-collections-style.css`
- Integration über Theme `functions.php`
