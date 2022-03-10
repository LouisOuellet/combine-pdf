<?php

// Import Librairies

class PHPIMAP{

	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Username;
	protected $Password;
	protected $Connection;
	protected $this->IMAP;

	public $Status = false;
	public $Folders = [];

	public function __construct($Host = null,$Port = null,$Encryption = null,$Username = null,$Password = null,$isSelfSigned = true){

    // Increase PHP memory limit
    ini_set('memory_limit', '2G');
    ini_set('max_execution_time',0);

		// Save Configuration
		$this->Host = $Host;
		$this->Port = $Port;
		$this->Encryption = $Encryption;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->isSelfSigned = $isSelfSigned;

		// Setup Connection
		$Connection = '{'.$Host.':'.$Port.'/imap/'.strtolower($Encryption);
		if($isSelfSigned){ $Connection .= '/novalidate-cert'; }
		$Connection .= '}';
		$this->Connection = $Connection;

		// Test Connection
		error_reporting(0);
		if(!$this->IMAP = imap_open($Connection, $Username, $Password)){
			$this->Status = end(imap_errors());
		} else {
			$this->Status = true;
			$this->Connection = $Connection;
			error_reporting(-1);
			$folders = imap_list($this->IMAP, $Connection, "*");
			if(is_array($folders)){ foreach($folders as $folder){ array_push($this->Folders,str_replace($Connection,'',imap_utf7_decode($folder))); } }
			// Close IMAP Connection
			imap_close($this->IMAP);
		}
	}

	public function login($username,$password,$host,$port,$encryption = null,$isSelfSigned = true){
		// Setup Connection
		$connection = '{'.$host.':'.$port.'/imap/'.strtolower($encryption);
		if($isSelfSigned){ $connection .= '/novalidate-cert'; }
		$connection .= '}';

		// Test Connection
		if(!imap_open($connection, $username, $password)){ return false; } else { return true; }
	}

