# GametrackerJSON - Deployed with Heroku
A quick HTML scrubber written in PHP that JSON-ifies player table data and responds to cross domain or same domain requests.

This version is deployable to <a href="https://www.heroku.com/">Heroku</a>, which is currently used as a public resource in the example below

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
    url: "https://gtjsonp.herokuapp.com/",
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
