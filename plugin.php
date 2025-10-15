<?php
/*
Plugin Name: YOURLS User Cleanup
Plugin URI: https://github.com/sebaier/yourls-user-cleanup
Description: Löscht alte ShortURLs bestimmter Benutzer die älter als X Wochen sind.
Version: 0.3.1
Author: Sebaier
Author URI: https://sebaier.de
*/

// Sicherheitsabfrage – verhindert direkten Aufruf der Datei
if (!defined('YOURLS_ABSPATH')) {
    die('No direct access allowed');
}

// Konfiguration
define('YOURLS_CLEANUP_WEEKS_OPTIONS', [52, 24, 12, 8, 4, 2, 1, 0]);

/**
 * Plugin-Seite im YOURLS-Admin-Bereich registrieren
 */
yourls_add_action('plugins_loaded', 'yourls_user_cleanup_add_page');
function yourls_user_cleanup_add_page() {
    yourls_register_plugin_page(
        'user_cleanup',
        'User Cleanup',
        'yourls_user_cleanup_page'
    );
}

/**
 * Hauptfunktion - Inhalt der Admin-Seite
 */
function yourls_user_cleanup_page() {
    $nonce = yourls_create_nonce('user_cleanup_action');

    echo '<h2>YOURLS User Cleanup</h2>';
    echo '<p>Hier kannst du alte Links löschen, die älter als eine bestimmte Anzahl von Wochen sind.</p>';

    // Prüfe ob Multi-User-System aktiv ist
    $multi_user_mode = yourls_user_cleanup_is_multi_user();

    // Vorschau-Modus
    if (isset($_POST['preview_submit']) && isset($_POST['nonce']) && yourls_verify_nonce('user_cleanup_action', $_POST['nonce'])) {
        yourls_user_cleanup_handle_preview($multi_user_mode);
    }

    // Lösch-Modus
    if (isset($_POST['cleanup_submit']) && isset($_POST['nonce']) && yourls_verify_nonce('user_cleanup_action', $_POST['nonce'])) {
        yourls_user_cleanup_handle_delete($multi_user_mode);
    }

    // Benutzer abrufen (falls Multi-User aktiv) und Formular anzeigen
    $available_users = $multi_user_mode ? yourls_user_cleanup_get_available_users() : [];
    yourls_user_cleanup_show_form($nonce, $available_users, $multi_user_mode);
}

/**
 * Prüft ob Multi-User-System aktiv ist
 */
