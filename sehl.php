<?php
/* ----------------------------------

SEARCH ENGINE HIGHLIGHING

brian suda
http://suda.co.uk

matt riggott
http://www.flother.com

NOTES:
The most upto date version of the project can be found at the following:
http://suda.co.uk/projects/SEHL/
http://sehl.googlecode.com/

COPYRIGHT:
GNU Lesser General Public License:
http://www.gnu.org/licenses/lgpl.html

WARNING:
This file MUST be saved as UTF-8
see removeaccents() for more information

---------------------------------- */

function sehl($full_html){
	/* If there is no referer, exit */
	$ref = getenv('HTTP_REFERER');
	if ($ref == '') {return $full_html;}

	/* parse the referer */
	$url_array = parse_url($ref);

	/* If there is no query string, exit */
	if (!isset($url_array['query'])) {return $full_html;}

	/* This is so we can sperate the body from html if ob_start is before body */
	/* Prevents highlighting words outside the body element */
	$pieces = explode('<body',$full_html,2);

	if (!isset($pieces[1])) {
		$before_body = '';
		$full_body = $full_html;
	} else {
		/* This needs to be added AFTER the body tag */
		$bbody = explode('>',$pieces[1],2);
		$pieces[0] .= '<body'.$bbody[0].">\n\t\t";
		if (isset($bbody[1])){$full_body = $bbody[1];}
		$before_body = $pieces[0];
	}

	/* initialise variables */
	$colour      = 0; /* zero-out variables */
	$gCounter    = 0;
	$hl_array     = array();

	/* fetch an array of the terms to highlight */	
	$hl_array = getHighlightTerms($url_array['query']);
		
	/* The hl_array[] has all the terms of user interest. This is where you can pass this information to other services. We selected do_highlight(), you could easily call out to an ATOMZ search, internal search or other services here.  */

	/* go through and highlight the search terms in different colours */
	$hl_text = ''; /* zero-out variable */
	$prev_colour = '-1';
	global $gCounter;
	$prev_gCounter = 0;
	$prev_phrase = '';
	$show=0;
	$other_occurances = 0;

	foreach($hl_array as $hl_phrase=>$hl_colour){
		$full_body = do_highlight($full_body, $hl_phrase, $hl_colour);
		
			
		/* Add terms to optional display box */
		$occurance = ($gCounter-$prev_gCounter);
		
		if ($prev_colour != $hl_colour){
			$show++;
			
			if ($prev_phrase != ''){
				$hl_text .= '<span class="hl'.$prev_colour.'" title="&#39;'.$prev_phrase.'&#39; occurs '.$prev_occurance.' '.pluralize('time',$prev_occurance).' on this page';
				if ($other_occurances > 0){
					$hl_text .= ' with '.$other_occurances.' other possible '.pluralize('derivitive',$prev_occurance);
				}
				$hl_text .= '.">'.$prev_phrase.'</span> ';
			}
			
			$prev_phrase = $hl_phrase;
			$prev_colour = $hl_colour;
			$prev_occurance = $occurance;
			$prev_gCounter = $gCounter;
			$other_occurances = 0;
			$prev_colour = $hl_colour;
		} else {
			if ($prev_gCounter != $gCounter) { 
				$other_occurances += $occurance;
				$prev_gCounter = $gCounter;
			}
		}
		
	}
	/* get the last item for the display box */
	$hl_text .= '<span class="hl'.$prev_colour.'" title="&#39;'.$prev_phrase.'&#39; occurs '.$prev_occurance.' '.pluralize('time',$prev_occurance).' on this page';
	if ($other_occurances > 0){
		$hl_text .= ' with '.$other_occurances.' other '.pluralize('derivitive',$prev_occurance);
	}
	$hl_text .= '.">'.$prev_phrase.'</span> ';
		
			
	/* OPTIONAL: prepend information to the page for the user*/
	if ($show){
		$box = '<div class="center"><div id="sehlalertbox"><h2>Why ';
		if ($show<2){$box.='is';}else{$box.='are';}
		$box .= ' '.$hl_text.' highlighted?</h2>The <a href="http://suda.co.uk/projects/SEHL/" title="Search Engine Highlight Function">Search Engine Highlight Feature</a> tags your search terms for easy identification.</div></div>'."\n";
		$full_body = $box.$full_body;
	}
	
	/* return the page to the client */
	return $before_body.$full_body;
}

