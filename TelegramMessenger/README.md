# Telegram Messenger Modul für IP-Symcon

Dieses Modul erlaubt das senden (Text und / oder Bilder) und empfangen (Text) von Nachrichten über den Telegram Messenger. Für die Nutzung des Moduls muss ein Telegram Bot erstellt werden. Der Bot kann dann den definierten Telegram Nutzern Nachrichten schicken oder welche von Ihnen empfangen. Wenn gewünscht können empfangene Nachrichten an ein IPS-Skript zur Verarbeitung weitergeleitet werden.

## Inhalt

1. Telegram Bot erstellen
2. Benutzer mit dem Bot verbinden
3. Nachrichten von IPS aus versenden
4. Empfangen (und ggf. verarbeiten) von Nachrichten mit IPS

## Telegram Bot erstellen

Das erstellen eines eigenen Telegram Bots ist einfach. Der einfachheit halber empfiehlt es sich den Desktop- oder Web-Client von Telegram für die Einrichtung des Bots zu nutzen. Eine schöne Anleitung findet sich bei hier: http://www.tutonaut.de/anleitung-einfuehrung-in-telegram-bots-nachrichten-und-dateien-aus-dem-terminal-senden.html

Bei der Einrichtung des Bots teilt einem Telegram den Access Token mit:

> Use this token to access the HTTP API:
> 123456789:ABCDEFG7HIJKLM8NOPRS7Tuvw89xYZ

Der Token muss in der Konfiguration der Instanz unter "BotID" eingetragen werden.

## Benutzer mit dem Bot verbinden

Die Nutzer müssen sich zuerst in ihrem Telegram-Client mit dem Bot verbinden und ihm dann irgendeine Nachricht senden. In der Konfiguration der Instanz muss zudem der Haken bei "Eingehende Nachrichten abrufen" gesetzt sein.

Im IP-Symcon Meldungen Fenster wird dann ein Eintrag in der Form "Update von 123456789 -> BlahBlubb...." auftauchen. Die User-ID ist also hier im Beispiel "123456789". Diese Zahl ist in der Instanz in das Feld "Recipients" einzutragen. Mehrere User-IDs sind durch Komma zu trennen, ohne Leerzeichen. Beispiel: "123456789,987654321,567891234"

## Nachrichten von IPS aus versenden

Für den Versand von Nachrichten an die definierten Empfänger definiert das Modul vier Befehle:

- Telegram_SendText($InstanzID, $text, $UserID)
- Telegram_SendTextToAll($InstanzID, $text)
- Telegram_SendImage($InstanzID, $text, $Path_To_ImageFile, $UserID)
- Telegram_SendImageToAll($InstanzID, $text, $Path_To_ImageFile)

Wichtig beim Versand von Bildern ist, dass diese tatsächlich als Datei im Dateisystem liegen, da die Befehle eine Pfadangabe erwarten. Aktuell werden die Formate JPG, GIF und PNG unterstützt.

## Empfangen (und ggf. verarbeiten) von Nachrichten mit IPS

Sofern man keine Nachrichten der Clients an IPS empfangen möchte kann der Haken bei "Eingehende Nachrichten abrufen" entfernt werden (nachdem man alle gewünschten User-IDs herausgefunden hat).

Möchte man hingegen Nachrichten empfangen, so muss der Haken gesetzt werden. Eingehende Nachrichten werden dann im Meldungen-Fenster von IPS geloggt. Interessant wird es natürlich erst, wenn man mit den Nachrichten auch etwas anstellen will. Dazu muss der Haken bei "Eingehende Nachrichten verarbeiten" gesetzt sein und ein Skript bei "Skript für eingehende Nachrichten" ausgewählt werden.

Dieses Skript muss eine PHP-Funktion mit folgender Signatur enthalten:

> function process_incoming($instance, $senderid, $text)

Die Variable $instance enthält die ID der Instanz die die Nachricht empfangen hat. $senderid ist der UserID des Absenders. $text enthält den empfangenen Text. Als Beispiel könnte das Skript wie folgt aussehen:

```php
<?php
function process_incoming($instance, $senderid, $text) {
	$return = "";

	switch(strtolower($text)) {

		case "temp": // Aussentemperatur
		   $return = "Die Aussentemperatur beträgt " . GetValueFormatted(54500 /*[Garten\Wetterstation\Aussentemperatur (komb.)]*/);
			break;
			
		default:
		   $return = "Der Befehl '".$text."' ist unbekannt.";
		   break;
	}

	if ($return != "") {
		Telegram_SendText($instance, $return, $senderid);
	}
}
?>
```

Hier kann man seiner Fantasie dann freien Lauf lassen, ich würde allerdings nicht unbedingt sicherheitsrelevante Funktionen auf diesem Wege von Aussen zugänglich machen. Das Modul leitet zwar nur Nachrichten von den definierten Empfängern an das Skript weiter, aber auch die Absender-Konten könnten ja gehackt sein...