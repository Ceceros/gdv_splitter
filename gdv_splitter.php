<?php

//Versicherungsscheinnummer. Wichtig um zu schauen ob sich mehrere Zeilen Teil der selben Versicherung sind
$vsnummer;
//Arrays, welche jeweils die Teilsätze 1,2 und 3 speichern
$array1;
$array2;
$array3;

//Erstelle einen output Ordner zum Speichern der json wenn keiner vorhanden ist
if(!is_dir("output")) mkdir("output");

$input = fopen($argv[1], "r") or die("Datei konnte nicht geöffnet werden.");
//Erstellt neue Json mit dem selben Namen (plus Timestamp) wie die gdv-Datei
$output = fopen("output/" . pathinfo($argv[1], PATHINFO_FILENAME) . date('Y-m-d-h-i-s') . ".json", "w") or die("Konnte keinen Json-File erstellen");

while(!feof($input)) {
  //Die Zeile der Textdatei die gerade gelesen wird  
  $line= fgets($input);
  //Der Code soll momentan nur Aufgaben mit der Satzart 0100 bearbeiten, alle anderen Zeilen werden übersprungen
  $satzart=substr($line,0,4);
  if($satzart !== "0100") continue;
  //Überprüfe ob sich die vsnummer geändert hat (neue Versicherung), speichere dann die Daten der vorherigen Versicherung im json
  if (isset($vsnummer) && ($vsnummer !== substr($line,13,17))) writeToJson($vsnummer, $array1, $array2, $array3, $output);
  //Wenn vsnummer noch unset ist, haben wir einen neuen Datensatz und müssen eine neue vsnummer nutzen
  if (!isset($vsnummer)) $vsnummer = substr($line,13,17);
  $teilsatznummer= substr($line,255,1);
  switch ($teilsatznummer) {
    case "1":
        $array1= array(1 => array(
            "satzart" => trim($satzart),
            "teilsatznummer" => $teilsatznummer,
            "vu-nummer"=> trim(substr($line,4,5)),
            "name 1"=> trim(substr($line,43,30)),
            "name 3"=> trim(substr($line,103,30)),
            "strasse"=> trim(substr($line,187,30)),
            "plz"=> trim(substr($line,156,6)),
            "ort"=> trim(substr($line,162,25)),
            "geburtsdatum"=> trim(substr($line,226,8))
        ));
        break;

    case "2":
        $array2 = array(2 => array(
            "satzart" => trim($satzart),
            "teilsatznummer" => $teilsatznummer,
            "vu-nummer"=> trim(substr($line,4,5)),
            "versicherungsnehmer"=> trim(substr($line,42,17)),
            "kontonummer"=> trim(substr($line,106,12)),
            "blz"=> trim(substr($line,118,8)),
        ));
        break;

    case "3":
        $array3 = array(3 => array(
            "satzart" => trim($satzart),
            "teilsatznummer" => $teilsatznummer,
            "vu-nummer"=> trim(substr($line,4,5)),
            "kommunikationsart"=> trim(substr($line,104,2)),
            "kommunikation"=> trim(substr($line,106,60)),
        ));
        break;
    default: continue 2;
  }
}
//Add the last batch of data
writeToJson($vsnummer, $array1, $array2, $array3, $output);
fclose($input);
fclose($output);

function writeToJson($vsnummer, $array1, $array2, $array3, $output){
    //Wenn die Arrays leer sind muss auch kein neues Object zur json hinzugefügt werden. Stattdessen wird nur die vsnummer resettet
    if (!isset($array1) && !isset($array2) && !isset($array3)) unset($GLOBALS['vsnummer']);
    else{
        $array = array(ltrim($vsnummer) => array($array1, $array2, $array3));
        fwrite($output, json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        //Variablen werden unset um sie mit den Werten der nächsten Zeile zu füllen
        unset($GLOBALS['vsnummer'],$GLOBALS['array1'],$GLOBALS['array2'],$GLOBALS['array3']);
    }
}

?>

