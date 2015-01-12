<!--
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
-->
<?php
header('content-type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

require_once(dirname(__FILE__)."/SmartDOMDocument.class.php");

function supamicrotime() {
   list($usec, $sec) = explode(' ', microtime());
   //$microget = explode(' ', microtime());
   return (string)$usec;
}

function htmltoxpath($htmlinput, $typebool) {
  //$html = file_get_contents(strval($htmlinput));
    $html = $htmlinput;
    $dom = new SmartDOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    return $xpath;
}

//Used to remove labels included in the table column
//By default, Gametracker has a label at the beginning and end of the table.
function unsetitles($array) {
    unset($array[0]);
    unset($array[(count($array))]);
    return array_values($array);
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

test_function();

function test_function() {
$curl_connection = curl_init("https://www.gametracker.com/server_info/209.246.143.162:27015/top_players/");
curl_setopt($curl_connection, CURLOPT_HTTPGET, 1);
curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, false);
$receivedpage = curl_exec($curl_connection);
curl_close($curl_connection);

$xpath = htmltoxpath($receivedpage, true);

//Seperates the selected Table by it's class, and then applies it as a new SmartDomDocument.
$nodes = $xpath->query("//table[contains(@class, 'table_lst_spn')]")->item(0);
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

echo $_GET['callback'] . '(' .  json_encode($toarrayjson) . ')';
}
?>