# GametrackerJSON
A quick HTML scrubber that JSON-ifies player table data and responds to cross domain or same domain requests.

Currently returns top players from a staticly set server. Dynamic AJAX requests are being completed soon.

<h3>Dependencies: </h3>
<ul>
<li>PHP 5.5</li>
<li>cURL</li>
<li>SmartDOMDocument</li>
<li>Jquery 1.11 and above</li>
</ul>

<h3>Example Usage: </h3>
<pre>
$( document ).ready( function() {
  //Gets top 50 players of a server as unique arrays.
  var data = {"format": "table", "server": "209.246.143.162", "port": "27015", "query": "", "rows": "50"};
  data = $.param(data);

  $.ajax({
    url: "URL TO PHP SCRIPT GOES HERE",
    data: data,
    dataType: "jsonp",
    success: function(data){
        console.log(data);
        //yourfunction(data);
    },
    error: function(xhr) {
          console.log(xhr.responseText);
    }
  });
  return false;
});
</pre>
<hr>
<h3>Parameters</h3>
<h5>format: <i>table|raw|none</i></h5>
<p>table - returns a unique array for every data row, useful for tables.</p>
<p>raw - returns the raw table element in HTML.</p>
<p>none - default, returns unique array for every column.</p>
<h5>server</h5>
<p>Specifies the IPV4 IP of a game server.</p>
<h5>port</h5> 
<p>Specifies the Port used with the server IP.</p>
<h5>query</h5>
<p>Specifies the name to send through gametracker search.</p>
<h5>rows</h5>
<p>Specifies the amount of table rows to receive from the query</p>