function getHighlightTerms($query_string){
	$max_colours = 5; /* Number of highlight colours in the css */
	
	/* This is where you can add support for more search engines */
	$acceptedQueryKeys = array(
	 'q',
	 'p',
	 'ask',
	 'searchfor',
	 'key',
	 'query',
	 'search',
	 'keyword',
	 'keywords',
	 'qry',
	 'searchitem',
	 'kwd',
	 'recherche',
	 'search_text',
	 'search_term',
	 'term',
	 'terms',
	 'qq',
	 'qry_str',
	 'qu',
	 's',
	 'k',
	 't',
	 'va'
	);

	/* parse the query into keys and values */
	/* Here we are making the assumption that the query will be split by
	ampersands.  The W3C recommend using a semi-colon to avoid problems with
	HTML entities, and so some user-agents may use these.  However, all said
	and done it's a fairly safe assumption. */
	$query_array = explode('&',$query_string);
	
	/* get the search terms from the query string */
	foreach($query_array as $var){
		$var_array = explode('=',$var);

		if (in_array($var_array[0], $acceptedQueryKeys)){
			/* TODO: strip-out advanced search paramters like (+/-) operators */
			/* parse the search terms for quoted values */
			$hl_array = parse_quote_string($var_array[1]);
			$hl_array = expand_list($hl_array,$max_colours);
		}
	}	
		
	return $hl_array;
}

function do_highlight($full_body, $q, $colour){
	global $gCounter;
	/* seperate tags and data from the HTML file INCLUDING comments, avoiding HTML in the comments */
	$pat = '/((<[^!][\/]*?[^<>]*?>)([^<]*))|<!---->|<!--(.*?)-->|((<!--[ \r\n\t]*?)(.*?)[ \r\n\t]*?-->([^<]*))/si';
/*
	$pat = '/((<[^!][\/]*?[^<>]*?>)([^<]*))|<!---->|<!--(.*)-->|((<!--[ \r\n\t]*)(.*?)[ \r\n\t]*-->([^<]*))/si';
*/
	preg_match_all($pat,$full_body,$tag_matches);
	$full_body_hl = '';
	/* loop through and highlight $q value in data and recombine with tags */
	for ($i=0; $i< count($tag_matches[0]); $i++) {
		/* ignore all text within these tags */
		if (
			(preg_match('/<!/i', $tag_matches[0][$i])) or 
			(preg_match('/<textarea/i', $tag_matches[2][$i])) or 
			(preg_match('/<script/i', $tag_matches[2][$i]))
		){
			/* array[0] is everything the REGEX found */
			$full_body_hl .= $tag_matches[0][$i];
		} else {
			$full_body_hl .= $tag_matches[2][$i];
			/* this one ALMOST works, except if the string is at the start or end of a string*/
			// 
			$holder = preg_replace('/(.*?)(\W)('.preg_quote($q,'/').')(\W)(.*?)/iu',"\$1\$2<span class=\"hl$colour\">\$3</span>\$4\$5",' '.$tag_matches[3][$i].' ');
			$full_body_hl .= substr($holder,1,(strlen($holder)-2));
			
			if ((strlen($tag_matches[3][$i]) + 2) != strlen($holder)){
				$gCounter += (strlen($holder) - (strlen($tag_matches[3][$i]) + 2))%(strlen("<span class='hl".$colour."'></span>")-1);
			}
			
			/* ORIGINAL REGEX with word boundries */
			/* the slash-i is for case-insensitive and the slash-b's are for word boundries */
			/*$full_body_hl .= preg_replace("/(.*?)\b(".preg_quote($q).")\b(.*?)/i", "\$1<span class=\"hl$colour\">\$2</span>\$3", $tag_matches[3][$i]);*/
		}
	}
	/* return tagged text */
	return $full_body_hl;
}

function parse_quote_string($query_string){
	/* urldecode the string and setup variables */
	$query_string = urldecode($query_string);
	$quote_flag = false;
	$word = '';
	$terms = array();

	/* loop through character by character and move terms to an array */
	for($i=0;$i<strlen($query_string);$i++){
		$char = substr($query_string,$i,1);
		if ($char == '"'){
			if ($quote_flag) { $quote_flag = false; } else { $quote_flag = true; }
		}
		if (($char == ' ') and (!($quote_flag))){
			$terms[] = $word;
			$word = '';
		} else {
			if (!($char == '"')) { $word .= $char; }
		}
	}
	$terms[] = $word;
	/* return the fully parsed array */
	return $terms;
}

