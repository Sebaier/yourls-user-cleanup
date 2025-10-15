# 🧹 YOURLS User Cleanup Plugin

Ein YOURLS-Plugin zur automatischen Bereinigung alter ShortURLs bestimmter Benutzerkonten.  
Das Plugin löscht alle Kurzlinks, die älter als eine festgelegte Anzahl von Wochen sind und von ausgewählten Nutzern erstellt wurden.

---

## 📋 Funktionsbeschreibung

Das **YOURLS User Cleanup Plugin** ermöglicht Administratoren, gezielt alte ShortURLs zu entfernen, die über den **Multi-User-Modus** (bereitgestellt durch das Plugin [AuthMgrPlus](https://github.com/joshp23/YOURLS-AuthMgrPlus)) erstellt wurden.  
Es eignet sich insbesondere für Umgebungen, in denen temporäre oder automatisiert erzeugte Links (z. B. API-Zugriffe oder Kampagnen-URLs) regelmäßig bereinigt werden sollen.

---

## ⚠️ Wichtiger Hinweis zur Kompatibilität

> **Dieses Plugin funktioniert derzeit nur in Kombination mit dem Plugin [AuthMgrPlus](https://github.com/joshp23/YOURLS-AuthMgrPlus).**  
>  
> YOURLS speichert standardmäßig keinen Benutzernamen in der URL-Tabelle.  
> AuthMgrPlus erweitert die Datenbank um eine Spalte `user`, in der der Ersteller der ShortURL gespeichert wird.  
> Ohne diese Erweiterung kann das Plugin keine Zuordnung zu Benutzern herstellen – und somit auch keine gezielte Bereinigung durchführen.

---

## 🧩 Features

- ✅ Auswahl einzelner Benutzer, deren Links bereinigt werden sollen  
- 🕒 Auswahl des Alters der zu löschenden Links (z. B. 1, 2, 4, 8 Wochen usw.)  
- 🧠 Schutzprüfung: Löscht nur, wenn mindestens ein Benutzer ausgewählt ist  
- 🗄️ Kompatibel mit **MariaDB** und **MySQL**  
- 🧰 Bedienung vollständig über das YOURLS-Admin-Interface  

---

## ⚙️ Installation

1. Lade das Plugin herunter oder klone das Repository:  
    ```bash
   git clone https://github.com/Sebaier/yourls-user-cleanup.git
    ````
2. Kopiere den Plugin-Ordner in dein YOURLS-Verzeichnis:
   ```
   /user/plugins/yourls-user-cleanup/
   ```
3. Aktiviere das Plugin im YOURLS-Adminbereich unter **Admin → Plugins**.
4. Stelle sicher, dass **AuthMgrPlus** aktiv ist und Benutzer-Daten in der Tabelle `yourls_url` unter der Spalte `user` gespeichert werden.

---

## 🧭 Verwendung

1. Öffne im Adminbereich die Seite:
   **User Cleanup**
2. Wähle:
   
   * Das Alter der Links (Dropdown)
   * Einen oder mehrere Benutzer (Checkbox)
3. Klicke auf **„Vorschau anzeigen“**
4. Klicke auf **"Jetzt x Links löschen"**
5. Das Plugin löscht automatisch alle Einträge, deren `timestamp` älter als der gewählte Zeitraum ist.

---

## 🧰 Voraussetzungen

| Komponente         | Erforderlich     |
| ------------------ | ---------------- |
| YOURLS             | ≥ 1.9            |
| PHP                | ≥ 7.4            |
| MariaDB/MySQL      | ✅                |
| AuthMgrPlus Plugin | **erforderlich** |

---

## 🚀 Zukünftige Erweiterungen

* 🕓 Automatische Bereinigung per Cron-Job
* 🔄 Unterstützung ohne AuthMgrPlus (z. B. über benutzerdefinierte Metafelder)
* 🌐 Mehrsprachigkeit

---

## 📄 Lizenz

Dieses Projekt steht unter der [MIT License](LICENSE).
