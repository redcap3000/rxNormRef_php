<?php
require '../config.php';

class rxNormRef{
	static	$normalElements = Array(
			'TTY'=>'Term Type','IN'=>'Ingredients','PIN'=>'Precise Ingredient',
			'MIN'=>'Multiple Ingredients','DF'=>'Dose Forms','SCDC'=>'Semantic Clinical Drug Components',
			'SCDF'=>'Semantic Clinical Drug Forms','BN'=>'Brand Names','SBDC'=>'Semantic Branded Drug Forms',
			'SBDF'=>'Semantic Branded Drug Forms','SBD'=>'Semantic Branded Drug','SY'=>'Term Type',
			'TMSY'=>'Term Type','BPCK'=>'Brand Name Pack','GPCK'=>'Generic Pack');

	function __construct(){
		$this->start_time = (float) array_sum(explode(' ',microtime()));
		$this->oh_memory = round(memory_get_usage() / 1024);
		// set up the 'filter' variable to determine what columns to show
		if(SHOW_ALL == FALSE){
			if(SHOW_LANGUAGE == false  ) $this->c_filter []='language';
			if(SHOW_SUPPRESS == false) $this->c_filter []='suppress';
			if(SHOW_RXCUI == false) $this->c_filter []='rxcui';
			if(SHOW_NAME == false) $this->c_filter []='name';
			if(SHOW_ALL_SYNONYM == FALSE) $this->c_filter []= 'synonym';
			if(SHOW_TTY == false) $this->c_filter []='tty';
			if(SHOW_UML == false) $this->c_filter []= 'umlscui';
		}
		// of course I could make a checkbox panel to allow for any combination of display fields, and cache entire returned xml results to do manipulations
		if(PROGRESSIVE_LOAD == true){
   	 	    @apache_setenv('no-gzip', 1);
			@ini_set('zlib.output_compression', 0);
			@ini_set('implicit_flush', 1);
			for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
			flush();
			ob_implicit_flush(1);
			ob_start();
		}
		// process any post if existant
		if($_POST) self::post_check();
		// if we haven't died by now then close and flush the ob cache for the final time
		self::ob_cacher(1);
		// echo the footer and stats to screen.
		echo '<div id="stats">' . self::stats().'</div>';

	}

