<?php

class rxNormRef{
	static	$normalElements = Array(
			'TTY'=>'Term Type','IN'=>'Ingredients','PIN'=>'Precise Ingredient',
			'MIN'=>'Multiple Ingredients','DF'=>'Dose Forms','SCDC'=>'Semantic Clinical Drug Components',
			'SCDF'=>'Semantic Clinical Drug Forms','BN'=>'Brand Names','SBDC'=>'Semantic Branded Drug Forms',
			'SBDF'=>'Semantic Branded Drug Forms','SBD'=>'Semantic Branded Drug','SY'=>'Term Type',
			'TMSY'=>'Term Type','BPCK'=>'Brand Name Pack','GPCK'=>'Generic Pack');


// should load one or the other based on what the post directive is or could pass a param via construct method to
// specify that its an ndfRT call ?

	function loadRxNorm(){
		if(!class_exists('APIBaseClass')) require 'APIBaseClass.php';	
		if(!class_exists('rxNormApi')){
				require 'rxNormApi.php';
				$this->api = new rxNormApi;	
			}
	}
	
	function loadNdf(){
		if(!class_exists('APIBaseClass')) require 'APIBaseClass.php';
			if(!class_exists('ndfRTApi')){
				require 'ndfRTApi.php';
				$this->ndfApi = new ndfRTApi;
			}
	}
	
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
		if(PROGRESSIVE_LOAD){
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
	//	obcer::ob_cacher(1);
		// echo the footer and stats to screen.
		echo '<div id="stats">' . $this->stats().'</div>';

	}

	function stats(){
		switch ($this->cache) {
		default:;break;
		case 1:$cache = '<p>Rendering from cached HTML (file_get_contents)';break;
		case 2:$cache = '<p>Rendering from cached XML (as url)';break;
		case 3:$cache = '<p>Rendering from cached XML (file_get_contents)';break;
		}

		return $cache .
			"<em>Memory use: " . round(memory_get_usage() / 1024) . 'k'. "</em></p> <p><em>Load time : "
	. sprintf("%.4f", (((float) array_sum(explode(' ',microtime())))-$this->start_time)) . " seconds</em></p><p><em>Overhead memory : ".$this->oh_memory." k</em></p>";

	}
	function build_concept($value,$c_name,$c_nui){

		return '<li class="'.$value.'"><ul><li class="conceptName">'. strtolower($c_name) . '</li><li class="conceptNui">'.$c_nui. "</li>\n";
	}

