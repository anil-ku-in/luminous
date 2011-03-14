<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"> 
<head profile="http://purl.org/NET/erdf/profile"> 
 <title>PHP: strcmp - Manual</title> 
 <style type="text/css" media="all"> 
  @import url("http://static.php.net/www.php.net/styles/site.css");
  @import url("http://static.php.net/www.php.net/styles/phpnet.css");
 </style> 
 <!--[if IE]><![if gte IE 6]><![endif]--> 
  <style type="text/css" media="print"> 
   @import url("http://static.php.net/www.php.net/styles/print.css");
  </style> 
 <!--[if IE]><![endif]><![endif]--> 
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
 <link rel="shortcut icon" href="http://static.php.net/www.php.net/favicon.ico" /> 
 <link rel="contents" href="index.php" /> 
 <link rel="index" href="ref.strings.php" /> 
 <link rel="prev" href="function.strchr.php" /> 
 <link rel="next" href="function.strcoll.php" /> 
 <link rel="schema.dc" href="http://purl.org/dc/elements/1.1/" /> 
 <link rel="schema.rdfs" href="http://www.w3.org/2000/01/rdf-schema#" /> 
 <link rev="canonical" rel="self alternate shorter shorturl shortlink" href="http://php.net/strcmp" /> 
 <link rel="license" href="http://creativecommons.org/licenses/by/3.0/" about="#content" /> 
 <link rel="canonical" href="http://php.net/manual/en/function.strcmp.php" /> 
 <script type="text/javascript" src="http://static.php.net/www.php.net/userprefs.js"></script> 
 <base href="http://www.php.net/manual/en/function.strcmp.php" /> 
 <meta http-equiv="Content-language" value="en" /> 
                        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script> 
                        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/jquery-ui.min.js"></script> 
<script type="text/javascript"> 
$(document).ready(function() {
        var toggleImage = function(elem) {
  var x = "<?php echo $hello; ?>aaaaa <? '"' ?>bbb";
          
                if ($(elem).hasClass("shown")) {
                        $(elem).removeClass("shown").addClass("hidden");
                        $("img", elem).attr("src", "/images/notes-add.gif");
                }
                else {
                        $(elem).removeClass("hidden").addClass("shown");
                        $("img", elem).attr("src", "/images/notes-reject.gif");
                }
        };
 
        $(".refsect1 h3.title").each(function() {
        url = "http://bugs.php.net/report.php?bug_type=Documentation+problem&amp;manpage=" + $(this).parent().parent().attr("id") + "%23" + $(this).text();
                $(this).parent().prepend("<div class='reportbug'><a href='" + url + "'>Report a bug</a></div>");
                $(this).prepend("<a class='toggler shown' href='#'><img src='/images/notes-reject.gif' /></a> ");
        });
        $("#usernotes .head").each(function() {
                $(this).prepend("<a class='toggler shown' href='#'><img src='/images/notes-reject.gif' /></a> ");
        });
        $(".refsect1 h3.title .toggler").click(function() {
                $(this).parent().siblings().slideToggle("slow");
                toggleImage(this);
                return false;
        });
        $("#usernotes .head .toggler").click(function() {
                $(this).parent().next().slideToggle("slow");
                toggleImage(this);
                return false;
        });
});
</script> 
 
</head> 