function expand_list($hl_array,$max_colours){
	$colour = 0;
	$hl_array_expanded = array();
	foreach ($hl_array as $hl){		
		$hl_ascii = '';
		/* Attempt to get the correct encoding */
		/* If you use other encodings, then they should be listed here */
		if (mb_strlen($hl,'iso-8859-1') > mb_strlen($hl,'utf-8')){
			$hl_ascii = utf8_decode($hl);
		}

		if (mb_strlen($hl,'iso-8859-1') == mb_strlen($hl,'utf-8')){
			$hl_ascii = $hl;
			$hl = utf8_encode($hl);
		}

		if ($hl != '') {
			/* STEP 1: just push the term onto the array */
			$hl_array_expanded = array_merge($hl_array_expanded,array($hl => $colour%$max_colours));

			/* STEP 2: Look for Common HTML entities */
			if($hl_ascii!=htmlentities($hl_ascii)){
				$hl_array_expanded = array_merge($hl_array_expanded,array(htmlentities($hl_ascii) => $colour%$max_colours));
			}

			/* STEP 3: downcast letters */
			if($hl!=removeaccents($hl)){
				$hl_array_expanded = array_merge($hl_array_expanded,array(removeaccents($hl) => $colour%$max_colours));
			}
			
			/* STEP 4: HEX encode */
			if($hl_ascii!=convertHEX($hl_ascii)){
				$hl_array_expanded = array_merge($hl_array_expanded,array(convertHEX($hl_ascii) => $colour%$max_colours));
			}
			
			/* STEP 5: DEC encode */
			if($hl_ascii!=convertDEC($hl_ascii)){
				$hl_array_expanded = array_merge($hl_array_expanded,array(convertDEC($hl_ascii) => $colour%$max_colours));
			}

			/* STEP 6: Pluralize */
			if($hl!=pluralize($hl)){
				$hl_array_expanded = array_merge($hl_array_expanded,array(pluralize($hl) => $colour%$max_colours));
			}
			
			/* STEP 7: Singluarlize */
			
			/* STEP 8: Stemming Algorithm to find roots */

			
			$colour++;
		}
	}
	
	return $hl_array_expanded;
}

function removeaccents($string){
/* ****************************************************************** */
/*                             WARNING                                */
/* This file MUST be saved as UTF-8, saving it as anything else will  */
/* change the following function to a two-byte chacter changing       */
/* the function so it doesn't match any characters, and no tagging.   */
/* ****************************************************************** */

/* This function moves special UTF-8 characters down to standard 256-ASCII charset */
/* You may have to change this to your specific language, for example in german 'ü' may downcast to 'ue' instead of 'u' */
 return strtr($string, array('À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A','Æ' => 'AE','Ç' => 'C','È' => 'E', 'É' => 'E','Ê' => 'E','Ë' => 'E','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I','Ð' => 'D','Ñ' => 'N','Ò' => 'O','Ó' => 'O','Ô' => 'O','Õ' => 'O','Ö' => 'O','Ø' => 'O','Ù' => 'U','Ú' => 'U','Ü' => 'U','Û' => 'U','Ý' => 'Y','Þ' => 'Th','ß' => 'ss','à' => 'a','á' => 'a','â' => 'a','ã' => 'a','ä' => 'a','å' => 'a','æ' => 'ae','ç' => 'c','è' => 'e','é' => 'e','ê' => 'ë','ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ð' => 'd','ñ' => 'n','ò' => 'o','ó' => 'o','ô' => 'o','õ' => 'o','ö' => 'o','ø' => 'o','ù' => 'u','ú' => 'u','û' => 'u','ü' => 'u','ý' => 'y','þ' => 'th','ÿ' => 'y','Œ' => 'OE','œ' => 'oe'));
}

/* Get the HEX representation of some characters */
function convertHEX($str) {
	$result = '';
	for($i = 0; $i < strlen($str); $i++) {
		if(ord(substr($str, $i, 1)) > 127){
			$result .= '&#x'.bin2hex(substr($str, $i, 1)).';';
		} else{
			$result .= substr($str, $i, 1);
		}
	}
	return $result;
}

/* Get the decimal representation of some characters */
function convertDEC($str) {
	$result = '';
	for($i = 0; $i < strlen($str); $i++) {
		if(ord(substr($str, $i, 1)) > 127){
			$result .= '&#'.ord(substr($str, $i, 1)).';';
		} else{
			$result .= substr($str, $i, 1);
		}
	}
	return $result;
}

/* Stole these two functions, need to update and correct */
function pluralize ($word,$count=2) {
	if ($count > 1){
		$plural_rules = array(
			'/(x|ch|ss|sh)$/'         => '\1es',       # search, switch, fix, box, process, address
			'/series$/'               => '\1series',
			'/([^aeiouy]|qu)ies$/'    => '\1y',
			'/([^aeiouy]|qu)y$/'      => '\1ies',      # query, ability, agency
			'/(?:([^f])fe|([lr])f)$/' => '\1\2ves',    # half, safe, wife
			'/sis$/'                  => 'ses',        # basis, diagnosis
			'/([ti])um$/'             => '\1a',        # datum, medium
			'/person$/'               => 'people',     # person, salesperson
			'/man$/'                  => 'men',        # man, woman, spokesman
			'/child$/'                => 'children',   # child
			'/s$/'                    => 's',          # no change (compatibility)
			'/$/'                     => 's'
		);
		foreach ($plural_rules as $rule => $replacement) {
			if (preg_match($rule, $word)) {
				return preg_replace($rule, $replacement, $word);
			}
		}
	} else {
		return $word;
	}
}

?>