	function post_check(){
		if($_POST['nui']){
				self::loadNdf();
				// default to showing all info
				// the result to cache (xml) is the result!!
					$result = new SimpleXMLElement($this->ndfApi->getAllInfo($_POST['nui']));	
					//print_r($result);
					foreach($result as $key=>$value){
						if($key=='fullConcept'){
						echo "\n<ul>";
						//	echo "\n\t<li>Full Concept</li>\n";	
							foreach($value as $key2=>$value2){	
								if($key2 == 'parentConcepts'){
									echo '<li><ul><li class="a_title">Parent Concepts</li>' . "\n";
									foreach($value2 as $key3=>$value3){
										foreach($value3 as $key4=>$value4)
										// turn this into a 'concept' function ...
											if($key4=='conceptName') $p_concept_name = $value4;
											elseif($key4=='conceptNui') $p_concept_nui = $value4;
											elseif($key4=='conceptKind')
												echo self::build_concept($value4,$p_concept_name,$p_concept_nui);
												//echo '<li class="'.$value4.'"><ul><li class="conceptName">'. $p_concept_name . '</li><li class="conceptNui">'.$p_concept_nui. "</li>\n";
										echo '</ul></li>';
									}
								}elseif($key2 == 'childConcepts'){
									//echo 
									if($value2 != '');
									foreach($value2 as $array){
										unset($temp);
										unset($result);
										foreach($array as $key5 =>$value5)
											if($key5=='conceptName') $c_concept_name = $value5;
											elseif($key5=='conceptNui') $c_concept_nui = $value5;
											elseif($key5=='conceptKind' && $value5 !='')
												//echo self::build_concept($value5,$c_concept_name,$c_concept_nui);
												$result = '<li class="'.$value5.'"><ul><li class="conceptName">'. $c_concept_name . '</li><li class="conceptNui">'.$c_concept_nui. "</li></ul>\n";
									
									}
									if($result)echo "<li class='a_title'>Child Concepts</li><li><ul>\n\n".	 $result.'</ul></li>';
									
									
								}elseif($key2 == 'groupProperties'){
									echo '<li class="a_title">Group Properties</li>';
									foreach($value2 as $item)
										foreach($item as $p_name=>$p_value)
											if($p_name == 'propertyName') $the_name = $p_value;
											elseif($p_value != '')
												echo "\n<li>\n<ul>\n<li class='$p_name'>".str_replace('_',' ',$the_name)." </li>\n<li> $p_value</li>\n</ul>\n</li>";
								}elseif($key2 == 'groupRoles'){
									echo '<li class="a_title">Group Roles</li>';
									//print_r($value2[0]);
									foreach($value2 as $roles)
										foreach($roles as $roleName=>$roles2)
											if($roleName == 'concept'){
												//echo "<li class='a_title'>Concept</li>";
												$roles_concept_name = '';
												$roles_inner_value = '';
												
												foreach($roles2 as $roles_inner_key =>$roles_inner_value)
													if($roles_inner_key == 'conceptName') $roles_concept_name = $roles_inner_value;
													elseif($roles_inner_key == 'conceptNui') $roles_nui = $roles_inner_value;
													else
														echo "<li><ul><li class='$roles_inner_value'>$master_role $roles_concept_name</li><li class='nui'>$roles_nui</li></ul></li>";
												
											}else
												$master_role = str_replace('_',' ',$roles2);
												//echo "<li class='$roleName'>$roles2</li>";
									echo '</ul>';

								}
								
							}
						}
						
					
					}
				
				}
		
	// idea why not use relatedBy and allow for lookup by id in the same form?
	// these are for array processing..
		
		
		// form objects to make ... 'ndf' is a list of the available ID types (radio names are verbose, values are what is passed into the api library)
		// also allow link for specific value types in the rxNorm api to jump to (find interactions ?)
	//	$_POST['ndf'] = 'RXCUI';
	//	$_POST['r'] = '36567';

		if(($_POST['ndf'] && ($_POST['r'] || $_POST['u'])) || $_POST['nui'] != ''){
		//print_r($_POST);
		// load ndf ??
		if($_POST['nui'] != '')$nui2 = $_POST['nui'];
		
		// di is drug interaction could be set to a single radio value to specify the type of ID to lookup, 
		// add as a get link on render pages
			
		if($_POST['ndf'])	{
			switch ($_POST['ndf']) {
				case "RXCUI":
					$byID = $_POST['r'];
					break;
				case "UMLSCUI":
					$byID = $_POST['u'];
					break;
				case "default":
					;
					break;	
			}
			
			if($byID){
				self::loadNdf();
				$nui = $this->ndfApi->findConceptsByID( $_POST['ndf'], $byID );
				$nui = new SimpleXMLElement($nui);
				foreach($nui->groupConcepts->concept as $inner)
					if($inner->conceptKind == 'DRUG_KIND' && !$nui2)
						$nui2= $inner->conceptNui;
		

			}
				// may need to make into xml element and select the id before passing it into drug interactions
				
			// need to be careful to not give it an improper value if a scope is not specified but we still need to tell it to find
			// interactions with the nui (if we have one) like a checkbox for 'di' and then a radio button for the scope ? or the default is set to 3
			// and we can be verbose with the html form
			
			// POST di is a radio value that if not selected shouldn't be sent in the post form...
			// these may all need to be ported from a GET variable, shorten names ...
			// 1 INGREDIENT_KIND
			
			// code a general 'transitive' radio button that is set to default
			
			if($nui2 && $_POST['nuiAction']){
				switch ($_POST['nuiAction']) {
					case 1:
					//getAllInfo
						$result = $this->NdfApi->getAllInfo($nui2);
						;
						break;
					case 2:
					//getChildConcepts nui,transitive
						$result = $this->NdfApi->getChildConcepts($nui2);

						;
						break;
					case 3:
					// TO DO
					//getConceptProperties
					//nui, propName
						;
						break;
					case 4:
					// getParentConcepts
					// nui, transitive
						$result = $this->NdfApi->getParentConcepts($nui2);
						break;
					case 5:
					// getRelatedConceptsByRole
					// nui, roleName (getRoleList()), transitive
						;
						break;
					case 6:
					//getRelatedConceptsByReverseRole( nui, roleName, transitive )
						;
						break;	
						
					case 7:
					//getRelatedConceptsByAssociation( nui, assocName )
						;
						break;
						
					case 8:
					//getVaClassOfConcept( nui )
						;
						break;	
						
				}
				}
			elseif($nui2 && $_POST['di']){
				// eventually allow a di value to be any other drug value ? allow searching
				// for both values in a sort of 'wizard' drug selector	
				// make a link for available urls to do 'drug interactions' on ?
				// store interacting NUI's ??
				$this->ndfApi->findDrugInteractions( $nui , $_POST['di'] );

				}
			elseif($_POST['nui']){
				self::loadNdf();
				// default to showing all info
					$result = $this->NdfApi->getAllInfo($_POST['nui']);
				}
				}
		
		}
		
		if(count($_POST) > 1)
			$a_post = array_intersect_key($_POST,array('relatedBy'=>'','related'=>'','extra'=>''));
		
		if(is_array($a_post))	
		// you probably dont need a foreach here ... could reduce it to one array value... in actuality this value is only 'extra' for now..
			foreach($a_post as $key=>$value)
				if(is_array($value))
					$formatted= implode('+',$_POST[$key]);
					

		$cached=obcer::ob_cacher();
		if($cached == TRUE) return 0;

		if(($_POST['searchTerm'] || $_POST['r'] || $_POST['u']) && !$cached ) {
			// look up inside of defined cache location
				if($_POST['id_lookup'] || $_POST['u'] ){
					$lookup = $_POST['searchTerm'];
					
					if($_POST['id_lookup']){
						$id_type = $_POST['id_lookup'];
						
					}elseif($_POST['u']){
						$id_type = 'UMLSCUI';
						$lookup = trim($_POST['u']);	
					}
					self::loadRxNorm();
					$xml = $this->api->findRxcuiByID($id_type,$lookup);
					$xml = (new SimpleXMLElement($xml));
					// INSERT INTO id_lookup values (p_token,rxcuid,umlscui,term_name) values ('obcer::cache_token()',$id,$uml,$term)
					$id = $xml->idGroup->rxnormId;
					// do we have a umlid in here??
				}elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u']){
					self::loadRxNorm();
					// if we have post extra than we can skip and set id properly?
					$xml = new SimpleXMLElement($this->api->findRxcuiByString($_POST['searchTerm']));
					$id = $xml->idGroup->rxnormId;
				}
				if($id != '' && !$_POST['extra'] && !$_POST['r'] && !$_POST['u']) 
					echo '<p class="term_result">Term "<em>'. $_POST['searchTerm'] . ($_POST['id_lookup']? " of ID type " . $_POST['id_lookup'] : NULL) . '</em>" matches RXCUI: <em>' .$id . "</em></p>\n" ;
				
				elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u']){
					//self::loadRxNorm();
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
						//unset($xml);
					}
				}elseif($_POST['r'])
					$id= trim($_POST['r']);
				
				// Now we get the actual syntax to return the real xml object
				if($id)
						$xml = self::make_xml($id,$formatted);
				// Modify output slightly to use filters if provided	
				if($formatted){
					// this is still in testing phases - for the relationship checker- returns raw object for now
					if($_POST['relatedBy'])
						self::list_2d_xmle($xml);
					else
						self::list_2d_xmle($xml->relatedGroup->conceptGroup);
					}
				else
					self::list_2d_xmle($xml->allRelatedGroup->conceptGroup);
				}
			
	}
	
	function make_xml($id,$formatted=false){
	// could check post here to see if cache_xml enabled and to return that instead of loading anything ...
	// switches between several rxnorm api function calls to make the interface more accessible.
	// add case of 'extra' .. not sure what to store .. and how to recall it properly may need additional rewriting to cache properly...
		if(CACHE_XML){
			$x_token = obcer::cache_token();
			$put_file = SERVER_ROOT . XML_STORE . $x_token;

			if(file_exists($put_file)){
			
			// get file ?? pull in xml file as URL if it exisists...
				if(XML_URL_ACCESS){
						$xml=new SimpleXMLElement(BASE_URL.XML_STORE.$x_token,0,true);
						$this->cache = 2;
					}
				else{	
						$xml=file_get_contents($put_file);
						//echo print_r($xml);
						$this->cache = 3;
					}
				}	
			else 
				unset($this->cache);
					}
		
	if($formatted && !$this->cache){
			self::loadRxNorm();
			$xml = ($_POST['relatedBy']?$this->api->getRelatedByRelationship("$formatted","$id"):$this->api->getRelatedByType("$formatted","$id"));
			}
		elseif(!$this->cache){
		
			self::loadRxNorm();
			$xml = $this->api->getAllRelatedInfo($id);
			}
		
		// messy but allows us to quickly enable url accessors (for whatever reason..)
		// could allow for remote caching!
			$return = (XML_URL_ACCESS && CACHE_XML?$xml:new SimpleXMLElement($xml));
			
			if(CACHE_XML && !$this->cache) file_put_contents("$put_file", $return->asXML());
			return $return;
		 }

	function xmle_table_row($key,$value,$key_css_class='property',$value_css_class='value',$first_row=NULL){
	return		($key!='tty' ? "\n\t\t\t<li class='record_".(self::$normalElements[strtoupper($key)]?self::$normalElements[strtoupper($key)]:$key)."'>".
				(self::$normalElements[strtoupper($value)]?self::$normalElements[strtoupper($value)]:$value)."</li>":NULL);
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
		//obcer::ob_cacher();
	}
	
	function show_row($rowData){

		foreach($rowData as $key=>$value){
			if(!in_array($key,$this->c_filter)){
				if( (SUPPRESS_EMPTY_COL && $value == '')) ;
				else{
					// adjust here for pretty URLS
					// if(PRETTY_GET_URLS)
					// also adjust for key on dose form - prevent does form rows from having rxcui/umlscui links
					if($key == 'rxcui' || $key == 'umlscui') $return .= "\n\t". '<li class="record_'.$key.'">'. "<a href='?".($key=='rxcui'?'r':'u')."=$value'>" .($key=='umlscui' && $value ==''?'n/a':$value) . "</a></li>";
					else
						$return .= "\n\t". '<li class="record_'.$key.'">'. ($key=='umlscui' && $value ==''?'n/a':$value) . "</li>"; 
					}
				}
		}
		return "\n\t<ul>\n\t\t$return\n\t\t</ul>\n";
	}
	

}
 new RxNormRef;

