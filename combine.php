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
$PDF = new apiPDF();

if($IMAP->Box == null){
  echo "Errors :<br>\n";var_dump($IMAP->Errors);
  echo "Alerts :<br>\n";var_dump($IMAP->Alerts);
} else {
  $store = dirname(__FILE__) . '/tmp/';
  if(!is_dir($store)){mkdir($store);}
  $store .= 'imap/';
  if(!is_dir($store)){mkdir($store);}
  $store .= $settings['imap']['username'].'/';
  if(!is_dir($store)){mkdir($store);}
  echo "Opening Mailbox ".$settings['imap']['username']."<br>\n";
  if($IMAP->NewMSG != null){
    echo "Reading Mailbox ".$settings['imap']['username']."<br>\n";
    foreach($IMAP->NewMSG as $msg){
      echo "Looking at message[".$msg->ID."]".$msg->Subject->PLAIN."<br>\n";
      $files = [];
      if(!is_dir($store.$msg->UID.'/')){mkdir($store.$msg->UID.'/');}
      // Saving Attachments
      foreach($msg->Attachments->Files as $file){
        if($file['is_attachment']){
          $filename = time().".dat";
          if(isset($file['filename'])){ $filename = $file['filename']; }
          if(isset($file['name'])){ $filename = $file['name']; }
          echo "Saving in ".$store.$msg->UID.'/'.$filename."<br>\n";
          $fp = fopen($store.$msg->UID.'/' . $filename, "w+");
          fwrite($fp, $file['attachment']);
          fclose($fp);
          array_push($files,$store.$msg->UID.'/' . $filename);
        }
      }
      // Merge Files
      if(!empty($files)){
        $mergedfile = $PDF->combine($files,$store.$msg->UID.'/');
        echo "Merging into ".$mergedfile."<br>\n";
        $message = "File(s) merged successfully!";
        // Send Mail to Contact
        if(isset($settings['destination'])){ $msg->From = $settings['destination']; }
        $SMTP->send($msg->From, $message, [
          'from' => $settings['smtp']['username'],
          'subject' => $msg->Subject->PLAIN,
          'attachments' => [$mergedfile],
        ]);
      } else {
        echo "No File Found!<br>\n";
        $message = "No File Found!";
        // Send Mail to Contact
        if(isset($settings['destination'])){ $msg->From = $settings['destination']; }
        $SMTP->send($msg->From, $message, [
          'from' => $settings['smtp']['username'],
          'subject' => $msg->Subject->PLAIN,
        ]);
      }
      echo "Sending email to ".$msg->From."<br>\n";
      // Set Mail Status to Read
      echo "Setting email ".$msg->UID." as read<br>\n";
      $IMAP->read($msg->UID);
      // Delete Mail
      echo "Deleting email ".$msg->UID."<br>\n";
      $IMAP->delete($msg->UID);
    }
  }
}
