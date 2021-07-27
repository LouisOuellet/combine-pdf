<?php

// Import Librairies

class apiIMAP{

	public $Box; // Contains the IMAP Connection
	public $Errors = null; // Contains the IMAP Connection
	public $Alerts = null; // Contains the IMAP Connection
	public $Meta; // Contains the Meta information of the IMAP Connection
	public $NewMSG; // Contains the all new messages

	public function __construct($host,$port,$encryption,$username,$password,$selfsigned = false){
		// Setup PhpImap
		error_reporting(0);
		$inbox = '{'.$host.':'.$port.'/imap/'.strtolower($encryption);
		if($selfsigned){ $inbox .= '/novalidate-cert'; }
		$inbox .= '}INBOX';
		if(!$this->Box = imap_open($inbox, $username, $password)){ $this->Box = null; $this->Errors = imap_errors(); $this->Alerts = imap_alerts(); }
		error_reporting(-1);
		if($this->Box != null){
			$this->Meta = imap_check($this->Box);
			if(imap_search($this->Box, 'UNSEEN')){
				$NewMSGs = imap_search($this->Box, 'UNSEEN');
				$this->Meta->Recent = count($NewMSGs);
				$this->NewMSG = [];
				foreach($NewMSGs as $msgid){
					$msg = imap_headerinfo($this->Box,$msgid);
					$msg->ID = $msgid;
					$msg->UID = imap_uid($this->Box,$msgid);
					$msg->Header = imap_header($this->Box,$msgid);
					$msg->From = $msg->Header->from[0]->mailbox . "@" . $msg->Header->from[0]->host;
					$sub = $msg->Subject;
					$msg->Subject = new stdClass();
					$msg->Subject->Full = $sub;
					$msg->Subject->PLAIN = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $sub),' ');
					$msg->Body = new stdClass();
					$msg->Body->Meta = imap_fetchstructure($this->Box,$msgid);
					$msg->Body->Full = imap_body($this->Box,$msgid);
					$msg->Body->PLAIN = imap_fetchbody($this->Box,$msgid, 1);
					$msg->Body->HTML = new DOMDocument();
					$msg->Body->HTML->loadHTML(imap_fetchbody($this->Box,$msgid, 2));
					$this->removeElementsByTagName('script', $msg->Body->HTML);
					$this->removeElementsByTagName('style', $msg->Body->HTML);
					$this->removeElementsByTagName('head', $msg->Body->HTML);
					$msg->Body->HTML = str_replace("<html>","",str_replace("</html>","",str_replace("<body>","",str_replace("</body>","",$msg->Body->HTML->saveHtml()))));
					$msg->Body->Unquoted = new DOMDocument();
					$msg->Body->Unquoted->loadHTML(imap_fetchbody($this->Box,$msgid, 2));
					$this->removeElementsByTagName('script', $msg->Body->Unquoted);
					$this->removeElementsByTagName('style', $msg->Body->Unquoted);
					$this->removeElementsByTagName('head', $msg->Body->Unquoted);
					$this->removeElementsByTagName('blockquote', $msg->Body->Unquoted);
					$msg->Body->Unquoted = str_replace("<html>","",str_replace("</html>","",str_replace("<body>","",str_replace("</body>","",$msg->Body->Unquoted->saveHtml()))));
					$msg->Attachments = new stdClass();
					$msg->Attachments->Files = [];
					if(is_array($msg->Body->Meta->parts)){
						$msg->Attachments->Count = count($msg->Body->Meta->parts);
						foreach($msg->Body->Meta->parts as $key => $part){
							if($part->ifdparameters){
								foreach($part->dparameters as $object){
									if(strtolower($object->attribute) == 'filename'){
										$msg->Attachments->Files[$key]['filename'] = $object->value;
										$msg->Attachments->Files[$key]['is_attachment'] = true;
									}
								}
							}
							if($part->ifparameters){
								foreach($part->parameters as $object){
									if(strtolower($object->attribute) == 'name'){
										$msg->Attachments->Files[$key]['name'] = $object->value;
										$msg->Attachments->Files[$key]['is_attachment'] = true;
									}
								}
							}
							if((isset($msg->Attachments->Files[$key]))&&($msg->Attachments->Files[$key]['is_attachment'])){
								$msg->Attachments->Files[$key]['attachment'] = imap_fetchbody($this->Box,$msgid, $key+1);
								$msg->Attachments->Files[$key]['encoding'] = $part->encoding;
	              if($part->encoding == 3){
	                $msg->Attachments->Files[$key]['attachment'] = base64_decode($msg->Attachments->Files[$key]['attachment']);
	              } elseif($part->encoding == 4){
	                $msg->Attachments->Files[$key]['attachment'] = quoted_printable_decode($msg->Attachments->Files[$key]['attachment']);
	              }
							}
						}
					}
					imap_clearflag_full($this->Box, $msgid, "\\Seen");
					array_push($this->NewMSG,$msg);
				}
			}
		}
	}

	public function read($uid){ imap_body($this->Box,$uid,FT_UID); }

	public function delete($uid){
		imap_mail_copy($this->Box,$uid,'Trash',FT_UID);
		imap_delete($this->Box,$uid,FT_UID);
		imap_expunge($this->Box);
	}

	protected function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
	    $node = $nodeList->item($nodeIdx);
	    $node->parentNode->removeChild($node);
	  }
	}
}
