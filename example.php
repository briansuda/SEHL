<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
<head>
</head>
<body>
<?php
if (!isSet($_GET['q'])) { $q = "All \"New York\" winter"; } else {$q = $_GET['q'];}
?>


<form method="get">
<h1>Example Search Engine</h1>
<input type="text" name="q" value="<?php echo (stripslashes(htmlspecialchars($q))); ?>" />
<input type="submit" value="Search" />
<br />
</form>

<?php
	/* get the parts of the referrer */
	$ref = getenv("REQUEST_URI");
	$url_array = parse_url($ref);
	/* If there is no query string, exit */
	if (!isSet($url_array['query'])) {$url_query = '';} else {$url_query = $url_array['query'];}

	/* initialise variables */
	$colour = 0;

	/* parse the query into keys and values */
	$query_array = split("&",$url_query);

	/* get the search terms from the query string */
	foreach($query_array as $var){
		$var_array = split("=",$var);

		/* This is where you can add support for more search engines */
		if (($var_array[0] == "q") or ($var_array[0] == "p") or ($var_array[0]=="ask") or ($var_array[0]=="searchfor") or ($var_array[0]=="key") or ($var_array[0]=="query") or ($var_array[0]=="va")){
			echo "<h2>RESULTS</h2>\n";
			echo "1. <a href=\"results.php\">The best result for your search!</a>";
		}
	}
?>
<br/>
<br/>
<small>This is using SEHL</small>
</body>
</html>
