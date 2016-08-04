<?
    // Klassendefinition
    class TelegramMessenger extends IPSModule {
 
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
			
			$this->RegisterTimer("GetUpdates", 15000, 'Telegram_GetUpdates($_IPS[\'TARGET\']);');
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            parent::Create();                       
			$this->RegisterPropertyString("BotID", "123456789:JHJ56HJJHJ78778JKLKJKLJ8798JHJahjhw");
			$this->RegisterPropertyString("Recipients", "123456789,987654321");   
			$this->RegisterPropertyBoolean("FetchIncoming", false);
			$this->RegisterPropertyBoolean("ProcessIncoming", false);
			//
			// Skript für die Verarbeitung eingehender Nachrichten erstellen.
			// Dies kann vom Nutzer dann nach seinen Wünschen bearbeitet werden.
			//
			$this->RegisterScript("PROCESS_INCOMING", "Process incoming messages",'<?php\nfunction process_incoming($instance, $senderid, $text) {\n}\n?>');
		}
		
		
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();

        }
 
        /**
        * Die folgenden Funktionen stehen automatisch zur Verfügung, wenn das Modul über die "Module Control" eingefügt wurden.
        * Die Funktionen werden, mit dem selbst eingerichteten Prefix, in PHP und JSON-RPC wiefolgt zur Verfügung gestellt:
        *
        * ABC_MeineErsteEigeneFunktion($id);
        *
        */
		
		public function SendTextToAll($text) {
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			foreach($recips as $r) {
				$this->SendText($text, $r);
			}			
		}
		
		public function SendText($text, $userid) {
			include_once(__DIR__ . "/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$content = array('chat_id' => $userid, 'text' => $text);
			$telegram->sendMessage($content);
		}
		
		public function SendPhoto($text, $jpeg_path) {
			include_once(__DIR__ . "/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			$img = curl_file_create($jpeg_path, 'image/jpg', md5($jpeg_path));
			foreach($recips as $r) {
				$content = array('chat_id' => $r, 'caption' => $text, 'photo' => $img);
				$telegram->sendPhoto($content);
			}
		}
		
		public function GetUpdates() {
			if ($this->ReadPropertyString("FetchIncoming") == "True") {
				include_once(__DIR__ . "/Telegram.php");
				$telegram = new Telegram($this->ReadPropertyString("BotID"));
				$req = $telegram->getUpdates();

				for ($i = 0; $i < $telegram->UpdateCount(); $i++) {
					// You NEED to call serveUpdate before accessing the values of message in Telegram Class
					$telegram->serveUpdate($i);
					$text = $telegram->Text();
					$chat_id = $telegram->ChatID();
					$date = $telegram->Date();
					IPS_LogMessage("Telegram", "Update von " . $chat_id . " -> " . $text . " / " . $date . " / " . print_r($telegram,true));
					// Verarbeiten von Nachrichten (aber nur wenn aktiviert und Nachricht nicht älter als 1 Minute);
					if ($this->ReadPropertyString("ProcessIncoming") == "True" && (time() - $date) < 60) {
						// Ist der User bekannt?
						$recips = explode(",",$this->ReadPropertyString("Recipients"));
						foreach($recips as $r) {
							if ($r == $chat_id) {
								include_once(IPS_GetKernelDirEx()."scripts/".IPS_GetScriptFile($this->GetIDForIdent("PROCESS_INCOMING")));
								process_incoming($this->InstanceID, $chat_id, $text);
								break;
							}
						}						
					}
				}
			}
		}
    }
?>