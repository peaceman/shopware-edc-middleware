# Bestelluebertragung
## SW OrderFetcher
1. Ruft Bestellungen von Shopware ab
    1. Bestellstatus
        1. offen / 0
    1. Zahlungsstatus
        1. komplett Bezahlt / 12
        1. komplett in Rechnung gestellt / 10
1. Speichert die Bestellung mit SW Transferstatus OPEN in der Datenbank
    1. Bestellung ist neu
        1. Wird mit EDC Transferstatus OPEN und den erhaltenen Daten abgespeichert
    1. Bestellung ist bereits bekannt
        1. EDC Transferstatus ist ERROR
            1. Bestellung wird mit den erhaltenen Daten aktualisiert
            1. EDC Transferstatus wird auf OPEN gesetzt
        1. EDC Transferstatus ist OPEN
            1. Bestellung wird mit den erhaltenen Daten aktualisiert
        1. EDC Transferstatus ist COMPLETED oder WAITING
            1. Erhaltene Daten werden ignoriert
        
## EDC OrderExporter
1. Bestellungen mit Transferstatus OPEN werden an EDC exportiert
    1. Export ist erfolgreich
        1. EDC Transferstatus wird auf WAITING gesetzt
        1. EDC OrderNumber wird gespeichert
        1. SW Transferstatus wird auf OPEN gesetzt
    1. Export schlaegt fehl (EDC seitig)
        1. Fehler wird an der Bestellung gespeichert
        1. EDC Transferstatus wird auf ERROR gesetzt
        1. SW Transferstatus wird auf OPEN gesetzt

## SW OrderUpdater
1. Aktualisiert die Bestellung mit SW Transferstatus OPEN in Shopware anhand der vorhandenen Daten
    1. TrackingNumber (falls vorhanden)
    1. EDCOrderNumber (falls vorhanden)
    1. EDCErrors (falls vorhanden)
        1. Alle vorhandenen Errors werden in ein Attribut zusammengefasst
    1. Bestellstatus
        1. EDC Transferstatus ist OPEN
            1. in Bearbeitung (wartet) / 1
        1. EDC Transferstatus ist COMPLETED
            1. komplett Abgeschlossen / 2
        1. EDC Transferstatus ist ERROR
            1. Klaerung notwendig / 8
        1. EDC Transferstatus ist WAITING
            1. in Bearbeitung (wartet) / 1
1. SW Transferstatus wird auf COMPLETED gesetzt

## EDC OrderStatus Update Endpoint
1. ShippedOrder
    1. Setzt die TrackingNumber
    1. EDC Transferstatus wird auf COMPLETED gesetzt
    1. SW Transferstatus wird auf OPEN gesetzt
1. BackOrder
    1. wird erstmal ignoriert
