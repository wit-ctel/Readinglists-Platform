<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
* Outputs an array or variable
*
* @param    $var array, string, integer
* @return    string
*/
    function debug_var($var = '')
    {
        echo _before();
        if (is_array($var))
        {
            print_r($var);
        }
            else
        {
            echo $var;
        }
        echo _after();
    }
    
//------------------------------------------------------------------------------

	function MARC2Array(){
		/**
		 * This is the start of a good method of bringing discipline to the 
		 * MARC	records.  They will be put into multideminsional arrays
		 */
		$final_array = array();
		$returnarray = array();
        foreach($this->lines as $line){
        	$prefix = true;
        	// separate the header material (the marc field)
        	// and make break all the subfields into an associative array
	        if(preg_match('/\$[a-z]{1}/', $line)){  // subfields present
           		//then get the 1st 4 chars
           		$line = preg_replace('/(^[0-9]{3})([ 0-9]{4})(.*)/', '${1}<br/>${2}<br/>${3}', $line);
	        }else{  // subfields absent
	        	$line = preg_replace('/(^[0-9]{3})(.*)/', '${1}<br/><br/>${2}', $line);
	        	$prefix = false;
	        }
	        $linearray = explode('<br/>', $line);
	        $returnarray[0] = $linearray[0];
	        
        	#print "=> " . $line . "\n";
        	
	      	if($prefix){
	        	$returnarray[1] =  explode('.', preg_replace('/(.{1})(.{1})(.{1})(.{1})/', '${1}.${2}.${3}.${4}', $linearray[1]));
	        	$returnarray[2] = preg_split('/(\$[a-z]{1})/', $linearray[2], null, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	      	}else{
	        	#print "----------------------------------------------------------------\n";
			#print "<hr/><pre>";
	        	#print_r($linearray);	
			#print "</pre>";
	      		$returnarray[1] = array('notfunny');
	      		$returnarray[2] = @array('nosubfields', $linearray[2]); // '@' to supress inevitable offset errors.
	        	#print "----------------------------------------------------------------\n";
	      	}
	        #$returnarray[1] = $funnynums[];
	        #$returnarray[2] = $subfields[];
	       	array_push($final_array, $returnarray);
        }
        return $final_array;
	}
	function iterate(){
		#print_r($this->MARC2Array());
		for($i = 0; $i < count($this->MARC2Array); $i++){
			print " " . $i . " <br/>\n";
		}
	}

	

	function MARCsyntaxHighlight($input){
		/**
		 * A convenience function for making the raw MARC record more
		 * readable during development and testing
		 * It just takes a line and adds the highlighting to it. 
		 */
			$patterns = array('/\</i','/\>/i', '/&lt;/','/&gt;/');
			$patterns[4]='/(\$.{1})/i';
			$patterns[5]='/(^[0-9]{3})(.*)/i';
			$replacements = array('&lt;','&gt;', '<span style=\'color: green; background-color: #ddffee\'>&lt;','&gt;</span>');
			$replacements[4]='<span style=\'color: blue; background-color: #ddeeff\'>${1}</span>';
			$replacements[5]='<span style=\'color: red; background-color: #ffddee\'>${1}</span> ${2}';
			return preg_replace($patterns, $replacements, $input);
	}

	function getTitle(){
		$MARC = $this->MARC2Array();
		$ret = "NO VALUE";
		for($i=0; $i < count($MARC); $i++){
			if($MARC[$i][0]=='245'){
				$ret = rtrim($MARC[$i][2][1], ' / : .');
			}
		}
		return $ret;
	}
	function getAuthor(){
		$MARC = $this->MARC2Array();
		$ret = "NO VALUE";
		for($i=0; $i < count($MARC); $i++){
			if($MARC[$i][0]=='700'){
				$ret = $MARC[$i][2][1];
			#	break;
			#}else{
			#	return "NO VALUEs";
			}
		}
		return $ret;
	}
	function getISBN(){
		$MARC = $this->MARC2Array();
		//$ret = "NO 020 FIELD";
		$ret = 'null';
		for($i=0; $i < count($MARC); $i++){
			if($MARC[$i][0]=='020'){
				$ret = $MARC[$i][2][1];
				$pat = array('/.*([0-9]{13}).*/', '/.*([0-9]{9}[0-9xX]{1}).*/i', '/(.*)\([^\)]*\)(.*)/', '/[a-z]* :/');
				$repl = array('$1','$1','$1$2','');
				$ret = preg_replace($pat,'$1',$ret);
				if(!preg_match('/^[0-9]{9}/',$ret)){
					$ret = 'null';
				}
			}
		}
		return $ret;
	}
	function getSubjects(){
		$MARC = $this->MARC2Array();
		$ret = array();
		for($i=0; $i < count($MARC); $i++){
			if($MARC[$i][0]=='650'){
				array_push($ret, $MARC[$i][2][1]);
			}
		}
		return $ret;
	}
	function getPubDate(){
		$MARC = $this->MARC2Array();
		$ret = "0000";
		for($i=0; $i < count($MARC); $i++){
			if($MARC[$i][0]=='260'){
				$ret = preg_replace("/.+([0-9]{4}).+/i", "$1", $MARC[$i][2][array_search('$c', $MARC[$i][2])+1]);
			
				if(!preg_match('/^[0-9]{4}/',$ret)){
					$ret = '0000';
				}
			#	break;
			#}else{
			#	return "0000";
			}
		}
		return $ret;
	}
	function getBestLinkURL(){
		if($this->getISBN() != 'null'){
			return "http://witcat.wit.ie/search/i?SEARCH=" . $this->XMLClean($this->getISBN());
		}else{
			return "http://witcat.wit.ie/search~S0?/t".$this->XMLClean($this->getTitle());
		}
	}
	
	
	function displayItemXML(){
		/*
		 * get the right parameters
		 * 
		 * title
		 * author
		 * ISBN
		 * subjects
		 * pubDate
		 */
		  



		
			print "<hr/>\n";
			print "PubDate: <span style='color: gray'>" . $this->XMLClean($this->getPubDate())."</span><br />\n";
			print "Title: <span style='color: gray'>" .$this->XMLClean($this->getTitle())."</span><br />\n";
			print "ISBN: <span style='color: gray'>" . $this->XMLClean($this->getISBN())."</span><br />\n";
			print "Author: <span style='color: gray'>" . $this->XMLClean($this->getAuthor())."</span><br />\n";
			//print "Subjects: " . $this->XMLClean($this->getSubjects())."<br />\n";
			print "Best Link: <a href='" . $this->XMLClean($this->getBestLinkURL())."'>" . $this->XMLClean($this->getBestLinkURL())."</a><br />\n";
			


			
		
	}

	function XMLClean($strin) {
		//tx to phil at lavin dot me dot uk
		$strout = null;
		for ($i = 0; $i < strlen($strin); $i++) {
			$ord = ord($strin[$i]);
			if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
				$strout .= "&amp;#{$ord};";
			}
			else {
				switch ($strin[$i]) {
					case '<':
						$strout .= '&lt;';                break;
					case '>':
						$strout .= '&gt;';                break;
					case '&':
						$strout .= '&amp;';               break;
					case '"':
						$strout .= '&quot;';              break;
					default:
						$strout .= $strin[$i];
				}
			}
		}
        return trim($strout);
	}


	function display($type){ 
		/**
		 * Pretty temporary function.  For early testing purposes.
		 * Takes PUBINFO||AUTHOR||STANDARD_NUMBER||TITLE as an arguement
		 */
		$output = "";
		switch($type){
			case 'SUBJECTS':
				$output = $this->SUBJECTS;			break;
			case 'PUBINFO':
				$output = $this->PUBINFO;			break;
			case 'AUTHOR':
				$output = $this->AUTHOR;			break;
			case 'STANDARD_NUMBER':
				$output = $this->STANDARD_NUMBER;	break;
			case 'TITLE':
				$output = $this->TITLE;				break;
		}
		print $this->MARCsyntaxHighlight($output);
	}

	function display_all(){
		/**
		 * Display the whole raw marc record (Syntax Highlighted)
		 */
		print"<div style=\"font-family: courier; border: solid 1px black; background-color: #c0c0c0; margin-bottom: 3px;\"><ul>";
		foreach($this->lines as $line){
			print"<li>".$this->MARCsyntaxHighlight($line)."</li>\n";
		}
		print"</ul></div>";
	}	


	
/*usage
 * This is the kind of array that is generated.
Array
(
    [0] => Array
        (
            [0] => 006
            [1] => Array
                (
                    [0] => notfunny
                )
 
            [2] => Array
                (
                    [0] => nosubfields
                    [1] => 92nam  22002055a 4500array1
                )
 
        )

    [11] => Array
        (
            [0] => 650
            [1] => Array
                (
                    [0] =>  
                    [1] =>  
                    [2] => 0
                    [3] =>  
                )
 
            [2] => Array
                (
                    [0] => $a
                    [1] =>  Engineering 
                    [2] => $x
                    [3] =>  Study and teaching (Higher) 
                    [4] => $z
                    [5] =>  Great Britain 
                    [6] => $x
                    [7] =>  Guidebooks.
                )
 
        )
}
*/




 ?>