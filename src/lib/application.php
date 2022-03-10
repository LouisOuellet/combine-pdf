<?php

// Import Librairies
require_once dirname(__FILE__,3) . '/src/lib/api.php';

class Application extends API{

  public function __construct(){
    parent::__construct();
  }

	public function start(){
    // Check Connection Status
    if($this->Auth->IMAP->isConnected()){
      // Init Messages
      $messages = [];
      // Retrieve emails
      $this->log($this->Fields['Opening Mailbox '].$this->Settings['imap']['username']);
      $messages = $this->Auth->IMAP->get();
      $this->log($this->Fields['Reading Mailbox '].$this->Settings['imap']['username']);
      foreach($messages->messages as $msg){
        $uid=str_replace(['>','<'],['',''],$msg->UID);
        $directory='tmp/imap/'.$this->Settings['imap']['username'].'/'.$uid;
        $this->mkdir($directory);
        $this->log($this->Fields['Looking at message ']."[".$uid."]".$msg->Subject->Full);
        $eml = [
          "uid" => $uid,
          "sender" => $msg->From,
          "subject" => $msg->Subject->Full,
          "body" => $msg->Body->Content,
          "date" => date('Y-m-d H:i:s',strtotime($msg->Date)),
        ];
        if(isset($eml['attachments'])){ unset($eml['attachments']); }
        $eml['attachments'] = [];
        // Saving Attachments
        $this->log($this->Fields['Saving Attachments']);
        foreach($msg->Attachments->Files as $file){
          if(isset($file["name"])){
            $filename = explode('.',$file["name"]);
            $type = end($filename);
            $name = $filename[0];
          } else { $file["name"] = null; }
          if(isset($file["filename"])){
            $filename = explode('.',$file["filename"]);
            $type = end($filename);
            $name = $filename[0];
          } else { $file["filename"] = null; }
          if(!isset($this->Settings['attachments']['pattern']) || preg_match($this->Settings['attachments']['pattern'],$name.'.'.$type)){
            $this->log($this->Fields['Saving '].$directory.'/'.$name.'.'.$type);
            $save = fopen($directory.'/'.$name.'.'.$type, "w+");
            fwrite($save, $file['attachment']);
            fclose($save);
            array_push($eml['attachments'],$directory.'/'.$name.'.'.$type);
          }
        }
        // Merging Attachments
        if(isset($this->Settings['attachments']['merge']) && $this->Settings['attachments']['merge']){
          $this->log($this->Fields['Merging Attachments']);
          $mergedfile = $this->PDF->combine($files,$directory.'/');
          $this->log($this->Fields['Merged File: '.$mergedfile]);
          $eml['attachments'] = [$mergedfile];
        }
        // Check Connection Status
        if($this->Auth->SMTP->isConnected()){
          // Send Mail to Contact
          if(isset($this->Settings['destination'])){ $msg->From = $this->Settings['destination']; }
          if(empty($eml['attachments'])){ $body = $this->Fields["No File Found!"]; } else { $body = $this->Fields["File(s) merged successfully!"]; }
          $options =[
            'from' => $eml['sender'],
            'subject' => $eml['subject'],
            'attachments' => $eml['attachments'],
          ];
          $this->log($this->Fields['Sending email to '].$msg->From);
          if($this->Auth->SMTP->send($msg->From, $body, $options)){
            $this->log($this->Fields['Email sent to '].$msg->From);
            $this->log($this->Fields['Deleting email '].$uid);
            if($this->Auth->IMAP->delete($uid)){
              $this->log($this->Fields['Email Deleted '].$uid);
            } else {
              $this->log($this->Fields['Unable to delete the email '].$uid);
              $this->error(["uid" => $uid]);
            }
          } else {
            $this->log($this->Fields['Unable to send email to '].$msg->From);
            $this->error([
              "recipient" => $msg->From,
              "from" => $eml['sender'],
              "subject" => $eml['subject'],
              "attachments" => $eml['attachments'],
              "body" => $body
            ]);
          }
        } else {
          $this->log($this->Fields['Unable to connect to SMTP server']);
          $this->error(["status" => $this->Auth->SMTP->isConnected(),"settings" => $this->Settings['smtp']]);
        }
      }
    } else {
      $this->log($this->Fields['Unable to connect to IMAP server']);
      $this->error(["status" => $this->Auth->IMAP->isConnected(),"settings" => $this->Settings['imap']]);
    }
	}
}
