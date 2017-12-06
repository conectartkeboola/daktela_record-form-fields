<?php
// proměnné a konstanty

// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// datumový rozsah zpracování
$processedDates     =   [   "start" =>  date("Y-m-d", strtotime(-$histDays['start']." days")),  // počáteční datum zpracováváného rozsahu
                            "end"   =>  date("Y-m-d", strtotime(-$histDays['end']  ." days"))   // koncové datum zpracovávaného rozsahu
                        ];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// seznam instancí Daktela
$instances = [  1 =>  ["url" => "https://ilinky.daktela.com",             "ver" => 5, "instOn" => NULL],
                2 =>  ["url" => "https://dircom.daktela.com",             "ver" => 5, "instOn" => NULL],
                3 =>  ["url" => "https://conectart.daktela.com",          "ver" => 6, "instOn" => NULL],
                4 =>  ["url" => "https://conectart-in.daktela.com",       "ver" => 6, "instOn" => NULL],
                5 =>  ["url" => "https://conectart-offsite.daktela.com",  "ver" => 6, "instOn" => NULL]
];
//  poli $instances se nastaví hodnota klíče"instOn" na true/false podle toho, jak mají instance v konfiguračním JSONu zapnuté/vypnuté zpracování:
foreach ($instances as $instId => $instAttrs) {
    $instances[$instId]["instOn"] = empty($processedInstances[$instId]) ? false : true;         // vstupní hodnota false se vyhodnotí jako empty :)
}                                                                                               // "vypnutí" zpracovávání dané instanceznamená, že se zpracovávájí jen statické tabulky
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// struktura tabulek

/* základní požadavky nutné u pořadí tabulek:
        - 'records' a 'recordSnapshots' se odkazují na 'statuses'.'idstatus' → musí být uvedeny až za 'statuses' (pro případ použití commonStatuses)
        - 'records' a 'fieldValues' se tvoří pomocí pole $fields vzniklého z tabulky 'fields' → musí být uvedeny až za 'fields' (kvůli foreach)
   detailní požadavky pořadí tabulek (respektující integritní vazby mezi tabulkami pro správnou funkci integritní validace - stejné jako u writeru):
        skupina 1  -  (groups)*, (databaseGroups)*, (instances)*, statuses              *  - out-only tabulky, vznikají v transformaci
        skupina 2  -  queues, fields, users, pauses, ticketSla**, crmRecordTypes**      ** - jen u v6
        skupina 3  -  accounts**, databases**, ticketCategories**, readySessions**, loginSessions, pauseSessions, queueSessions   *** - jen u v5
        skupina 4  -  contacts**, records, calls***
        skupina 5  -  recordSnapshots, (fieldValues)*, (contFieldVals)*, tickets**
        skupina 6  -  crmRecords**, activities**, (tickFieldVals)*                                     
        skupina 7  -  crmRecordSnapshots**, (crmFieldVals)*
*/

// vstupně-výstupní tabulky (načtou se jako vstupy, transformují se a výsledek je zapsán jako výstup)

