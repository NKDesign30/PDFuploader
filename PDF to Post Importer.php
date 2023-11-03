<?php

/**
 * Plugin Name: PDF to Post Importer
 * Description: Import PDF files and convert them into WordPress posts with formatted HTML content.
 * Version: 1.0.0
 * Author: Ihr Name
 */

// Sicherstellen, dass das Skript nicht direkt aufgerufen wird
if (!defined('ABSPATH')) {
  exit;
}

// Hook für das Hinzufügen eines Menüeintrags im Admin-Bereich
add_action('admin_menu', 'pdf_to_post_importer_menu');

// Funktion zum Erstellen des Menüeintrags
function pdf_to_post_importer_menu()
{
  add_menu_page('PDF to Post Importer', 'PDF Importer', 'manage_options', 'pdf-to-post-importer', 'pdf_to_post_importer_page', 'dashicons-media-text');
}

// Funktion zum Anzeigen der Upload-Seite im Admin-Bereich
function pdf_to_post_importer_page()
{
?>
  <div class="wrap">
    <h1>PDF to Post Importer</h1>
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
      <input type="hidden" name="action" value="pdf_file_upload">
      <input type="file" name="pdf_file" required>
      <input type="submit" value="Upload und Import" class="button button-primary">
    </form>
  </div>
<?php
}

// Hook für das Verarbeiten des Uploads
add_action('admin_post_pdf_file_upload', 'handle_pdf_file_upload');

// Funktion zum Verarbeiten des hochgeladenen PDF-Files
function handle_pdf_file_upload()
{
  // Überprüfen, ob die Datei vorhanden ist und der Upload kein Fehler war
  if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == UPLOAD_ERR_OK) {
    // Dateiinformationen
    $file_tmp_name = $_FILES['pdf_file']['tmp_name'];
    $file_name = $_FILES['pdf_file']['name'];

    // Zielverzeichnis
    $upload_dir = wp_upload_dir();
    $target_dir = trailingslashit($upload_dir['basedir']) . 'PDF-Upload/';
    $target_file = $target_dir . basename($file_name);

    // Verzeichnis erstellen, falls nicht vorhanden
    if (!file_exists($target_dir)) {
      wp_mkdir_p($target_dir);
    }

    // Verschieben der Datei in das Zielverzeichnis
    if (move_uploaded_file($file_tmp_name, $target_file)) {
      // PDF-Datei in einen Beitrag umwandeln
      convert_pdf_to_post($target_file);
    } else {
      // Fehler beim Hochladen der Datei
      wp_die('Fehler beim Hochladen der Datei.');
    }
  } else {
    // Keine Datei hochgeladen oder ein Fehler ist aufgetreten
    wp_die('Bitte eine gültige PDF-Datei hochladen.');
  }
}

// Funktion zum Konvertieren der PDF-Datei in einen WordPress-Beitrag
function convert_pdf_to_post($file_path)
{
  // Stellen Sie sicher, dass die PDFParser-Bibliothek geladen ist
  require_once 'vendor/autoload.php';

  // PDF-Datei laden und Text extrahieren
  $parser = new \Smalot\PdfParser\Parser();
  $pdf = $parser->parseFile($file_path);
  $text = $pdf->getText();

  // Text zu HTML konvertieren
  $html_content = text_to_html($text);

  // Beitrag erstellen
  $post_data = array(
    'post_title'    => wp_strip_all_tags('PDF Import: ' . basename($file_name)),
    'post_content'  => $html_content,
    'post_status'   => 'publish',
    'post_author'   => get_current_user_id(),
    'post_type'     => 'post', // oder Ihren benutzerdefinierten Post-Typ
  );

  // Beitrag einfügen
  $post_id = wp_insert_post($post_data);

  // Überprüfen, ob der Beitrag erstellt wurde
  if (!$post_id) {
    // Fehler beim Erstellen des Beitrags
    wp_die('Fehler beim Erstellen des Beitrags.');
  }

  // Erfolg! Weiterleitung zur Beitragsseite
  wp_redirect(admin_url('edit.php'));
  exit;
}

// Funktion zum Umwandeln von Text in HTML
function text_to_html($text)
{
  // Hier kommt Ihre Logik zum Konvertieren des Textes in HTML
  // Dies ist ein Platzhalter und sollte durch Ihre eigene Logik ersetzt werden
  $html = '<p>' . nl2br($text) . '</p>';
  return $html;
}
