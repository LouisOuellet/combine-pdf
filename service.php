<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/imap.php';
require_once dirname(__FILE__) . '/src/lib/smtp.php';
require_once dirname(__FILE__) . '/src/lib/pdf.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Adding Librairies
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);
$SMTP = new apiSMTP($settings['smtp']['host'],$settings['smtp']['port'],$settings['smtp']['encryption'],$settings['smtp']['username'],$settings['smtp']['password']);
if(isset($settings['pdf'])){ $PDF = new apiPDF($settings['pdf']); }
else{ $PDF = new apiPDF(); }

// Functions
function deleteDir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir")
           rrmdir($dir."/".$object);
        else unlink   ($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
 }

if($IMAP->Box == null){
  echo "Errors :\n";var_dump($IMAP->Errors);
  echo "Alerts :\n";var_dump($IMAP->Alerts);
} else {
  $store = dirname(__FILE__) . '/tmp/';
  if(!is_dir($store)){mkdir($store);}
  $store .= 'imap/';
  if(!is_dir($store)){mkdir($store);}
  $store .= $settings['imap']['username'].'/';
  if(!is_dir($store)){mkdir($store);}
  echo "Opening Mailbox ".$settings['imap']['username']."\n";
  if($IMAP->NewMSG != null){
    echo "Reading Mailbox ".$settings['imap']['username']."\n";
    foreach($IMAP->NewMSG as $msg){
      echo "Looking at message[".$msg->ID."]".$msg->Subject->PLAIN."\n";
      $files = [];
      if(!is_dir($store.$msg->UID.'/')){mkdir($store.$msg->UID.'/');}
      // Saving Attachments
      foreach($msg->Attachments->Files as $file){
        if($file['is_attachment']){
          $filename = time().".dat";
          if(isset($file['filename'])){ $filename = $file['filename']; }
          if(isset($file['name'])){ $filename = $file['name']; }
          echo "Saving in ".$store.$msg->UID.'/'.$filename."\n";
          $fp = fopen($store.$msg->UID.'/' . $filename, "w+");
          fwrite($fp, $file['attachment']);
          fclose($fp);
          array_push($files,$store.$msg->UID.'/' . $filename);
        }
      }
      // Merge Files
      if(!empty($files)){
        $mergedfile = $PDF->combine($files);
        if(count($PDF->errors)){ print_r($PDF->errors); }
        echo "Merging into ".$mergedfile."\n";
        $message = "File(s) merged successfully!";
        // Send Mail to Contact
        if(isset($settings['destination'])){ $msg->From = $settings['destination']; }
        $SMTP->send($msg->From, $message, [
          'from' => $settings['smtp']['username'],
          'subject' => $msg->Subject->PLAIN,
          'attachments' => [$mergedfile],
        ]);
      } else {
        echo "No File Found!\n";
        $message = "No File Found!";
        // Send Mail to Contact
        if(isset($settings['destination'])){ $msg->From = $settings['destination']; }
        $SMTP->send($msg->From, $message, [
          'from' => $settings['smtp']['username'],
          'subject' => $msg->Subject->PLAIN,
        ]);
        echo "Sending email to ".$msg->From."\n";
      }
      // Set Mail Status to Read
      echo "Setting email ".$msg->UID." as read\n";
      $IMAP->read($msg->UID);
      // Delete Mail
      echo "Deleting email ".$msg->UID."\n";
      $IMAP->delete($msg->UID);
      deleteDir($store.$msg->UID.'/');
    }
  }
}