/* "tab" => [   "instPrf" - prefixovat hodnoty ve sloupci identifikátorem instance (0/1),
                "pk"   (nepovinné) - primární klíč (1),
                "fk"   (nepovinné) - cizí klíč (tabName),
                "tt"   (nepovinné) - sloupec obsahující title (1),
                "json" (nepovinné) - přítomnost klíče indikuje, že jde o JSON; 0/1 = jen rozparsovat / rozparsovat a pokračovat ve zpracování hodnoty (0/1),
                "ti"   (nepovinné) - parametr pro časovou restrikci záznamů (1) - jen u tabulek obsahujících dynamické údaje
                                     [statické se nesmějí datumově restringovat (např. "crmRecordTypes" datem vytvoření), musejí být k dispozici] 
            ]
*/
$tabsInOutV56_part1 = [
    // skupina 1 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 2 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
];
$tabsInOutV6_part1  = [
    // skupina 2 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 3 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 4 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    ];
$tabsInOutV5  = [
    // skupina 3 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
];
$tabsInOutV56_part2 = [
    // skupina 3 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 4 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 5 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
];
$tabsInOutV6_part2 = [            // vstupně-výstupní tabulky používané pouze u Daktely v6
    // skupina 5 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 6 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    // skupina 7 -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
];
$tabsInOut = [
    5                   =>  array_merge($tabsInOutV56_part1, $tabsInOutV5, $tabsInOutV56_part2),
    6                   =>  array_merge($tabsInOutV56_part1, $tabsInOutV6_part1, $tabsInOutV56_part2, $tabsInOutV6_part2)
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// jen vstupní tabulky
$tabsInOnlyV5  = []; 
$tabsInOnlyV56 = [
    "fields"            =>  [   "idfield"               =>  ["instPrf" => 1, "pk" => 1],
                                "title"                 =>  ["instPrf" => 0, "tt" => 1],
                                "idinstance"            =>  ["instPrf" => 0, "fk" => "instances"],
                                "name"                  =>  ["instPrf" => 0]
                            ],
    "records"           =>  [   "idrecord"              =>  ["instPrf" => 1, "pk" => 1],
                            "iduser"                =>  ["instPrf" => 1, "fk" => "users"],
                            "idqueue"               =>  ["instPrf" => 1, "fk" => "queues"],
                            "idstatus"              =>  ["instPrf" => 1, "fk" => "statuses"],
                            "iddatabase"            =>  ["instPrf" => 1, "fk" => "databases"],
                            "number"                =>  ["instPrf" => 0],
                            "idcall"                =>  ["instPrf" => 1, "fk" => "calls"],
                            "action"                =>  ["instPrf" => 0],
                            "edited"                =>  ["instPrf" => 0, "ti" => 1],
                            "created"               =>  ["instPrf" => 0],
                            "idinstance"            =>  ["instPrf" => 0],
                            "form"                  =>  ["instPrf" => 0, "json" => 0]               // "json" => <0/1> ~ jen rozparsovat / rozparsovat a pokračovat ve zpracování hodnoty
                        ]
];
$tabsInOnlyV6  = [
    "contacts"          =>  [   "idcontact"             => ["instPrf" => 1, "pk" => 1],
                                "name"                  => ["instPrf" => 0],
                                "title"                 => ["instPrf" => 0, "tt" => 1],
                                "firstname"             => ["instPrf" => 0],
                                "lastname"              => ["instPrf" => 0],
                                "idaccount"             => ["instPrf" => 1, "fk" => "accounts"],
                                "iduser"                => ["instPrf" => 1, "fk" => "users"],
                                "description"           => ["instPrf" => 0],
                                "deleted"               => ["instPrf" => 0],
                                "idinstance"            => ["instPrf" => 0, "fk" => "instances"],
                                "form"                  => ["instPrf" => 0, "json" => 1],
                                "number"                => ["instPrf" => 0]
                            ],
    "crmFields"         =>  [   "idcrmfield"            => ["instPrf" => 1],
                                "title"                 => ["instPrf" => 0],
                                "idinstance"            => ["instPrf" => 0],
                                "name"                  => ["instPrf" => 0]
                            ],
    "tickets"           =>  [   "idticket"              => ["instPrf" => 1, "pk" => 1],
                                "name"                  => ["instPrf" => 0],
                                "title"                 => ["instPrf" => 0, "tt" => 1],
                                "idticketcategory"      => ["instPrf" => 1, "fk" => "ticketCategories"],
                                "iduser"                => ["instPrf" => 1, "fk" => "users"],
                                "email"                 => ["instPrf" => 0],
                                "idcontact"             => ["instPrf" => 1, "fk" => "contacts"],
                                "idstatus"              => ["instPrf" => 1, "fk" => "statuses"],
                                "description"           => ["instPrf" => 0],
                                "stage"                 => ["instPrf" => 0],
                                "priority"              => ["instPrf" => 0],
                                "sla_deadtime"          => ["instPrf" => 0],
                                "sla_change"            => ["instPrf" => 0],
                                "sla_notify"            => ["instPrf" => 0],
                                "sla_duration"          => ["instPrf" => 0],
                                "sla_custom"            => ["instPrf" => 0],
                                "survey"                => ["instPrf" => 0],
                                "survey_offered"        => ["instPrf" => 0],
                                "satisfaction"          => ["instPrf" => 0],
                                "satisfaction_comment"  => ["instPrf" => 0],
                                "reopen"                => ["instPrf" => 0],
                                "deleted"               => ["instPrf" => 0],
                                "created"               => ["instPrf" => 0],
                                "edited"                => ["instPrf" => 0],        // neuvádět "ti" => 1, jde o tabulku stat. údajů, musejí být k dispozici pro podřazené "crmRecords
                                "edited_by"             => ["instPrf" => 1],
                                "first_answer"          => ["instPrf" => 0],
                                "first_answer_duration" => ["instPrf" => 0],
                                "closed"                => ["instPrf" => 0],
                                "unread"                => ["instPrf" => 0],
                                "idinstance"            => ["instPrf" => 0, "fk" => "instances"],
                                "form"                  => ["instPrf" => 0, "json" => 0]
                            ],
    "crmRecords"        =>  [   "idcrmrecord"           => ["instPrf" => 1, "pk" => 1],
                                "name"                  => ["instPrf" => 0],    
                                "title"                 => ["instPrf" => 0, "tt" => 1],
                                "idcrmrecordtype"       => ["instPrf" => 1, "fk" => "crmRecordTypes"],
                                "iduser"                => ["instPrf" => 1, "fk" => "users"],
                                "idcontact"             => ["instPrf" => 1, "fk" => "contacts"],
                                "idaccount"             => ["instPrf" => 1, "fk" => "accounts"],
                                "idticket"              => ["instPrf" => 1, "fk" => "idticket"],
                                "idstatus"              => ["instPrf" => 1, "fk" => "idstatus"],
                                "description"           => ["instPrf" => 0],
                                "deleted"               => ["instPrf" => 0],
                                "edited"                => ["instPrf" => 0, "ti" => 1],
                                "created"               => ["instPrf" => 0],
                                "stage"                 => ["instPrf" => 0],
                                "idinstance"            => ["instPrf" => 0, "fk" => "instances"],
                                "form"                  => ["instPrf" => 0, "json" => 0]
                            ]
];
$tabsInOnly = [
    5                   =>  array_merge($tabsInOnlyV5, $tabsInOnlyV56),
    6                   =>  array_merge($tabsInOnlyV56, $tabsInOnlyV6)
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// jen výstupní tabulky
$tabsOutOnlyV56 = [         // tabulky, které vytváří transformace a objevují se až na výstupu (nejsou ve vstupním bucketu KBC) používané u Daktely v5 i v6
    "fieldValues"       =>  [   "idfieldvalue"          => ["instPrf" => 1, "pk" => 1],
                                "idrecord"              => ["instPrf" => 1, "fk" => "records"],
                                "idfield"               => ["instPrf" => 1, "fk" => "fields"],
                                "value"                 => ["instPrf" => 0]
                            ]
];
$tabsOutOnlyV6 = [          // tabulky, které vytváří transformace a objevují se až na výstupu (nejsou ve vstupním bucketu KBC) používané pouze u Daktely v6
    "contFieldVals"     =>  [   "idcontfieldval"        => ["instPrf" => 1, "pk" => 1],
                                "idcontact"             => ["instPrf" => 1, "fk" => "contacts"],
                                "idfield"               => ["instPrf" => 1, "fk" => "fields"],
                                "value"                 => ["instPrf" => 0]
                            ],
    "tickFieldVals"     =>  [   "idtickfieldval"        => ["instPrf" => 1, "pk" => 1],
                                "idticket"              => ["instPrf" => 1, "fk" => "tickets"],
                                "idfield"               => ["instPrf" => 1, "fk" => "fields"],
                                "value"                 => ["instPrf" => 0]
                            ],                                                  // hodnoty formulářových polí z tabulky "tickets"
    "crmFieldVals"      =>  [   "idcrmfieldval"         => ["instPrf" => 1, "pk" => 1],
                                "idcrmrecord"           => ["instPrf" => 1, "fk" => "crmRecords"],
                                "idfield"               => ["instPrf" => 1, "fk" => "fields"],
                                "value"                 => ["instPrf" => 0]
                            ]                                                   // hodnoty z pole "item" tabulky "activities"
];
$tabsOutOnly = [
    5                   =>  $tabsOutOnlyV56,
    6                   =>  array_merge($tabsOutOnlyV56, $tabsOutOnlyV6)
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// parametry parsování JSON řetězců záznamů z formulářových polí do out-only tabulek hodnot formulářových polí
$jsonFieldsOuts = [     // <vstupní tabulka kde se nachází form. pole> => [<název out-only tabulky hodnot form. polí>, <umělý inkrementální index hodnot form. polí>]
    "records"       =>  "fieldValues",
    "contacts"      =>  "contFieldVals",
    "tickets"       =>  "tickFieldVals",
    "crmRecords"    =>  "crmFieldVals"
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// jen vstupní sloupce
$colsInOnly = [         // seznam sloupců, které se nepropíší do výstupních tabulek (slouží jen k internímu zpracování)
 // "název_tabulky"     =>  ["název_sloupce_1", "název_sloupce_2, ...]
    "fields"            =>  ["name"],   // systémové názvy formulářových polí, slouží jen ke spárování "čitelných" názvů polí s hodnotami polí parsovanými z JSONu
    "records"           =>  ["form"],   // hodnoty formulářových polí z tabulky "records"    jako neparsovaný JSON
    //"contacts"          =>  ["form"],   // hodnoty formulářových polí z tabulky "contacts"   jako neparsovaný JSON
    "tickets"           =>  ["form"],   // hodnoty formulářových polí z tabulky "tickets"    jako neparsovaný JSON
    "crmRecords"        =>  ["form"],   // hodnoty formulářových polí z tabulky "crmRecords" jako neparsovaný JSON
    //"activities"      =>  [...]
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// proměnné pro práci se všemi tabulkami
$tabsList_InOut = [
    5                   =>  array_keys($tabsInOut[5]),
    6                   =>  array_keys($tabsInOut[6])
];
$tabs_InOut_InOnly = [  // nutno dodržet pořadí spojování polí, aby in-only tabulka "crmFields" (v6) byla před tabulkami závislými na "fields" !
    5                   => array_merge($tabsInOnly[5], $tabsInOut[5]),
    6                   => array_merge($tabsInOnly[6], $tabsInOut[6])
];
$tabs_InOut_OutOnly = [      
    5                   => array_merge($tabsInOut[5], $tabsOutOnly[5]),
    6                   => array_merge($tabsInOut[6], $tabsOutOnly[6])
];
$tabsList_InOut_InOnly = [
    5                   => array_keys($tabs_InOut_InOnly[5]),
    6                   => array_keys($tabs_InOut_InOnly[6])
];
$tabsList_InOut_OutOnly = [
    5                   => array_keys($tabs_InOut_OutOnly[5]),
    6                   => array_keys($tabs_InOut_OutOnly[6])
];
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// pole obsahující unikátní seznam výstupních tabulek všech verzí Daktely s počtem sloupců jednotlivých tabulek
$outTabsColsCount = [];
foreach ($tabs_InOut_OutOnly as $verTabs) {                     // iterace podle verzí Daktely (klíč = 5, 6, ...)
    foreach ($verTabs as $tab => $cols) {                       // iterace definic tabulek v rámci dané verze
        $colNames = array_key_exists($tab, $colsInOnly) ? array_diff(array_keys($cols), $colsInOnly[$tab]) : array_keys($cols); // jsou-li některé sloupce jen vstupní nezapočtou se
        if (!array_key_exists($tab, $outTabsColsCount)) {
            $outTabsColsCount[$tab] = count($colNames);
        }
    }
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
// ostatní proměnné

// volitelná náhrada prázdných hodnot ID umělou hodnotou ID, která odpovídá umělému title
// motivace:  pro joinování tabulek v GD (tam se prázdná hodnota defaultně označuje jako "(empty value)")
$emptyToNA   = true;
$fakeId      = "0";
$fakeTitle   = "";                                          // původně "(empty value)"
$tabsFakeRow = $tabsList_InOut_OutOnly[6] + $tabsList_InOut_OutOnly[5];
                                                            // = všechny InOut tabulky napříč verzemi (původně jen ["users", "statuses"])
                                                            // sjednocení polí je nekomutativní!! - jinak jsou ve výsledném poli názvy některých tabulek 2× !!

// počty číslic, na které jsou doplňovány ID's (kvůli řazení v GoodData je výhodné mít konst. délku ID's) a oddělovač prefixu od hodnoty
$idFormat = [
    "sep"       =>  "",                                     // znak oddělující ID instance od inkrementálního ID dané tabulky ("", "-" apod.)
    "instId"    =>  ceil(log10(max(2, count($instances)))), // počet číslic, na které je doplňováno ID instance (hodnota před oddělovačem) - určuje se dle počtu instancí
    "idTab"     =>  8,                                      // výchozí počet číslic, na které je doplňováno inkrementální ID dané tabulky (hodnota za oddělovačem);
                                                            // příznakem potvrzujícím, že hodnota dostačovala k indexaci záznamů u všech tabulek, je proměnná $idFormatIdEnoughDigits;
                                                            // nedoplňovat = "" / 0 / NULL / []  (~ hodnota, kterou lze vyhodnotit jako empty)    
    "idField"   =>  3                                       // výchozí počet číslic, na které je doplňováno inkrementální ID hodnot konkrétního form. pole
];

// delimitery názvu skupiny v queues.idgroup
$delim = [ "L" => "[[" , "R" => "]]" ];

// defaultní počet znaků pro ořez řetězců hodnot z JSONů (hodnoty form. polí, hodnoty z activities.items)
$strTrimDefaultLen = 8000;                                  // GD dovolí až 65 535 znaků, někde (např. u activities.items) se vyskytují i delší řetězce!  

// klíčová slova pro identifikaci typů formulářových polí a pro validaci + konverzi obsahu formulářových polí
$keywords = [
    "dateEq" => ["od", "do"],
    "mailEq" => ["mail", "email", "e-mail"],
    "date"   => ["datum"],
    "name"   => ["jméno", "jmeno", "příjmení", "prijmeni", "řidič", "ceo", "makléř", "předseda"],
    "addr"   => ["adresa", "address", "město", "mesto", "obec", "část obce", "ulice", "čtvrť", "ctvrt", "okres"],
    "psc"    => ["psč", "psc"],
    "addrVal"=> ["do","k","ke","mezi","na","nad","pod","před","při","pri","u","ve","za","čtvrť","ctvrt","sídliště","sidliste","sídl.","sidl.",
                 "ulice","ul.","třída","trida","tř.","tr.","nábřeží","nábř.","nabrezi","nabr.","alej","sady","park","provincie","svaz","území","uzemi",
                 "království","kralovstvi","republika","stát","stat","ostrovy", "okr.","okres","kraj", "kolonie","č.o.","c.o.","č.p.","c.p."],
                 // místopisné předložky a označení
    "romnVal"=> ["i", "ii", "iii", "iv", "vi", "vii", "viii", "ix", "x", "xi", "xii", "xiii", "xiv", "xv", "xvi", "xvii", "xviii", "xix", "xx"],
    "noConv" => ["v"]   // nelze rozhodnout mezi místopis. předložkou a řím. číslem → nekonvertovat case    
];