function yourls_user_cleanup_is_multi_user() {
    $ydb = yourls_get_db();
    $table = YOURLS_DB_TABLE_URL;
    
    try {
        // Prüfe ob user-Spalte existiert und befüllt ist
        $result = $ydb->fetchValue("SELECT COUNT(*) FROM `$table` WHERE `user` IS NOT NULL AND `user` <> ''");
        return (intval($result) > 0);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verarbeitet Vorschau-Anfrage
 */
function yourls_user_cleanup_handle_preview($multi_user_mode = true) {
    $weeks = intval($_POST['weeks']);
    $selected_users = ($multi_user_mode && isset($_POST['selected_users'])) ? $_POST['selected_users'] : null;

    if ($multi_user_mode && empty($selected_users)) {
        echo '<div class="notice notice-warning"><p><strong>Bitte wähle mindestens einen Benutzer aus!</strong></p></div>';
        return;
    }
    
    if (!in_array($weeks, YOURLS_CLEANUP_WEEKS_OPTIONS)) {
        echo '<div class="notice notice-error"><p><strong>Ungültiger Wochenwert!</strong></p></div>';
        return;
    }

    $links_to_delete = yourls_user_cleanup_get_old_links($selected_users, $weeks);
    $count = count($links_to_delete);
    
    echo '<div class="notice notice-info" style="padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">';
    echo '<p><strong>Vorschau:</strong></p>';
    echo '<p>Es würden <strong style="color: #d9534f; font-size: 18px;">' . $count . ' Links</strong> gelöscht werden.</p>';
    
    if ($multi_user_mode && !empty($selected_users)) {
        echo '<p>Betroffene Benutzer: <strong>' . implode(', ', array_map('htmlspecialchars', $selected_users)) . '</strong></p>';
    }
    
    echo '<p>Zeitraum: Links älter als <strong>' . $weeks . ' Woche' . ($weeks > 1 ? 'n' : '') . '</strong></p>';
    
    if ($count > 0) {
        yourls_user_cleanup_show_links_table($links_to_delete, $multi_user_mode);
        yourls_user_cleanup_show_delete_form($count, $weeks, $selected_users, $multi_user_mode);
    } else {
        echo '<p style="color: #666; font-style: italic;">Keine Links gefunden, die den Kriterien entsprechen.</p>';
    }
    
    echo '</div>';
}

/**
 * Verarbeitet Lösch-Anfrage
 */
function yourls_user_cleanup_handle_delete($multi_user_mode = true) {
    $weeks = intval($_POST['weeks']);
    $selected_users = ($multi_user_mode && isset($_POST['selected_users'])) ? $_POST['selected_users'] : null;

    if ($multi_user_mode && empty($selected_users)) {
        echo '<div class="notice notice-warning"><p><strong>Fehler: Keine Benutzer ausgewählt!</strong></p></div>';
        return;
    }
    
    if (!in_array($weeks, YOURLS_CLEANUP_WEEKS_OPTIONS)) {
        echo '<div class="notice notice-error"><p><strong>Ungültiger Wochenwert!</strong></p></div>';
        return;
    }

    $deleted = yourls_user_cleanup_delete_old_links($selected_users, $weeks);

    if ($deleted > 0) {
        echo '<div class="notice notice-success"><p><strong>Erfolg: ' . $deleted . ' alte Links wurden gelöscht.</strong></p></div>';
        
        // Log-Eintrag (falls YOURLS Logging unterstützt)
        if (function_exists('yourls_log')) {
            $log_msg = 'User Cleanup: ' . $deleted . ' Links gelöscht';
            if ($multi_user_mode && !empty($selected_users)) {
                $log_msg .= ' von Benutzern: ' . implode(', ', $selected_users);
            }
            yourls_log($log_msg);
        }
    } else {
        echo '<div class="notice notice-info"><p><strong>Info: Keine alten Links gefunden, die den Kriterien entsprechen.</strong></p></div>';
    }
}

/**
 * Zeigt Tabelle mit betroffenen Links
 */
function yourls_user_cleanup_show_links_table($links, $multi_user_mode = true) {
    echo '<div style="margin-top: 20px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; background: white;">';
    echo '<table style="width: 100%; border-collapse: collapse;">';
    echo '<thead style="position: sticky; top: 0; background: #f5f5f5;">';
    echo '<tr>';
    echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Keyword</th>';
    echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Short URL</th>';
    echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Ziel-URL</th>';
    
    if ($multi_user_mode) {
        echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Benutzer</th>';
    }
    
    echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Erstellt am</th>';
    echo '<th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">Klicks</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($links as $link) {
        $short_url = YOURLS_SITE . '/' . $link['keyword'];
        $age_days = floor((time() - strtotime($link['timestamp'])) / 86400);
        
        echo '<tr style="border-bottom: 1px solid #eee;">';
        echo '<td style="padding: 8px; font-family: monospace;"><strong>' . htmlspecialchars($link['keyword']) . '</strong></td>';
        echo '<td style="padding: 8px;"><a href="' . htmlspecialchars($short_url) . '" target="_blank" style="color: #0073aa;">' . htmlspecialchars($short_url) . '</a></td>';
        echo '<td style="padding: 8px; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . htmlspecialchars($link['url']) . '">' . htmlspecialchars($link['url']) . '</td>';
        
        if ($multi_user_mode) {
            echo '<td style="padding: 8px;">' . htmlspecialchars($link['user']) . '</td>';
        }
        
        echo '<td style="padding: 8px; white-space: nowrap;">' . htmlspecialchars($link['timestamp']) . '<br><small style="color: #666;">(' . $age_days . ' Tage alt)</small></td>';
        echo '<td style="padding: 8px; text-align: center;">' . intval($link['clicks']) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

/**
 * Zeigt Lösch-Formular nach Vorschau
 */
function yourls_user_cleanup_show_delete_form($count, $weeks, $selected_users, $multi_user_mode = true) {
    $nonce = yourls_create_nonce('user_cleanup_action');
    
    echo '<form method="post" style="margin-top: 20px;" onsubmit="return confirm(\'WARNUNG: ' . $count . ' Links werden unwiderruflich gelöscht! Fortfahren?\');">';
    echo '<input type="hidden" name="nonce" value="' . $nonce . '">';
    echo '<input type="hidden" name="weeks" value="' . $weeks . '">';
    
    if ($multi_user_mode && !empty($selected_users)) {
        foreach ($selected_users as $user) {
            echo '<input type="hidden" name="selected_users[]" value="' . htmlspecialchars($user) . '">';
        }
    }
    
    echo '<button type="submit" name="cleanup_submit" class="button-primary" style="background: #d9534f; border-color: #d43f3a; font-size: 16px; padding: 10px 20px;">';
    echo 'Jetzt ' . $count . ' Link' . ($count > 1 ? 's' : '') . ' löschen';
    echo '</button>';
    echo '</form>';
}

/**
 * Zeigt Haupt-Formular
 */
function yourls_user_cleanup_show_form($nonce, $available_users, $multi_user_mode = true) {
    echo '<form method="post" style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-radius: 5px; margin-top: 20px;">';
    echo '<input type="hidden" name="nonce" value="' . $nonce . '">';

    // Info-Box für Single-User-Modus
    if (!$multi_user_mode) {
        echo '<div style="background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin-bottom: 20px;">';
        echo '<p style="margin: 0;"><strong>Info:</strong> Es wurde kein Multi-User-Plugin erkannt. Alle Links werden nach Alter gefiltert, unabhängig vom Ersteller.</p>';
        echo '</div>';
    }

    // Auswahl Alter
    echo '<div style="margin-bottom: 20px;">';
    echo '<label for="weeks"><strong>Links die älter sind als:</strong></label> ';
    echo '<select name="weeks" id="weeks" style="padding: 5px; font-size: 14px;">';
    
    foreach (YOURLS_CLEANUP_WEEKS_OPTIONS as $w) {
        echo '<option value="' . $w . '">' . $w . ' Woche' . ($w > 1 ? 'n' : '') . '</option>';
    }
    
    echo '</select>';
    echo '</div>';

    // Checkboxen für Benutzer (nur im Multi-User-Modus)
    if ($multi_user_mode) {
        if (!empty($available_users)) {
            echo '<div style="margin-bottom: 20px;">';
            echo '<strong style="font-size: 16px;">Benutzer auswählen (' . count($available_users) . ' gefunden):</strong><br><br>';
            echo '<div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: white;">';
            
            // Batch-Abruf aller User-Link-Counts für bessere Performance
            $user_counts = yourls_user_cleanup_get_all_user_counts($available_users);
            
            foreach ($available_users as $user) {
                $user_count = isset($user_counts[$user]) ? $user_counts[$user] : 0;
                
                echo '<label style="display: block; padding: 8px; margin-bottom: 5px; background: #f9f9f9; border: 1px solid #e0e0e0; border-radius: 3px; cursor: pointer;" onmouseover="this.style.background=\'#e8f5e9\'" onmouseout="this.style.background=\'#f9f9f9\'">';
                echo '<input type="checkbox" name="selected_users[]" value="' . htmlspecialchars($user) . '" style="margin-right: 8px;"> ';
                echo '<strong>' . htmlspecialchars($user) . '</strong>';
                echo ' <span style="color: #666;">(' . $user_count . ' Link' . ($user_count != 1 ? 's' : '') . ')</span>';
                echo '</label>';
            }
            
            echo '</div>';
            echo '</div>';
        } else {
            echo '<p style="background: #f8d7da; padding: 10px; border-left: 4px solid #dc3545; margin-bottom: 20px;">';
            echo '<strong>Keine Benutzer mit Links gefunden.</strong><br>';
            echo 'Mögliche Ursachen: YOURLS läuft ohne Multi-User-Plugin oder es wurden noch keine Links mit Benutzerzuordnung erstellt.';
            echo '</p>';
        }
    }

    echo '<button type="submit" class="button" name="preview_submit" style="padding: 10px 20px; font-size: 14px;">Vorschau anzeigen</button>';
    echo ' <small style="color: #666;">Zeigt an, welche Links betroffen wären, ohne sie zu löschen.</small>';
    echo '</form>';
}

/**
 * Holt alle verfügbaren Benutzer aus der Datenbank
 */
function yourls_user_cleanup_get_available_users() {
    $ydb = yourls_get_db();
    $table = YOURLS_DB_TABLE_URL;
    $users = [];
    
    try {
        $sql = "SELECT DISTINCT `user` FROM `$table` WHERE `user` IS NOT NULL AND `user` <> '' ORDER BY `user` ASC";
        $results = $ydb->fetchAll($sql);
        
        if ($results) {
            foreach ($results as $row) {
                $users[] = $row['user'];
            }
        }
    } catch (Exception $e) {
        // Fehler wird stillschweigend behandelt, leeres Array wird zurückgegeben
    }
    
    return $users;
}

/**
 * Holt Link-Counts für alle Benutzer in einer Query (Performance-Optimierung)
 */
function yourls_user_cleanup_get_all_user_counts($users) {
    $ydb = yourls_get_db();
    $table = YOURLS_DB_TABLE_URL;
    $counts = [];
    
    if (empty($users)) {
        return $counts;
    }
    
    try {
        // Erstelle Platzhalter für IN-Klausel
        $placeholders = [];
        $binds = [];
        
        foreach ($users as $index => $user) {
            $key = 'user' . $index;
            $placeholders[] = ':' . $key;
            $binds[$key] = $user;
        }
        
        $in_clause = implode(',', $placeholders);
        $sql = "SELECT `user`, COUNT(*) as count FROM `$table` WHERE `user` IN ($in_clause) GROUP BY `user`";
        
        $results = $ydb->fetchAll($sql, $binds);
        
        if ($results) {
            foreach ($results as $row) {
                $counts[$row['user']] = intval($row['count']);
            }
        }
    } catch (Exception $e) {
        // Fehler wird stillschweigend behandelt
    }
    
    return $counts;
}

/**
 * Holt alle Links, die gelöscht werden würden (für Vorschau)
 */
function yourls_user_cleanup_get_old_links($users, $weeks) {
    $ydb = yourls_get_db();
    
    if (empty($users)) {
        return [];
    }

    // YOURLS verwendet DATETIME-Format
    $threshold_unix = time() - ($weeks * 7 * 24 * 60 * 60);
    $threshold = date('Y-m-d H:i:s', $threshold_unix);
    $table = YOURLS_DB_TABLE_URL;
    
    // Erstelle Platzhalter für IN-Klausel
    $placeholders = [];
    $binds = ['threshold' => $threshold];
    
    foreach ($users as $index => $user) {
        $key = 'user' . $index;
        $placeholders[] = ':' . $key;
        $binds[$key] = $user;
    }
    
    $in_clause = implode(',', $placeholders);
    
    try {
        $sql = "SELECT `keyword`, `url`, `title`, `timestamp`, `user`, `clicks` 
                FROM `$table` 
                WHERE `timestamp` < :threshold AND `user` IN ($in_clause) 
                ORDER BY `timestamp` ASC";
        
        $results = $ydb->fetchAll($sql, $binds);
        return $results ? $results : [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Löscht alte Links
 */
function yourls_user_cleanup_delete_old_links($users, $weeks) {
    $ydb = yourls_get_db();

    // YOURLS verwendet DATETIME-Format
    $threshold_unix = time() - ($weeks * 7 * 24 * 60 * 60);
    $threshold = date('Y-m-d H:i:s', $threshold_unix);
    $table = YOURLS_DB_TABLE_URL;

    try {
        // Single-User-Modus (keine Benutzer-Filterung)
        if ($users === null || empty($users)) {
            $sql = "DELETE FROM `$table` WHERE `timestamp` < :threshold";
            $binds = ['threshold' => $threshold];
            
            $affected = $ydb->fetchAffected($sql, $binds);
            return intval($affected);
        }
        
        // Multi-User-Modus (mit Benutzer-Filterung)
        $placeholders = [];
        $binds = ['threshold' => $threshold];
        
        foreach ($users as $index => $user) {
            $key = 'user' . $index;
            $placeholders[] = ':' . $key;
            $binds[$key] = $user;
        }
        
        $in_clause = implode(',', $placeholders);
        $sql = "DELETE FROM `$table` WHERE `timestamp` < :threshold AND `user` IN ($in_clause)";

        $affected = $ydb->fetchAffected($sql, $binds);
        return intval($affected);
    } catch (Exception $e) {
        return 0;
    }
}