	function post_check(){
	// idea why not use relatedBy and allow for lookup by id in the same form?
	
		if(count($_POST) > 1)
			foreach($_POST as $key=>$value){
				if($key == 'relatedBy' && is_array($value)){
					unset($_POST['related']);
					$formatted= implode('+',$_POST['relatedBy']);
					
					}
			
				if($key == 'related' && is_array($value)){
					$formatted= implode('+',$_POST['related']);
					}
					
				if($key == 'searchTerm')
					$_POST['searchTerm']=trim($_POST['searchTerm']);
				if($key == 'extra' && is_array($value)){
					foreach($extra as $value2){
						$extras[]= $value2;
					
					}
					// add the UNII code to the result menu??
					}
				}

		$cacher = self::ob_cacher();
		if($cacher == TRUE) return 0;
			if($_POST['searchTerm'] || $_POST['r']  || $_POST['u']) {
			
			// look up inside of defined cache location
				if(!class_exists('rxNorm')){
					require 'APIBaseClass.php';
					require 'rxNormApi.php';
					$this->api = new rxNormApi;	
				}

				if($_POST['extra'])
				{
					switch ($_POST['extra'] ) {
						case 'UNII':
							$xml = new SimpleXMLElement($this->api->getUNII( $_POST['searchTerm'] ));
							$value_d =$xml->uniiGroup->unii;
							$id = $xml->uniiGroup->rxcui .'';
							break;
						case 'Quantity':
							$xml = new SimpleXMLElement( $this->api->getQuantity( $_POST['searchTerm'] ) );
							$value_d =$xml->quantityGroup->quantity;
							$id = $xml->quantityGroup->rxcui . '';
							break;
						case 'Strength':
							$xml = new SimpleXMLElement($this->api->getStrength( $_POST['searchTerm'] ));
							$value_d = $xml->strengthGroup->strength ;
							$id = $xml->strengthGroup->rxcui . '';
							break;

					}
					
					echo '<p class="term_result"><em>RXCUI: <i>'."$id</i><br> Matches <i>$value_d</i></em></p>";
					$xml = NULL;							

			}

				if($_POST['id_lookup']){
				
					$xml = $this->api->findRxcuiByID($_POST['id_lookup'],$_POST['searchTerm']);
					$xml = (new SimpleXMLElement($xml));
					$id = $xml->idGroup->rxnormId;
				
				}elseif(!$_POST['extra']){
					// if we have post extra than we can skip and set id properly?
					$xml = new SimpleXMLElement($this->api->findRxcuiByString($_POST['searchTerm']));
					$id = $xml->idGroup->rxnormId;
				
				}
				if($id != '' && !$_POST['extra'] && !$_POST['r'] && !$_POST['u']) {
				// sometimes we come up with nothing but the legend thing shows up anyway ....
					echo '<p class="term_result">Term "<em>'. $_POST['searchTerm'] . ($_POST['id_lookup']? " of ID type " . $_POST['id_lookup'] : NULL) . '</em>" matches RXCUI: <em>' .$id . "</em></p>\n" ;
				}
				elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u']){
					$search = new SimpleXMLElement($this->api->getSpellingSuggestions($_POST['searchTerm']));
					echo '<p class="term_result"><em>Term "'. $_POST['searchTerm'].'" not found</em></p>';
					if($search->suggestionGroup->suggestionList->suggestion){
						echo '<em>Did you mean?</em>' ;
						foreach($search->suggestionGroup->suggestionList->suggestion as $loc=>$value)
							echo "\n\t<strong class='suggestion'>$value</strong>\t\n";
						// also if cache enabled check to see if the RXCUI file already exists?
						$first = $search->suggestionGroup->suggestionList->suggestion[0] . '';
						$xml= new SimpleXMLElement($this->api->findRxcuiByString($first));
						unset($search);
						$id= $xml->idGroup->rxnormId;
						echo '<p><em>Showing first suggestion '.$first.'.</em></p>';
						// so we don't store incorrect search values as cache!
						$_POST['searchTerm'] =$first;
						unset($xml);
					}
				}elseif($_POST['u']){
				// look up by 
					$id = trim($_POST['u']);
					$xml = new SimpleXMLElement($this->api->findRxcuiByID('UMLSCUI',$id));
					$id =  $xml->idGroup->rxnormId;
					
				}elseif($_POST['r']){
					$id= trim($_POST['r']);
				}
				if($id){
				// look up all related info
				// wish i could do this so it doesnt have to load the API ...
				// perhaps make like a search lookup hash table for the XML files ..
					if(CACHE_XML){
					
					$x_token = self::cache_token();
					// to do make output 'prettier'
					// add config to allow to render from xml or html
						$put_file = XML_STORE . $x_token;
						if(!file_exists($put_file)){
							
							$xml = self::make_xml($id,$formatted);
							file_put_contents("$put_file", $xml->asXML());
							}
						else{
							
						// get file ?? pull in xml file as URL if it exisists...
							if(XML_URL_ACCESS){
									
									$xml=new SimpleXMLElement(BASE_URL.XML_STORE.$x_token,0,true);
									$this->cache = 2;
								}
							else{
									$xml=new SimpleXMLElement(file_get_contents(SERVER_ROOT.XML_STORE.$x_token));
									$this->cache = 3;
								}
						}
					}else{
					$xml = self::make_xml($id,$formatted);
					}
			
				
				
					if($formatted){
						if($_POST['relatedBy']){
							self::list_2d_xmle($xml);}
						else
							self::list_2d_xmle($xml->relatedGroup->conceptGroup);
						}
					else{
						self::list_2d_xmle($xml->allRelatedGroup->conceptGroup);
						}
					unset($xml);
				}
			}
		
			
	}
	
	function make_xml($id,$formatted=false){

	// switches between several rxnorm api function calls to make the interface more accessible.
	// add case of 'extra' .. not sure what to store .. and how to recall it properly may need additional rewriting to cache properly...
		if($formatted){
			if($_POST['relatedBy']){
				$xml= $this->api->getRelatedByRelationship("$formatted","$id");
				echo $xml;
				}
			else
				$xml = $this->api->getRelatedByType("$formatted","$id");
				}
		else
			$xml = $this->api->getAllRelatedInfo($id);
			return new SimpleXMLElement($xml);
		 }

	function xmle_table_row($key,$value,$key_css_class='property',$value_css_class='value',$first_row=NULL){
	return		($key!='tty' ? "\n\t\t\t<li class='record_".(self::$normalElements[strtoupper($key)]?self::$normalElements[strtoupper($key)]:$key)."'>".
				(self::$normalElements[strtoupper($value)]?self::$normalElements[strtoupper($value)]:$value)."</li>":NULL);
	}

	function cache_token(){
	// generates file name for cache storage
		if($_POST){
		// also check count of array to see if it is all of the checkbox, in that case use _allRelatedInfo
			foreach($_POST as $value){
				if(is_array($value)){
					foreach($value as $v2){
						$token2 [] = trim($v2);
						}
					$token2 = implode('_',$token2);
					$token []= $token2;		
					}
				else		
					$token []= str_replace(' ','',strtolower(trim($value)));
				}
				
			return (HIDE_CACHE?'.':NULL).($token2?implode('__',$token):implode('_',$token).'_allRelatedInfo');
		}
	}

	function ob_cacher($stop=NULL){
		if(PROGRESSIVE_LOAD){		
			if(CACHE_QUERY && $stop){
				$put_file = CACHE_STORE . self::cache_token();
				$cache = ob_get_flush();
				
				if(!file_exists($put_file)){
				// compressing output doesn't seem to make the files drastically smaller
					if(COMPRESS_OUTPUT) $cache =  preg_replace("/\r?\n/m", "",$cache);
						file_put_contents("$put_file", $cache);
					}
				}
			elseif(CACHE_QUERY != true){
				ob_end_flush();
				if($stop!= NULL ) ob_start();	
			}elseif($_POST && CACHE_QUERY == TRUE){
				$cache_token = CACHE_STORE.self::cache_token();
				if(file_exists($cache_token)){
					$this->cache = 1;
					echo file_get_contents($cache_token);
					// tell the rest of the object to not render!
					return true;
					}
			}
		}
	}

	function sxmle_to_obj($xml){
		$result = $xml->xpath('conceptProperties|name');
		foreach($result as $object){
			$add= new stdClass;
			foreach($object as $key=>$value){
				// have probs if it is another xmlelement although ...
				$add->$key = $value . '';
				if(is_object($add->$key))
					foreach($add->$key as $add2->$key2)
						$add->$key[$add2] = $key2;
				}
			$return []=$add;
		}
		return $return;
	}

	function list_2d_xmle($xml){
		if(is_a($xml,'SimpleXMLElement')){
		
		// this needs to be done better.... shows up if no rows are returned... should attempt to count the number of simpleXML elements ...
			echo "
	<div id='led'>			
		<ul> 			
			<li class='record_rxcui'>RXCUI</li> 
		</ul> 
		<ul> 
			<li class='record_name'>Name</li> 
		</ul> 
		<ul> 
			<li class='record_synonym'>Synonym</li> 
		</ul> 
		<ul> 
			<li class='record_umlscui'>UMLSCUI</li> 
		</ul> 
			
			</div>";
		
		
		foreach($xml as $value){
		// second row avoids displaying the parameter name for subsequent rows
		// parent name is used to determine what columns to display (rather than parse the xml object)
			$tty = $value->tty;
			$tty= (self::$normalElements[strtoupper($tty)]?self::$normalElements[strtoupper($tty)]:$tty);

			echo ($value->conceptProperties || $value->name ?"\n\t<ul>\n\t\t<li class='property'>$tty</li>\n\t</ul>":NULL);
			
			$value = self::sxmle_to_obj($value);
			if(is_array($value)){
				$result = '';
				foreach($value as $key=>$showme){
					$result .= self::show_row($showme);
					
					}
			}
			// often the RxNorm api will return duplicate rows. This hopefully ensures that two records next to eachother
			// that are identical do not get displayed
			if($result != $old_result) echo "\n\t<ul>\n<li>".$result."</li>\n</ul>\n";
			$old_result = $result;
			}	
		}
		unset($xml);
		unset($result);
		self::ob_cacher();
	}
	
	function show_row($rowData){

		foreach($rowData as $key=>$value){
			if(!in_array($key,$this->c_filter)){
				if( (SUPPRESS_EMPTY_COL && $value == '')) ;
				else{
					// adjust here for pretty URLS
					// if(PRETTY_GET_URLS)
					if($key == 'rxcui' || $key == 'umlscui') $return .= "\n\t". '<li class="record_'.$key.'">'. "<a href='?".($key=='rxcui'?'r':'u')."=$value'>" .($key=='umlscui' && $value ==''?'n/a':$value) . "</a></li>";
					else
						$return .= "\n\t". '<li class="record_'.$key.'">'. ($key=='umlscui' && $value ==''?'n/a':$value) . "</li>"; 
					}
				}
				//($key=='rxcui'?"<hr>":NULL) .
		}
		return "\n\t<ul>\n\t\t$return\n\t\t</ul>\n";
	}
	
	function stats(){
		switch ($this->cache) {
		default:
			;
			break;
		case 1:
			$cache = '<p>Rendering from cached HTML (file_get_contents)';
			break;
		case 2:
			$cache = '<p>Rendering from cached XML (as url)';
			break;
		case 3:
			$cache = '<p>Rendering from cached XML (file_get_contents)';
			break;
		}

		return $cache .
			"<em>Memory use: " . round(memory_get_usage() / 1024) . 'k'. "</em></p> <p><em>Load time : "
	. sprintf("%.4f", (((float) array_sum(explode(' ',microtime())))-$this->start_time)) . " seconds</em></p><p><em>Overhead memory : ".$this->oh_memory." k</em></p>";

	}
}
 new RxNormRef;

