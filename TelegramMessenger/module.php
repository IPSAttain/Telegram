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
			$this->RegisterPropertyInteger ("ProcessIncomingSkript", 0);
		
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
			$content = array('chat_id' => $userid, 'text' => $text, 'parse_mode' => "Markdown");
			$telegram->sendMessage($content);
		}
		
		public function SendPhoto($text, $jpeg_path, $userid) {
			include_once(__DIR__ . "/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$img = curl_file_create($jpeg_path, 'image/jpg', md5($jpeg_path));
			$content = array('chat_id' => $r, 'caption' => $text, 'photo' => $img);
			$telegram->sendPhoto($content);
		}
		
		public function SendPhotoToAll($text, $jpeg_path) {
			include_once(__DIR__ . "/Telegram.php");
			$telegram = new Telegram($this->ReadPropertyString("BotID"));
			$recips = explode(",",$this->ReadPropertyString("Recipients"));
			foreach($recips as $r) {
				$this->sendPhoto($text, $jpeg_path, $r);
			}
		}
		
		public function GetUpdates() {
			if ($this->ReadPropertyBoolean("FetchIncoming")) {
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
					if ($this->ReadPropertyBoolean("ProcessIncoming") && (time() - $date) < 60) {
						// Ist der User bekannt?
						$recips = explode(",",$this->ReadPropertyString("Recipients"));
						foreach($recips as $r) {
							if ($r == $chat_id) {
								include_once(IPS_GetKernelDirEx()."scripts/".IPS_GetScriptFile($this->ReadPropertyInteger("ProcessIncomingSkript")));
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