	protected function formatMsgs($ids = []){
		$messages = [];
		foreach($ids as $id){
			// Handling Meta Data
			$msg = imap_headerinfo($this->IMAP,$id);
			$msg->ID = $id;
			$msg->UID = imap_uid($this->IMAP,$id);
			$msg->Header = imap_headerinfo($this->IMAP,$id);
			$msg->Date = $msg->Header->date;
			$msg->From = $msg->Header->from[0]->mailbox . "@" . $msg->Header->from[0]->host;
			$msg->Sender = $msg->Header->sender[0]->mailbox . "@" . $msg->Header->sender[0]->host;
			$msg->Receiver = [];
			$msg->To = [];
			if(isset($msg->Header->to)){
				foreach($msg->Header->to as $to){
					if(property_exists($to, 'host') && property_exists($to, 'mailbox')){
						array_push($msg->To,$to->mailbox . "@" . $to->host);
						array_push($msg->Receiver,$to->mailbox . "@" . $to->host);
					}
				}
			} else { array_push($msg->To,$this->Username); }
			$msg->CC = [];
			if(isset($msg->Header->cc)){
				foreach($msg->Header->cc as $cc){
					if(property_exists($cc, 'host') && property_exists($cc, 'mailbox')){
						array_push($msg->CC,$cc->mailbox . "@" . $cc->host);
						array_push($msg->Receiver,$cc->mailbox . "@" . $cc->host);
					}
				}
			}
			$msg->BCC = [];
			if(isset($msg->Header->bcc)){
				foreach($msg->Header->bcc as $bcc){
					if(property_exists($bcc, 'host') && property_exists($bcc, 'mailbox')){
						array_push($msg->BCC,$bcc->mailbox . "@" . $bcc->host);
						array_push($msg->Receiver,$bcc->mailbox . "@" . $bcc->host);
					}
				}
			}
			// Handling Subject Line
			if(isset($msg->subject)){$sub = $msg->subject;}
			if(isset($msg->Subject)){$sub = $msg->Subject;}
			$sub = imap_utf8($sub);
			$msg->Subject = new stdClass();
			$msg->Subject->Full = str_replace('~','-',$sub);
			$msg->Subject->PLAIN = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $msg->Subject->Full),' ');
			$msg->Subject->Meta = [];
			$meta = $msg->Subject->PLAIN;
			$replace = ['---','--','CID:','UTF-8','(',')','<','>','{','}','[',']',';','"',"'",'_','=','~','+','!','?','@','$','%','^','&','*','\\','/','|'];
			foreach($replace as $str1){ $meta = str_replace($str1,' ',strtoupper($meta)); }
			foreach(explode(' ',$meta) as $string){
				if(mb_strlen($string)>=3 && (preg_match('~[0-9]+~', $string) || strpos($string, '-') !== false) && substr($string, 0, 1) !== '=' && substr($string, 0, 1) !== '?'){ array_push($msg->Subject->Meta,$string);}
			}
			// Handling Body
			$msg->Body = new stdClass();
			$msg->Body->Meta = imap_fetchstructure($this->IMAP,$id);
			$msg->Body->Content = $this->getBody($this->IMAP,$msg->UID);
			if($this->isHTML($msg->Body->Content)){
				$htmlBody = $this->convertHTMLSymbols($msg->Body->Content);
				$html = new DOMDocument();
				libxml_use_internal_errors(true);
				$html->loadHTML($htmlBody);
				libxml_use_internal_errors(false);
				$this->removeElementsByTagName('script', $html);
				$this->removeElementsByTagName('style', $html);
				$this->removeElementsByTagName('head', $html);
				$body = $html->getElementsByTagName('body');
				if( $body && 0<$body->length ){
					$msg->Body->Content = $html->saveHtml($body->item(0));
				} else {
					$msg->Body->Content = $html->saveHtml($html);
				}
				$msg->Body->Unquoted = $this->convertHTMLSymbols($msg->Body->Content);
				if(strpos($msg->Body->Unquoted, 'From:') !== false){
					$msg->Body->Unquoted = explode('From:',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("From:","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, 'Wrote:') !== false){
					$msg->Body->Unquoted = explode('Wrote:',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("Wrote:","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, '------ Original Message ------') !== false){
					$msg->Body->Unquoted = explode('------ Original Message ------',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("------ Original Message ------","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, '------ Forwarded Message ------') !== false){
					$msg->Body->Unquoted = explode('------ Forwarded Message ------',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("------ Forwarded Message ------","",$msg->Body->Unquoted);
				}
				$html = new DOMDocument();
				libxml_use_internal_errors(true);
				$html->loadHTML($msg->Body->Unquoted);
				libxml_use_internal_errors(false);
				$this->removeElementsByTagName('blockquote', $html);
				$body = $html->getElementsByTagName('body');
				if( $body && 0<$body->length ){
					$msg->Body->Unquoted = $html->saveHtml($body->item(0));
				} else {
					$msg->Body->Unquoted = $html->saveHtml($html);
				}
			} else {
				$msg->Body->Unquoted = "";
				foreach(explode("\n",$msg->Body->Content) as $line){
					if(substr($line, 0, 1) != '>'){ $msg->Body->Unquoted .= $line."\n"; }
				}
			}
			// Handling Attachments
			$msg->Attachments = new stdClass();
			$msg->Attachments->Files = [];
			$parts = [];
			if(isset($msg->Body->Meta->parts) && is_array($msg->Body->Meta->parts) && count($msg->Body->Meta->parts) > 0){
				$parts = $this->createPartArray($msg->Body->Meta);
				$msg->Attachments->Count = 0;
				foreach($parts as $key => $objects){
					$part = $objects['part_object'];
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
						$msg->Attachments->Count++;
						$msg->Attachments->Files[$key]['attachment'] = imap_fetchbody($this->IMAP,$id, $objects['part_number']);
						$msg->Attachments->Files[$key]['encoding'] = $part->encoding;
						if(isset($part->bytes)){$msg->Attachments->Files[$key]['bytes'] = $part->bytes;}
						if($part->encoding == 3){
							$msg->Attachments->Files[$key]['attachment'] = base64_decode($msg->Attachments->Files[$key]['attachment']);
						} elseif($part->encoding == 4){
							$msg->Attachments->Files[$key]['attachment'] = quoted_printable_decode($msg->Attachments->Files[$key]['attachment']);
						}
					}
				}
			}
			$messages[$msg->ID] = $msg;
			// Resetting Flag
			if(isset($opt["new"]) && is_bool($opt["new"]) && $opt["new"]){ imap_clearflag_full($this->IMAP,$id, "\\Seen"); }
		}
		return $messages;
	}

	public function search($criteria = "ALL", $folder = "INBOX", $opt = []){
		if(is_array($folder)){ $opt = $folder;$folder = "INBOX"; }
		if($this->isConnected()){
			// Init Return
			$return = new stdClass();
			// Connect to Folder
			if(in_array($folder, $this->Folders) && $this->IMAP = imap_open($this->Connection.$folder, $this->Username, $this->Password)){
				// Building Meta Data
				$return->Meta = imap_check($this->IMAP);
				$return->Meta->All = imap_num_msg($this->IMAP);
				$new = imap_search($this->IMAP, $criteria.' UNSEEN');
				if(is_array($new)){ $return->Meta->Recent = count($new); } else { $return->Meta->Recent = 0; }
				$ids = imap_search($this->IMAP, $criteria);
				$return->messages = [];
				if(!empty($ids)){
					$return->messages = $this->formatMsgs($ids);
				}
				// Close IMAP Connection
				imap_close($this->IMAP);
				// Return
				return $return;
			} else { return end(imap_errors()); }
		} else { return $this->Status; }
	}

	public function get($folder = "INBOX", $opt = []){
		if(is_array($folder)){ $opt = $folder;$folder = "INBOX"; }
		if($this->isConnected()){
			// Init Return
			$return = new stdClass();
			// Connect to Folder
			error_reporting(0);
			if(in_array($folder, $this->Folders) && $this->IMAP = imap_open($this->Connection.$folder, $this->Username, $this->Password)){
				error_reporting(-1);
				// Building Meta Data
				$return->Meta = imap_check($this->IMAP);
				$return->Meta->All = imap_num_msg($this->IMAP);
				$new = imap_search($this->IMAP, 'UNSEEN');
				if(is_array($new)){ $return->Meta->Recent = count($new); } else { $return->Meta->Recent = 0; }
				$ids = imap_search($this->IMAP,"ALL");
				$return->messages = [];
				if(!empty($ids)){
					$return->messages = $this->formatMsgs($ids);
				}
				// Close IMAP Connection
				imap_close($this->IMAP);
				// Return
				return $return;
			} else { return end(imap_errors()); }
		} else { return $this->Status; }
	}

	public function isConnected(){
		return is_bool($this->Status) && $this->Status ? true:false;
	}

	public function read($uid){
		// Connect IMAP
		$this->IMAP = imap_open($this->Connection, $this->Username, $this->Password);
		// Read Message
		imap_body($this->IMAP,$uid,FT_UID);
		// Close IMAP Connection
		imap_close($this->IMAP);
	}

	public function saveEml($uid){
		// Connect IMAP
		error_reporting(0);
		if($this->IMAP = imap_open($this->Connection, $this->Username, $this->Password)){
			error_reporting(-1);
			// Fetch Email
			$headers = imap_fetchheader($this->IMAP, $uid, FT_UID);
			$body = imap_body($this->IMAP, $uid, FT_UID);
			// Close IMAP Connection
			imap_close($this->IMAP);
			// Return Blob
			return $headers."\n".$body;
		} else { error_reporting(-1);return false; }
	}

	public function delete($uid){
		// Connect IMAP
		error_reporting(0);
		if($this->IMAP = imap_open($this->Connection, $this->Username, $this->Password)){
			error_reporting(-1);
			// Delete Email
			imap_mail_copy($this->IMAP,$uid,'Trash',FT_UID);
			imap_delete($this->IMAP,$uid,FT_UID);
			imap_expunge($this->IMAP);
			// Close IMAP Connection
			imap_close($this->IMAP);
			return true;
		} else { error_reporting(-1);return false; }
	}

	public function saveAttachment($file,$destination){
		// Saving Attachment
		if($file['is_attachment']){
			$filename = time().".dat";
			if(isset($file['filename'])){ $filename = $file['filename']; }
			if(isset($file['name'])){ $filename = $file['name']; }
			$save = fopen(rtrim($destination,"/") . "/" . $filename, "w+");
			fwrite($save, $file['attachment']);
			fclose($save);
			return rtrim($destination,"/") . "/" . $filename;
		} else { return false; }
	}

	protected function innerHTML(DOMNode $element){
    $innerHTML = "";
    $children  = $element->childNodes;
    foreach($children as $child){
    	$innerHTML .= $element->ownerDocument->saveHTML($child);
    }
    return $innerHTML;
	}

	protected function convertHTMLSymbols($str_in){
		$list = get_html_translation_table(HTML_ENTITIES);
		unset($list['"']);
		unset($list['<']);
		unset($list['>']);
		unset($list['&']);
		$search = array_keys($list);
		$values = array_values($list);
		return str_replace($search, $values, $str_in);
	}

	protected function isHTML($string){
	 return $string != strip_tags($string) ? true:false;
	}

	protected function getMimeType($structure){
    $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype){
      return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
	}

	protected function getBody($imap, $uid){
	    $body = $this->getPart($imap, $uid, "TEXT/HTML");
	    if($body == ""){ $body = $this->getPart($imap, $uid, "TEXT/PLAIN"); }
			$body = imap_utf8($body);
	    return $body;
	}

	protected function getPart($imap, $uid, $mimetype, $structure = false, $partNumber = false){
    if(!$structure){ $structure = imap_fetchstructure($imap, $uid, FT_UID); }
    if($structure){
      if($mimetype == $this->getMimeType($structure)){
        if(!$partNumber){ $partNumber = 1; }
        $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
        switch ($structure->encoding) {
          case 3: return imap_base64($text);
          case 4: return imap_qprint($text);
          default: return $text;
        }
      }
      // multipart
      if($structure->type == 1){
        foreach($structure->parts as $index => $subStruct){
          $prefix = "";
          if($partNumber){ $prefix = $partNumber . "."; }
          $data = $this->getPart($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
          if($data){ return $data; }
        }
      }
    }
    return false;
	}

	protected function stripLine($text, $nbr = 1) {
    for($count = 1; $count <= $nbr; $count ++){
      $text = substr($text, strpos($text, "\n") + 1);
    }
    return $text;
	}

	protected function getStringBetween($string, $start, $end){
    $string = ' '.$string;
    $ini = strpos($string, $start);
    if($ini == 0){ return ''; }
		else {
			$ini += strlen($start);
	    $len = strpos($string, $end, $ini) - $ini;
	    return substr($string, $ini, $len);
		}
	}

	protected function convertUTF8( $string ) {
    if(strlen(utf8_decode($string)) == strlen($string)){
      return iconv("ISO-8859-1", "UTF-8", $string);
    } else {
      return $string;
    }
	}

	protected function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
	    $node = $nodeList->item($nodeIdx);
	    $node->parentNode->removeChild($node);
	  }
	}

	protected function createPartArray($structure, $prefix="") {
    if (sizeof($structure->parts) > 0) {
      foreach ($structure->parts as $count => $part) { $this->addPart2Array($part, $prefix.($count+1), $part_array); }
    }else{ $part_array[] = array('part_number' => $prefix.'1', 'part_object' => $obj); }
   return $part_array;
	}

	function addPart2Array($obj, $partno, & $part_array) {
    $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
    if($obj->type == 2){
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $part){
          if(isset($part->parts) && sizeof($part->parts) > 0){
            foreach($part->parts as $count2 => $part2){ $this->addPart2Array($part2, $partno.".".($count2+1), $part_array); }
          }else{ $part_array[] = array('part_number' => $partno.'.'.($count+1), 'part_object' => $obj); }
        }
      }else{ $part_array[] = array('part_number' => $partno, 'part_object' => $obj); }
    }else{
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $p){ $this->addPart2Array($p, $partno.".".($count+1), $part_array); }
      }
    }
	}
}
