<?php
//The MIT License (MIT)
//
//Copyright (c) 2014 Cyken Zeraux aka CZauX
//
//Permission is hereby granted, free of charge, to any person obtaining a copy
//of this software and associated documentation files (the "Software"), to deal
//in the Software without restriction, including without limitation the rights
//to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//copies of the Software, and to permit persons to whom the Software is
//furnished to do so, subject to the following conditions:
//
//The above copyright notice and this permission notice shall be included in all
//copies or substantial portions of the Software.
//
//THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
//IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
//FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
//AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
//LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
//OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
//SOFTWARE.
//
header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once(dirname(__FILE__)."/SmartDOMDocument.class.php");

function supamicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   return (string)$usec;
}

function htmltoxpath($htmlinput, $typebool) {
    if ($typebool == true) {
        $html = file_get_contents(strval($htmlinput));
    } else {
        $html = $htmlinput;
    }
    $dom = new SmartDOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    return $xpath;
}

//domdoctoxpath is used to turn a xpath query back into xpath for further querying.
function domdoctoxpath($domdocinput) {
    //local variables clean themselves up, no need to unset.
    $newdomdoc = new SmartDOMDocument();
    $newdomdoc->appendChild($newdomdoc->importNode($domdocinput, true));
    $newxpathtable = new DOMXPath($newdomdoc);
    //DEBUG $image = $newdomdoc->saveHTMLExact();echo($image);
    return $newxpathtable;
}

function curlrequest($xURL) {
    $curl_connection = curl_init($xURL);
    curl_setopt($curl_connection, CURLOPT_HTTPGET, 1);
    curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($curl_connection, CURLOPT_FAILONERROR, true);
    $receivedpage = curl_exec($curl_connection);
    if( curl_errno($curl_connection) ) {
        jsonecho('cURL: ' . curl_error($curl_connection));
        exit;
    }
    curl_close($curl_connection);
    return $receivedpage;
}

//Used to remove labels included in the table column
//By default, Gametracker has a label at the beginning and end of the table.
function unsetitles($array) {
    unset($array[0]);
    unset($array[(count($array))]);
    return array_values($array);
}

function jsonecho($jsonoutputvar) {
    if(isset($_GET['callback'])) {
        echo $_GET['callback'] . '(' .  json_encode($jsonoutputvar) . ')'; //JSONP
    } else {
        echo json_encode($jsonoutputvar); //JSON
    }
}

$addechoque = array();
$urlserver = '';
$urlport = '';
$urlquery = '';
$urlrows = '';

