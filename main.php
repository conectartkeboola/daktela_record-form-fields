<?php
// TRANSFORMACE DAT Z LIBOVOLNÉHO POČTU INSTANCÍ DAKTELA

require_once "vendor/autoload.php";

$ds      = DIRECTORY_SEPARATOR;
$dataDir = getenv("KBC_DATADIR");
$homeDir = __DIR__;

require_once $homeDir.$ds."kbcparams.php";                                      // načtení parametrů importovaných z konfiguračního JSON řetězce v definici PHP aplikace v KBC
require_once $homeDir.$ds."variables.php";                                      // načtení definic proměnných a konstant
require_once $homeDir.$ds."functions.php";                                      // načtení definic funkcí

logInfo("PROMĚNNÉ A FUNKCE ZAVEDENY");                                          // volitelný diagnostický výstup do logu
logInfo("ZPRACOVÁVANÝ DATUMOVÝ ROZSAH:  ".$processedDates["start"]." ÷ ".$processedDates["end"]);
// ==============================================================================================================================================================================================
// načtení vstupních souborů
foreach ($instances as $instId => $inst) {
    foreach ($tabsList_InOut_InOnly[$inst["ver"]] as $file) {
        ${"in_".$file."_".$instId} = new Keboola\Csv\CsvFile($dataDir."in".$ds."tables".$ds."in_".$file."_".$instId.".csv");
    }
}
logInfo("VSTUPNÍ SOUBORY NAČTENY");     // volitelný diagnostický výstup do logu
// ==============================================================================================================================================================================================
logInfo("ZAHÁJENO NAČÍTÁNÍ DEFINICE DATOVÉHO MODELU");                          // volitelný diagnostický výstup do logu
$jsonList = $tiList = $fkList = [];
/* struktura polí:  $jsonList = [$instId => [$tab => [$colName => <0~jen rozparsovat / 1~rozparsovat a pokračovat ve zpracování hodnoty>]]] ... pole sloupců obsahujících JSON                    
                    $tiList   = [$instId => [$tab => <ID_časového_atributu>]]                 ... pole indexů sloupců pro časovou restrikci záznamů
                    $fkList   = [$instId => [$tab => [$colName => <název_nadřazené_tabulky>]]]... pole názvů nadřazených tabulek pro každý sloupec, který je FK
*/
foreach ($instances as $instId => $inst) {                                      // iterace instancí
    logInfo("NAČÍTÁNÍ DEFINICE INSTANCE ".$instId);                             // volitelný diagnostický výstup do logu        
    
    foreach ($tabs_InOut_InOnly[$inst["ver"]] as $tab => $cols) {               // iterace tabulek; $tab - název tabulky, $cols - pole s parametry sloupců
        logInfo("NAČÍTÁNÍ DEFINICE TABULKY ".$instId."_".$tab);                 // volitelný diagnostický výstup do logu   
        
        $colId   = 0;                                                           // počitadlo sloupců (číslováno od 0)
        $tiColId = NULL;                                                        // ID sloupce, který je v dané tabulce atributem pro datumovou restrikci (číslováno od 0)
        foreach ($cols as $colName => $colAttrs) {                              // iterace sloupců
            if (array_key_exists("json", $colAttrs)) {                          // nalezen sloupec, který je JSON
                $jsonList[$instId][$tab][$colName] = $colAttrs["json"];         // uložení příznaku způsobu zpracování JSONu (0/1) do pole $jsonList                                         //
                logInfo("TABULKA ".$instId."_".$tab." - NALEZEN JSON ".$colName."; DALŠÍ ZPRACOVÁNÍ PO PARSOVÁNÍ = ".$colAttrs["json"]);
            }
            if (array_key_exists("ti", $colAttrs)) {                            // nalezen sloupec, který je atributem pro časovou restrikci záznamů
                $tiColId = $colId;
                $tiList[$instId][$tab] = $colId;                                // uložení indexu sloupce (0, 1, 2, ...) do pole $tiList                                         //
                logInfo("TABULKA ".$instId."_".$tab." - ATRIBUT PRO ČASOVOU RESTRIKCI ZÁZNAMŮ: SLOUPEC #".$colId." (".$colName.")");
            }
            if (array_key_exists("fk", $colAttrs)) {                            // nalezen sloupec, který je PK
                $fkList[$instId][$tab][$colName] = $colAttrs["fk"];             // uložení názvu nadřezené tabulky do pole $fkList
                logInfo("TABULKA ".$instId."_".$tab." - NALEZEN FK DO TABULKY ".$colAttrs["fk"]." (SLOUPEC ".$colName.")");
            }
            $colId ++;                                                          // přechod na další sloupec            
        }        
    }
}
logInfo("DOKONČENO NAČTENÍ DEFINICE DATOVÉHO MODELU");
$expectedDigs = $idFormat["instId"] + $idFormat["idTab"];
logInfo("PŘEDPOKLÁDANÁ DÉLKA INDEXŮ VE VÝSTUPNÍCH TABULKÁCH JE ".$expectedDigs." ČÍSLIC");  // volitelný diagnostický výstup do logu
// ==============================================================================================================================================================================================

