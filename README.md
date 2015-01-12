# GametrackerJSON
A quick HTML scrubber that JSON-ifies player table data and responds to cross domain or same domain requests.

Currently returns top players from a staticly set server. Dynamic AJAX requests are being completed soon.

Example Usage:
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