//Required - Checks IP
if (isset($_GET['server'])) {
    $serverquest = $_GET['server'];
    if (strlen($serverquest) < 40 && filter_var($serverquest, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $urlserver = $serverquest;
    } else {
        $addechoque[] = "\nCritical Error: IP must be IPV4!";
    }
} else {
  $addechoque[] = "\nCritical Error: IP key/value missing!";
}

//Required - Checks Port
if (isset($_GET['port'])) {
    $portquest = $_GET['port'];
    //crude way of checking if port is an absolute integer within range.
    if (is_numeric($portquest) && strlen($portquest) < 10) {
        $portquest = intval($portquest);
        if (filter_var($portquest, FILTER_VALIDATE_INT) == true && $portquest > 0) {
            $urlport = $portquest;
        } else {
            $addechoque[] = "Critical Error: Port must be a positive integer!";
        }
    } else {
        if (strlen($portquest) <= 0) {
            $addechoque[] = "Critical Error: Missing Port!";
        } else {
            $addechoque[] = "Critical Error: Port not valid!";
        }
    }
} else {
  $addechoque[] = "Critical Error: Port key/value missing!";
}

//Optional - Checks Query
if (isset($_GET['query'])) {
    $queryquest = $_GET['query'];
    if (is_string($queryquest) && strlen($queryquest) < 200 && strlen($queryquest) > 0) {
        $queryquest = utf8_decode($queryquest);
        $urlquery = '?query=' . urlencode($queryquest) . '&Search=Search';
    }
}

if (isset($_GET['rows'])) {
    $urlquest = $_GET['rows'];
    if (is_numeric($urlquest) && strlen($urlquest) <= 50) {
        $urlquest = intval($urlquest);
        if (filter_var($urlquest, FILTER_VALIDATE_INT) == true) {
            $urlrows = '&searchipp=' . $urlquest;
        }
    }
}


//Echo's critical errors to requester in a list form, then terminates program.
if (count($addechoque) > 0) {
    jsonecho(implode('\n', $addechoque));
    exit;
}

$GTURL =  "http://www.gametracker.com/server_info/" . $urlserver . ':' . $urlport . '/top_players/' . $urlquery . $urlrows ;
//https://www.gametracker.com/server_info/209.246.143.162:27015/top_players/?query=colgate&Search=Search&searchpge=2&searchipp=25#search
$CURLPAGE = curlrequest($GTURL);

//This is where the xpathing fun starts.
$xpath = htmltoxpath($CURLPAGE, false);

//Seperates the selected Table by it's class, and then applies it as a new SmartDomDocument.
$nodes = $xpath->query("//table[contains(@class, 'table_lst_spn')]")->item(0);

if ($nodes == NULL) {
    jsonecho('Critical Error: Page does not exist');
    exit;
}

unset($xpath);
$xpathtable = domdoctoxpath($nodes);
$nodesTR = $xpathtable->query('//tr'); //Gets the TR elements
$nodesTD = $xpathtable->query('//tr//td'); //Gets the TD elements inside of TR
//DEBUG $nodesTRcount=$nodesTR->length;$nodesTDcount=$nodesTD->length;$nodescount=$nodesTDcount/$nodesTRcount;

$i = 0;
$nodestorearray = array();
foreach($nodesTR as $key => $tr) {
    $tds = $tr->getElementsByTagName('td'); 
    ${"nodesarray$i"} = array();
    foreach($tds as $key => $td) { //For every TR column, it will make an array of the row.
        ${"nodesarray$i"}[] = trim(htmlspecialchars_decode($td->nodeValue, ENT_HTML5));
    }
    $nodestorearray[] = ${"nodesarray$i"}; //Dat 2D array doe
    //print_r(${"nodesarray$i"});
    //echo '<br>';
    $i++;
}

$i = 0;
$toarraytospecific = array();
foreach($nodesarray0 as $key => $label) {
    ${"dataarray$i"} = array();
    for($p = 0 ; $p < count($nodestorearray) ; $p++) {
        ${"dataarray$i"}[] = ${"nodesarray$p"}[$i];
        //echo $i, '<br>';
    }
    $toarraytospecific[] = ${"dataarray$i"}; //Dat 2D array doe
    //print_r(${"dataarray$i"});
    //echo '<br>', '<br>';
    $i++;
}

$i = 0;
$toarrayjson = array();
foreach($toarraytospecific as $key => $arrayjs) {
    $jsray = array(${"dataarray$i"}[0] => unsetitles(${"dataarray$i"}));
    $toarrayjson[] = $jsray;
    //print_r($jsray);
    $i++;
}

//Optional - Checks format type and outputs accordingly.
//Defaults to columns as unique arrays.
if(isset($_GET['format'])) {
    $outputformat = $_GET['format'];
    //Sends each player as a unique array, useful for tables.
    if ($outputformat === 'table') {
        jsonecho($nodestorearray);
    }
    elseif ($outputformat === 'raw') {
        $rawdomdoc = new SmartDOMDocument();
        $rawdomdoc->appendChild($rawdomdoc->importNode($nodes, true));
        $image = $rawdomdoc->saveHTMLExact();
        jsonecho(preg_replace('/^\s+|\n|\r|\s+$/m', '', $image));
    } else {
        jsonecho($toarrayjson);
    }
} else {
    jsonecho($toarrayjson);
}
exit;
?>