logInfo("ZAHÁJENO ZPRACOVÁNÍ DAT");     // volitelný diagnostický výstup do logu
$idFormatIdEnoughDigits = false;        // příznak potvrzující, že počet číslic určený proměnnou $idFormat["idTab"] dostačoval k indexaci záznamů u všech tabulek (vč. out-only položek)
$tabItems = [];                         // pole počitadel záznamů v jednotlivých tabulkách (ke kontrole nepřetečení počtu číslic určeném proměnnou $idFormat["idTab"])

while (!$idFormatIdEnoughDigits) {      // dokud není potvrzeno, že počet číslic určený proměnnou $idFormat["idTab"] dostačoval k indexaci záznamů u všech tabulek (vč. out-only položek)
    
    foreach ($tabs_InOut_OutOnly[6] as $tab => $cols) {        
        $tabItems[$tab] = 0;                                // úvodní nastavení nulových hodnot počitadel počtu záznamů všech OUT tabulek
        // vytvoření výstupních souborů    
        ${"out_".$tab} = new \Keboola\Csv\CsvFile($dataDir."out".$ds."tables".$ds."out_".$tab.".csv");
        // zápis hlaviček do výstupních souborů
        $colsOut = array_key_exists($tab, $colsInOnly) ? array_diff(array_keys($cols), $colsInOnly[$tab]) : array_keys($cols);
        $colPrf  = strtolower($tab)."_";                    // prefix názvů sloupců ve výstupní tabulce (např. "loginSessions" → "loginsessions_")
        $colsOut = preg_filter("/^/", $colPrf, $colsOut);   // prefixace názvů sloupců ve výstupních tabulkách názvy tabulek kvůli rozlišení v GD (např. "title" → "groups_title")
        ${"out_".$tab} -> writeRow($colsOut); 
    }
    logInfo("VÝSTUPNÍ SOUBORY VYTVOŘENY, ZÁHLAVÍ VLOŽENA"); // volitelný diagnostický výstup do logu

    // vytvoření záznamů s umělým ID v tabulkách definovaných proměnnou $tabsFakeRow (kvůli JOINu tabulek v GoodData) [volitelné]
    if ($emptyToNA) {
        foreach ($tabsFakeRow as $ftab) {
            $frow = [];
            foreach ($tabs_InOut_OutOnly[6] + $tabs_InOut_OutOnly[5] as $tabName => $col) {
                foreach ($col as $colName => $colAttrs) {
                    if ($tabName == $ftab) {
                        if (array_key_exists($tabName, $colsInOnly)) {
                            if (in_array($colName, $colsInOnly[$tabName])) {continue;}                  // sloupec je in-only → přeskočit 
                        }
                        if (array_key_exists("fk", $colAttrs) || array_key_exists("pk", $colAttrs)) {   // do sloupců typu FK nebo PK ...
                            $frow[] = $fakeId;                                                          // ... se vloží $fakeId, ...
                        } elseif (array_key_exists("tt", $colAttrs)) {
                            $frow[] = $fakeTitle;                                                       // ... do sloupců obsahujících title se vloží $fakeTitle, ...
                        } else {
                            $frow[] = "";                                                               // ... do ostatních sloupců se vloží ptázdná hodnota
                        }
                    }
                }
            }
            ${"out_".$ftab} -> writeRow($frow);
            logInfo("VLOŽEN UMĚLÝ ZÁZNAM S ID \"".$fakeId."\" A NÁZVEM \"".$fakeTitle."\" DO VÝSTUPNÍ TABULKY ".$ftab); // volitelný diag. výstup do logu
        }
    }

    // ==========================================================================================================================================================================================
    // zápis záznamů do výstupních souborů
    
    setFieldsShift();                                       // výpočet konstant posunu indexování formulářových polí
    
    // iterace instancí -------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    foreach ($instances as $instId => $inst) {              // procházení tabulek jednotlivých instancí Daktela
        initFields();                                       // nastavení výchozích hodnot proměnných popisujících formulářová pole         
        logInfo("ZAHÁJENO ZPRACOVÁNÍ INSTANCE ".$instId);   // volitelný diagnostický výstup do logu
        
        // iterace tabulek dané instance --------------------------------------------------------------------------------------------------------------------------------------------------------
        foreach ($tabs_InOut_InOnly[$inst["ver"]] as $tab => $cols) {               // iterace tabulek dané instance
            $dateRestrictColId = dateRestrictColId($instId, $tab);                  // ID sloupce, který je v dané tabulce atributem pro datumovou restrikci (0,1,...), pokud v tabulce existuje (jinak NULL)
            if (!$inst["instOn"] && !is_null($dateRestrictColId)) {                 // jde o dynamickou tabulku v instanci vypnuté v konfiguračním JSONu
                logInfo("ZPRACOVÁNÍ TABULKY ".$instId."_".$tab." VYPNUTO V JSON");  // volitelný diagnostický výstup do logu 
                continue;
            }
            logInfo("ZAHÁJENO ZPRACOVÁNÍ TABULKY ".$instId."_".$tab);               // volitelný diagnostický výstup do logu           
            // iterace řádků dané tabulky -------------------------------------------------------------------------------------------------------------------------------------------------------
            foreach (${"in_".$tab."_".$instId} as $rowNum => $row) {                // načítání řádků vstupních tabulek [= iterace řádků]
                if ($rowNum == 0) {continue;}                                       // vynechání hlavičky tabulky
                // ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                // při inkrementáním módu zpracování pro všechny nestatické tabulky (tj. nejen "calls" a "activities") přeskočení záznamů ležících mimo zpracovávaný datumový rozsah 
                if (!$incrCallsOnly && !is_null($dateRestrictColId)) {              // inkrementálně zpracováváme všechny nestatické tabulky && sloupec pro datumovou restrikci záznamů v tabulce existuje
                    if (!dateRngCheck($row[$dateRestrictColId])) {continue;}        // hodnota atributu pro datumovou restrikci leží mimo zpracovávaný datumový rozsah → přechod na další řádek                  
                } 
                // ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                $tabItems[$tab]++;                                                  // inkrement počitadla záznamů v tabulce
                if (checkIdLengthOverflow($tabItems[$tab])) {                       // došlo k přetečení délky ID určené proměnnou $idFormat["idTab"]
                    continue 4;                                                     // zpět na začátek cyklu 'while' (začít plnit OUT tabulky znovu, s delšími ID)
                }
          
                $fieldRow = [];                                                     // záznam do pole formulářových polí     
                unset($idFieldSrcRec);                                              // reset indexu zdrojového záznamu do out-only tabulky hodnot formulářových polí + ...
                                                                                    // ... + indexu zdrojové aktivity do out-only tabulky 'actItemVals' + ID front, uživatelů a typu aktivity                               
                $colId = 0;                                                         // index sloupce (v každém řádku číslovány sloupce 0,1,2,...) 
       
                foreach ($cols as $colName => $colAttrs) {                          // konstrukce řádku výstupní tabulky (vložení hodnot řádku) [= iterace sloupců]                    
                    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    switch ($colAttrs["instPrf"]) {                                 // prefixace hodnoty číslem instance (je-li požadována)
                        case 0: $hodnota = $row[$colId]; break;                     // hodnota bez prefixu instance
                        case 1: $hodnota = setIdLength($instId, $row[$colId]);      // hodnota s prefixem instance
                    }
                    // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------
                    $afterJsonProc = jsonProcessing($instId,$tab,$colName,$hodnota);// jsonProcessing - test, zda je ve sloupci JSON; když ano, rozparsuje se
                    if (!$afterJsonProc) {$colId++; continue;}                      // přechod na další sloupec
                    
                    $colParentTab = colParentTab($instId, $tab, $colName);          // test, zda je daný sloupec FK; když ano, aplikuje se na hodnotu fce emptyToNA (u FK vrátí název nadřazené tabulky, u ne-FK NULL)
                    $hodnota = is_null($colParentTab) ? $hodnota : emptyToNA($hodnota); // emptyToNA - prázdné hodnoty nahradí $fakeId kvůli integritní správnosti
                    
                    switch ([$tab, $colName]) {
                        // TABULKY V5+6
                        case ["fields", "idfield"]: $hodnota_shift = (int)$hodnota + $formFieldsIdShift;
                                                    $fieldRow["idfield"] = $hodnota_shift;      // hodnota záznamu do pole formulářových polí
                                                    break;
                        case ["fields", "title"]:   $fieldRow["title"] = $hodnota;              // hodnota záznamu do pole formulářových polí
                                                    break;
                        case ["fields", "name"]:    $fieldRow["name"] = $hodnota;               // název klíče záznamu do pole formulářových polí
                                                    break;                                      // sloupec "name" se nepropisuje do výstupní tabulky "fields"                
                        case ["records","idrecord"]:$idFieldSrcRec = $hodnota;                  // uložení hodnoty 'idrecord' pro následné použití ve 'fieldValues'
                                                    break;                                         
                        // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------                                          
                        // TABULKY V6 ONLY
                        case ["crmRecords", "idcrmrecord"]:$idFieldSrcRec = $hodnota;           // uložení hodnoty 'idcrmrecord' pro následné použití v 'crmFieldVals'
                                                    break;
                        case ["crmFields", "idcrmfield"]:
                                                    $hodnota_shift = (int)$hodnota + $formCrmFieldsIdShift;
                                                    $fieldRow["idfield"] = $hodnota_shift;      // hodnota záznamu do pole formulářových polí
                                                    break;
                        case ["crmFields", "title"]:$fieldRow["title"] = $hodnota;              // hodnota záznamu do pole formulářových polí
                                                    break;
                        case ["crmFields", "name"]: $fieldRow["name"] = $hodnota;               // název klíče záznamu do pole formulářových polí
                                                    break;                                      // sloupec "name" se nepropisuje do výstupní tabulky "fields"                                                                 
                        // ----------------------------------------------------------------------------------------------------------------------------------------------------------------------                                                  
                    }
                    $colId++;                                                       // přechod na další sloupec (buňku) v rámci řádku                
                }   // --------------------------------------------------------------------------------------------------------------------------------------------------------------------------              
                // operace po zpracování dat v celém řádku

                // přidání řádku do pole formulářových polí $fields (struktura pole je <idfield> => ["name" => <hodnota>, "title" => <hodnota>] )
                if (!empty($fieldRow["name"]) && !empty($fieldRow["idfield"]) && !empty($fieldRow["title"])) {  // je-li známý název, title i hodnota záznamu do pole form. polí...          
                        /*if ($instId == "3" && ($tab == "crmFields" || $tab == "fields")) {
                        echo "do pole 'fields' přidán záznam (idfield ".$fieldRow["idfield"].", name ".$fieldRow["name"].", title ".$fieldRow["title"].")\n";
                        } */
                    $fields[$fieldRow["idfield"]]["name"]  = $fieldRow["name"];     // ... provede se přidání prvku <idfield>["name"] => <hodnota> ...
                    $fields[$fieldRow["idfield"]]["title"] = $fieldRow["title"];    // ... a prvku <idfield>["title"] => <hodnota>
                } 
            }   // ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
            // operace po zpracování dat v celé tabulce
            logInfo("DOKONČENO ZPRACOVÁNÍ TABULKY ".$instId."_".$tab);              // volitelný diagnostický výstup do logu
        }
        logInfo("DOKONČENO ZPRACOVÁNÍ INSTANCE ".$instId);                      // volitelný diagnostický výstup do logu
        // operace po zpracování dat ve všech tabulkách jedné instance
                //echo "pole 'fields' instance ".$instId.":\n"; print_r($fields); echo "\n";        
    }
    // operace po zpracování dat ve všech tabulkách všech instancí
    
    $idFormatIdEnoughDigits = true;         // potvrzení, že počet číslic určený proměnnou $idFormat["idTab"] dostačoval k indexaci záznamů u všech tabulek (vč. out-only položek)
}
// ==============================================================================================================================================================================================
logInfo("TRANSFORMACE DOKONČENA");          // volitelný diagnostický výstup do logu