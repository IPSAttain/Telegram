<?
    // Klassendefinition
    class TelegramMessenger extends IPSModule {
 
        public function __construct($InstanceID) {
            // Diese Zeile nicht l�schen
            parent::__construct($InstanceID);
        }
 
        // �berschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            parent::Create();                       
			$this->RegisterPropertyString("BotID", "123456789:JHJ56HJJHJ78778JKLKJKLJ8798JHJahjhw");
			$this->RegisterPropertyString("Recipients", "123456789,987654321");   
			$this->RegisterPropertyBoolean("FetchIncoming", true);
			$this->RegisterPropertyBoolean("ProcessIncoming", false);
			$this->RegisterPropertyInteger ("ProcessIncomingSkript", 0);
			$this->RegisterPropertyString("DeniedUser", "Access denied!");
			$this->RegisterPropertyBoolean("HTML", false);
			$this->RegisterTimer("GetUpdates", 15000, 'Telegram_GetUpdates($_IPS[\'TARGET\']);');
			$this->RegisterAttributeString("Buffer", "");
		}
		
        // �berschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht l�schen
            parent::ApplyChanges();
        }
		
		public function SendTextToAll(string $text) {
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			$retVal = true;
			foreach($recips as $r) {
				$retVal &= $this->SendText($text, $r);
			}
			return $retVal;
		}
		
		public function SendText(string $text, string $userid) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$frmt = "Markdown";
			if ($this->ReadPropertyBoolean("HTML") == true) {
				$frmt = "HTML";
			}
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$content = array('chat_id' => $userid, 'text' => $text, 'parse_mode' => $frmt);
			return $telegram->sendMessage($content);
		}

		public function SendTextKey(string $text, string $userid, string $markup) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$frmt = "Markdown";
			if ($this->ReadPropertyBoolean("HTML") == true) {
					$frmt = "HTML";
			}
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$content = array('chat_id' => $userid, 'text' => $text, 'parse_mode' => $frmt,'reply_markup' => $markup);
			return $telegram->sendMessage($content);
		}

		public function SendImage(string $text, string $image_path, string $userid) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$img_info = getimagesize($image_path);
			$mime = $img_info['mime'];
			if ($mime == "image/jpeg" or $mime == "image/jpg") {
				$ext = ".jpg";
			} else if ($mime == "image/png") {
				$ext = ".png";
			} else if ($mime == "image/gif") {
				$ext = ".gif";
			} else {
				return false;
			}
			$img = curl_file_create($image_path, $mime , md5($image_path.time()).$ext);
			$content = array('chat_id' => $userid, 'caption' => $text, 'photo' => $img);
			return $telegram->sendPhoto($content);
		}
		
		public function SendImageToAll(string $text, string $image_path) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			$retVal = true;
			foreach($recips as $r) {
				$retVal &= $this->SendImage($text, $image_path, $r);
			}
			return $retVal;
		}
		
		public function SendDocument(string $text, string $document_path, string $mimetype, string $userid) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$ext = pathinfo($document_path);
			$doc = curl_file_create($document_path, $mimetype , md5($document_path.time()).".".$ext['extension']);
			$content = array('chat_id' => $userid, 'caption' => $text, 'document' => $doc);
			return $telegram->sendDocument($content);
		}
		
		public function SendDocumentToAll(string $text, string $document_path, string $mimetype) {
			include_once(__DIR__ . "/../libs/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			$retVal = true;
			foreach($recips as $r) {
				$retVal &= $this->SendDocument($text, $document_path, $mimetype, $r);
			}
			return $retVal;
		}

		private function SetValueHTMLListe($chat_id, $text, $first_name, $last_name){
			$amount = 10;
			$header ='<body bgcolor="#a6caf0"><style type="text/css">table.liste { width: 100%; border-collapse: true;} table.liste td { border: 1px solid #444455; } table.liste th { border: 1px solid #444455; }</style>';
			$header.='<table border = "0" frame="box" class="liste">';
			$header.='<tr>';
			$header.='<th>' . $this->Translate('Date') . '</th>';
			$header.='<th>' . $this->Translate('Time') . '</th>';
			$header.='<th>' . $this->Translate('User') . ' ID</th>';
			$header.='<th>' . $this->Translate('First Name') . '</th>';
			$header.='<th>' . $this->Translate('Last Name') . '</th>';
			$header.='<th>' . $this->Translate('Message') . '</th>';
			$header.='</tr>';
		
			$data ='<tr align="center"><td>'.date("d.m.Y").'</td>';
			$data.='<td>'.date("H:i").'</td>';
			$data.='<td>'.$chat_id.'</td>';
			$data.='<td>'.$first_name.'</td>';
			$data.='<td>'.$last_name.'</td>';
			$data.='<td>'.$text.'</td>';
		   
			$buffer = explode("</tr>",$this->ReadAttributeString("Buffer"),$amount);
			array_unshift($buffer, $data);
			$buffer = array_slice( $buffer, 0, $amount );	
			$string = implode("</tr>",$buffer);
			$this->WriteAttributeString("Buffer",$string);
			$this->RegisterVariableString("Telegram_Table", "Telegram Events","~HTMLBox",10);
			SetValueString($this->GetIDForIdent("Telegram_Table"), $header . $string . "</table></body>");
		}
		
		public function GetUpdates() {
			if ($this->ReadPropertyBoolean("FetchIncoming")) {
				include_once(__DIR__ . "/../libs/Telegram.php");
				$telegram = new Telegram($this->ReadPropertyString("BotID"));
				$req = $telegram->getUpdates();

				for ($i = 0; $i < $telegram->UpdateCount(); $i++) {
					// You NEED to call serveUpdate before accessing the values of message in Telegram Class
					$telegram->serveUpdate($i);
					$text = $telegram->Text();
					$chat_id = $telegram->ChatID();
					$date = $telegram->Date();
					$first_name = $telegram->FirstName();
					$last_name = $telegram->LastName();
					IPS_LogMessage("Telegram", "Update von " . $chat_id . " -> " . $text . " / " . $date . " / " . print_r($telegram,true));
					$this->SetValueHTMLListe($chat_id, $text, $first_name, $last_name);
					// Verarbeiten von Nachrichten (aber nur wenn aktiviert und Nachricht nicht �lter als 1 Minute);
					if ($this->ReadPropertyBoolean("ProcessIncoming") && (time() - $date) < 60) {
						// Ist der User bekannt?
						$recips = explode(",",$this->ReadPropertyString("Recipients"));
						$GuarantAccess = false;
						foreach($recips as $r) {
							if ($r == $chat_id) {
								IPS_RunScriptEx(
									$this->ReadPropertyInteger("ProcessIncomingSkript"),
									array("SENDER" => "Telegram", "INSTANCE" => $this->InstanceID, "CHAT" => $chat_id, "VALUE" => $text, "LASTNAME" => $last_name, "FIRSTNAME" => $first_name)
								);
								$GuarantAccess = true;
								break;
							}
						}
						if (!$GuarantAccess) {
							$this->SendText("Hey " . $first_name . " " . $last_name . "\r\n" . $this->ReadPropertyString("DeniedUser"), $chat_id);
						}						
					}
				}
			}
		}  
	}
?>