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
require('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once(dirname(__FILE__)."/SmartDOMDocument.class.php");
$version = "2.2";

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

//The function to finally return JSON data, this script should only output this once.
function jsonecho($jsonoutputvar) {
    if(isset($_GET['callback'])) {
        echo $_GET['callback'] . '(' .  json_encode($jsonoutputvar) . ')'; //JSONP
    } else {
        echo json_encode($jsonoutputvar); //JSON
    }
}

$addechoque = array();

$DEBUGserver = '';
$DEBUGport = '';
$DEBUGquery = '';
$DEBUGrows = '';
$urlserver = '';
$urlport = '';
$urlquery = '';
$urlrows = '';

//Required - Checks IP
if (isset($_GET['server'])) {
    $serverquest = $_GET['server'];
    if (strlen($serverquest) < 45 && (filter_var($serverquest, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false || filter_var($serverquest, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)) {
        $DEBUGserver = $serverquest;
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
            $DEBUGport = (string)$portquest;
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
        $DEBUGquery = $queryquest;
        $queryquest = utf8_decode($queryquest);
        $urlquery = '?query=' . urlencode($queryquest) . '&Search=Search';
    }
}

if (isset($_GET['rows'])) {
    $urlquest = $_GET['rows'];
    if (is_numeric($urlquest) && strlen($urlquest) <= 50) {
        $urlquest = intval($urlquest);
        if (filter_var($urlquest, FILTER_VALIDATE_INT) == true) {
            $DEBUGrows = (string)$urlquest;
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

// Page catching
$cachedir = dirname(__FILE__)."/cache/" ; // Directory to cache files in (keep outside web root)
$cachetime = 300; // Seconds to cache files for
$cacheext = '.json'; // Extension to give cached files (usually cache, htm, txt)
$cachefile = $cachedir . md5($GTURL) . $cacheext; // Cache file to either load or create

$cachefile_created = @file_exists($cachefile) ? @filemtime($cachefile) : 0;
@clearstatcache();

// Show file from cache if still valid
if (time() - $cachetime < $cachefile_created) {
    $zerph = json_decode(file_get_contents($cachefile));
    jsonecho($zerph);
    $oldcache = false;
    exit;
} else {
    $CURLPAGE = curlrequest($GTURL);
    $DEBUGnewpage = true;
    $xpath = htmltoxpath($CURLPAGE, false);
    $oldcache = true;
}

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
unset($xpathtable);

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

function isempty($var) {
    if (empty($var)) {
     return "blank";
    } else {
    return $var;
    }
}

$i = 0;
$toarrayjson = array();
$collection = new stdClass();
for ($i = 0; $i < count($toarraytospecific); $i++) {
    $collection->{isempty(${"dataarray$i"}[0])} = unsetitles(${"dataarray$i"});
}

$DEBUGarray = array("freshpage" => $DEBUGnewpage, "format" => "none", "GTserver" => $DEBUGserver, "GTport" => $DEBUGport, "rows" => $DEBUGrows, "revision" => $version);
//Optional - Checks format type and outputs accordingly.
//Defaults to columns as unique arrays.
if(isset($_GET['format'])) {
    $outputformat = $_GET['format'];
    //Sends each player as a unique array, useful for tables.
    if ($outputformat === 'table') {
        $jsonobjects = $nodestorearray;
        $defaultformat = false;
    }
    //Sends raw HTML table
    elseif ($outputformat === 'raw') {
        if ($oldcache == true) {
            $jsonobjects = $htmlstore;
            $defaultformat = false;
        } else {
            $rawdomdoc = new SmartDOMDocument();
            $rawdomdoc->appendChild($rawdomdoc->importNode($nodes, true));
            $image = $rawdomdoc->saveHTMLExact();
            $htmlstore = preg_replace('/^\s+|\n|\r|\s+$/m', '', $image);
            $jsonobjects = $htmlstore;
            $defaultformat = false;
        }
    //Defaults to columns as unique arrays.
    } else {
    $defaultformat = true;
    }
} else {
    $defaultformat = true;
}

if ( $defaultformat == true ) {

    $jsonobjects = array(
	"Dataset" => $collection, 
	"Debug" => $DEBUGarray
	);
}

if ($oldcache = true) {
    @clearstatcache();
    $f = fopen($cachefile, "w+");
    ftruncate($f, 0);
    fwrite($f, json_encode($jsonobjects));
    fclose($f);
    unset($f);
}

jsonecho($jsonobjects);

exit;
?>