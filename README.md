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
  //Gets top players of a server
  var data = {"server": "209.246.143.162:27015", "query": ""};
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
