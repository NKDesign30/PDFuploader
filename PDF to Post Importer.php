<?php

/**
 * Plugin Name: PDF to Post Importer
 * Description: Import PDF files and convert them into WordPress posts.
 * Version: 1.0.1
 * Author: Niko
 */

// Sicherstellen, dass das Skript nicht direkt aufgerufen wird
if (!defined('ABSPATH')) {
  exit;
}

// Composer Autoloader einbinden
require_once __DIR__ . '/vendor/autoload.php';

// Hook für das Hinzufügen eines Menüeintrags im Admin-Bereich
add_action('admin_menu', 'pdf_to_post_importer_menu');

function pdf_to_post_importer_menu()
{
  add_menu_page('PDF to Post Importer', 'PDF Importer', 'manage_options', 'pdf-to-post-importer', 'pdf_to_post_importer_page', 'dashicons-media-text');
}

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

function handle_pdf_file_upload()
{
  if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] == UPLOAD_ERR_OK) {
    $file_tmp_name = $_FILES['pdf_file']['tmp_name'];
    $file_name = $_FILES['pdf_file']['name'];

    $upload_dir = wp_upload_dir();
    $target_dir = trailingslashit($upload_dir['basedir']) . 'PDF-Upload/';
    $target_file = $target_dir . basename($file_name);

    if (!file_exists($target_dir)) {
      wp_mkdir_p($target_dir);
    }

    if (move_uploaded_file($file_tmp_name, $target_file)) {
      convert_pdf_to_post($target_file);
    } else {
      wp_die('Fehler beim Hochladen der Datei.');
    }
  } else {
    wp_die('Bitte eine gültige PDF-Datei hochladen.');
  }
}

function convert_pdf_to_post($file_path)
{
  $parser = new \Smalot\PdfParser\Parser();
  $pdf    = $parser->parseFile($file_path);
  $text   = $pdf->getText();

  $post_data = array(
    'post_title'    => 'PDF Import: ' . basename($file_path),
    'post_content'  => wp_kses_post(nl2br($text)),
    'post_status'   => 'publish',
    'post_author'   => get_current_user_id(),
    'post_type'     => 'post', // oder einen anderen Post-Typ, falls benötigt
  );

  $post_id = wp_insert_post($post_data);

  if (!$post_id) {
    wp_die('Fehler beim Erstellen des Beitrags.');
  }

  wp_redirect(admin_url('post.php?action=edit&post=' . $post_id));
  exit;
}
function clean_extracted_text($text)
{
  // Entfernen von übermäßigen Leerzeichen
  $text = preg_replace('/[ ]{2,}/', ' ', $text);
  // Entfernen von Leerzeichen vor und nach Zeilenumbrüchen
  $text = preg_replace('/\s*\n\s*/', "\n", $text);
  // Ersetzen von mehreren Zeilenumbrüchen durch einen einzigen
  $text = preg_replace('/[\r\n]{2,}/', "\n\n", $text);
  // Weitere Bereinigungen können hier hinzugefügt werden...

  return $text;
}

// Verwenden Sie diese Funktion nach der Extraktion des Textes aus der PDF
$cleaned_text = clean_extracted_text($text);
