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
		$this->cache_token = obcer::cache_token((COUCH?'db':NULL));
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

		// echo the footer and stats to screen.
		echo '<div id="stats">' . $this->stats().'</div>';

	}

	function stats(){
		switch ($this->cache) {
		default:;break;
		case 1:$cache = '<p>Rendering from cached HTML (file_get_contents)</p>';break;
		case 2:$cache = '<p>Rendering from cached XML (as url)</p>';break;
		case 3:$cache = '<p>Rendering from cached XML (file_get_contents)</p>';break;
		case 4:$cache = '<p>Rendering from Couch Database</p>'; break;
		}

		return $cache .
			"<em>Memory use: " . round(memory_get_usage() / 1024) . 'k'. "</em> <p><em>Load time : "
	. sprintf("%.4f", (((float) array_sum(explode(' ',microtime())))-$this->start_time)) . " seconds</em></p><p><em>Overhead memory : ".$this->oh_memory." k</em></p>";

	}
	function build_concept($value,$c_name,$c_nui,$c_kind=NULL){
	// get method args nad do a str replace for ampersands?
	
		return '<li class="'.htmlentities($value).'"><ul><li class="conceptName">'. htmlentities(ucwords(strtolower($c_name))) . '</li><li class="nui"><a href="?n='.$c_nui. '">'. $c_nui. "</a></li></ul></li>\n";
	}
	
	function r_check($xml,$reverse=NULL){
	// simply returns either simple xml element or json_decode object based on RENDER_MODE, mode can be overridden
	// if provided will give the reverse of whatever the render mode is	
		return ($reverse == NULL?(RENDER_MODE =='xml'?new SimpleXMLElement($xml):json_decode($xml)) : (RENDER_MODE == 'json'?new SimpleXMLElement($xml):json_decode($xml)));
	}

	function post_check(){
	
		if($_POST['drugs'] == 'on' && $_POST['searchTerm']){
				self::loadRxNorm();
				$xml = $this->api->getDrugs($_POST['searchTerm']);
				//unset($formatted);
				
				$xml = self::r_check();
// assumes user enters the correct spelling :(
				// may need to return something else for json return ...
				return self::list_2d_xmle($xml->drugGroup->conceptGroup);
		}

		if($_POST['nui']){
				
		
				// default to showing all info
				// the result to cache (xml) is the result!!
				// do a obcacher check to see if we cant get the XML back and avoid making the ndfApi call
				// write something to rewrite the xml files in a better (smaller) format, or store in database - or both.
				// ideally tab/space delimited would probably be best

					if(COUCH){
					// how do we set output of ndfApi ?? without the method ?? force it to by modifying the source? that wont work.. will have to update the library
						$result = self::couchCheck();
						
						if($result != false) {
							$this->cache = 4;
						}else{
						 unset($this->cache);
						// put the couch ??
						}
					}

					if(!$this->cache || $_POST['s']){
					
						self::loadNdf();
					// we could also just decode a refined object to store in a 'better/smaller' format??
					// run curl in background??
						if(COUCH || RENDER_MODE == 'json') $this->ndfApi->setOutputType('json');
						// this processing is for when people want to search by name or they click a link to search the term in RxNorm (COMING SOON!!)
						if($_POST['findConcepts'] != 'on'){
							$result = $this->ndfApi->findConceptsByName($_POST['nui'],'DRUG_KIND');
							
							if(RENDER_MODE == 'json')
								$result = json_decode($result);
							else
								$result = new SimpleXMLElement($result);
							
							// switch result output based on xml or json...
							if(is_a($result,'SimpleXMLElement'))
								if(!$result->xpath('groupConcepts/concept') )
									{ echo"<p class='term_result'>Term <em>" .$_POST['nui'] . "</em> did not return any matching concepts. Please check your spelling.</p>";
										unset($result);
									}
							elseif(!$result->groupConcepts[0]->concept[0])
									echo"<p class='term_result'>Term <em>" .$_POST['nui'] . "</em> did not return any matching concepts. Please check your spelling.</p>";
									
							
							if(!COUCH && !$this->cache && $result){
								//$result = new SimpleXMLElement($result);
								$return .= '<ul>';
								
								if(is_a($result,'SimpleXMLElement')){
								
									foreach($result->xpath('groupConcepts/concept') as $ic)
										$return .=  self::build_concept($ic->conceptKind,str_replace('_',' ',$ic->conceptKind) .' ' . $ic->conceptName,$ic->conceptNui)  ;	
								
								}else{
									foreach($result->groupConcepts[0]->concept as $ic)
										$return .=  self::build_concept($ic->conceptKind,str_replace('_',' ',$ic->conceptKind) .' ' . $ic->conceptName,$ic->conceptNui)  ;
								
								}
								$return .= '</ul>';
								// do json processing...
								
							echo $return;
							// exit the logical flow... 
							return true;
							}

						}else{
						// switches json and xml...
							if(RENDER_MODE == 'json')
								$this->ndfApi->setOutputType('json');
						
							
								$result = $this->ndfApi->getAllInfo($_POST['nui']);
							
							if(RENDER_MODE =='xml'){
								
								$result = new SimpleXMLElement($result);
								}
							elseif(!is_object($result) && RENDER_MODE == 'json'){
								
								
								$result = json_decode($result);
								
								}
						}						
						if($result) {
							if(COUCH) self::put_couch($result);
							if(!is_object($result)){
								
								if('RENDER_MODE' == 'json')
									$result = json_decode($result);
								else{
									$result = new SimpleXMLElement($result);
									}
								}
						}
					}
				
					// store this if it is a simple xml element ??
					// to do reverse order of this stuff....
				if($result){
				//print_r($result);
					foreach($result as $key=>$value){
						if($key=='fullConcept'){
							foreach($value as $key2=>$value2){	
								if($key2 == 'parentConcepts'){
									echo '<ul><li class="a_title">Parent Concepts</li>' . "\n";
									foreach($value2 as $key3=>$value3){
										if(is_a($value3,'stdClass'))
											$value3 = $value3->concept[0];
										foreach($value3 as $key4=>$value4)
										// turn this into a 'concept' function ...
											if($key4=='conceptName') $p_concept_name = $value4;
											elseif($key4=='conceptNui') $p_concept_nui = $value4;
											elseif($key4=='conceptKind')
												self::build_concept($value4,$p_concept_name,$p_concept_nui);
												echo '<li class="'.$value4.'"><ul><li class="conceptName">'. ucwords(strtolower($p_concept_name)) . '</li><li class="nui"><a href="?n='.$p_concept_nui.'">'.$p_concept_nui. "</a></li>\n";
										echo '</ul></li>';
									}
								}elseif($key2 == 'childConcepts'){
										unset($result);
									if($value2 != '');

									if(is_a($value2[0],'stdClass')){
										$value2 = $value2[0]->concept;
									}
									foreach($value2 as $array){
										unset($temp);
										foreach($array as $key5 =>$value5)
											if($key5=='conceptName') $c_concept_name = $value5;
											elseif($key5=='conceptNui') $c_concept_nui = $value5;
											elseif($key5=='conceptKind' && $value5 !='')
												$result.= self::build_concept($value5,$c_concept_name,$c_concept_nui). "\n";
									
									}
									if($result)echo "<li class='a_title'>Child Concepts</li>\n\n".	 $result ;
									
								}elseif($key2 == 'groupProperties'){
									unset($result);
									
									if(is_a($value2[0],'stdClass')){
										$value2 = $value2[0]->property;
									}
									unset($names);
										unset($vandf);
									foreach($value2 as $item)
									{
										// often names are identical.. do a check and combine the fields for the names and render it its own element
										
										
										foreach($item as $p_name=>$p_value){
											unset($link);
											if($p_name == 'propertyName') $the_name = $p_value;
											elseif($p_value != ''){
											// these links need to be done better... all my paths need to be done better...
											// group 'MESH' attributes
												if($the_name=='RxNorm_CUI' || $the_name =='UMLS_CUI') $link = "../public/?".($the_name=='RxNorm_CUI'?'r':'u')."=$p_value";
											// add extra names here	
												elseif(in_array($the_name,array('Display_Name','RxNorm_Name'))){
														if($names){
															$p_value = strtolower($p_value);
															$key_check = array_search ( $p_value , $names);
															
															// remove _name from  the key each element except the last one
															if($key_check){
																	
																// value exists append the key check value to the key with a comma
																	$names["$the_name,$key_check"]=$p_value;
																
																}
															}else{
															$names["$the_name"] = strtolower("$p_value");
														}
													}
													// missing a few vandf settings that may be xml add as encountered...
													elseif(in_array($the_name,array('VANDF_Record','VANDF_Record'))){
													// do xml processing
														$p_value = str_replace(array("<$the_name>",'<VA_File>','<VA_IEN>',"</$the_name>",'</VA_IEN>','</VA_File>'),
																			   array('<em>','<em><strong>VA File</strong> ','<em><strong>VA IEN</strong> ','</em><br/>','</em><br/>','</em><br/>','</em>'),"$p_value");
														$vandf["$the_name"] = $p_value;
														
													}
												
												elseif($the_name != 'FDA_UNII'){
													$p_value = str_replace('_',' ',ucwords(strtolower($p_value)));
												}
												//do check if it is a vandf field and to then first process it as xml to display it properly (sans cryptic tags) or use string replace function ?
												$result .= "\n<li>\n<ul class='gProperty'>\n<li class='group_t'>".str_replace('_',' ',$the_name)." </li>\n<li class='gValue'>".($link?"<a href='$link'> $p_value</a>":$p_value)."</li>\n</ul>\n</li>";
												}
									}
												
										}
									echo '<li class="a_title">Group Properties</li>' . $result;	
									// this is where we go very wrong ...??
									
								}elseif($key2 == 'groupRoles'){
								
								// rewrite this completely to be like everything else ... please...
									//
									if(RENDER_MODE=='json'){
									// hummm...
									// can only process a single  group role value for now :( due to bug in json structure
									// still stores the whole record in the db or xml/json cache
										$value2= $value2[0]->role[0];
									}
									unset($result);
									
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
														$result .= "\n<li>\n<ul>\n<li class='$roles_inner_value'>$master_role $roles_concept_name</li>\n<li class='nui'><a href='?n=$roles_nui'>$roles_nui</a></li>\n</ul>\n</li>\n";
												
											}else
												$master_role = str_replace('_',' ',$roles2);
												//echo "<li class='$roleName'>$roles2</li>";
									
									if($result)
										echo '<li class="a_title">Group Roles</li>' . $result. '</ul>';

								}
								
							}
						}
						
					
					}
					}
				
				if($_POST['drug_inter'] != 'on' && $result){	
				// weird in a loop that this wont show if render mode is json?
				//default it to show drug interactions for now...
					self::loadNdf();
					
					if(RENDER_MODE=='json'){
				
						$this->ndfApi->setOutputType('json');
					
						//$this->ndfApi->setOutputType='json';
						
						
					//	die(print_r($drug_inter));
						}
						
					$drug_inter = $this->ndfApi->findDrugInteractions($_POST['nui'],3);
					
					
					if(RENDER_MODE=='json'){
						$drug_inter = json_decode($drug_inter);
					}
					else{
						
					
						$drug_inter = new SimpleXMLElement($drug_inter);
						
					}
					// should return encode json object etc...

					if(is_a($drug_inter,'SimpleXMLElement')){
					
						$drug_inter = $drug_inter->xpath('groupInteractions/interactions');
					}else{
					
						$drug_inter = $drug_inter->groupInteractions->interactions;
					//	die(print_r($drug_inter));
					}
					
					
					
					if($drug_inter[0]){ 
							$drug_inter = $drug_inter[0];
						
						
					echo '
<ul>
<li class="a_title">Drug Interactions</li>
<li class="d_int_comment"><ul><li>'.$drug_inter->comment."</li></ul></li>
";					if(RENDER_MODE == 'xml'){
					//	function build_concept($value,$c_name,$c_nui,$c_kind=NULL){
					if($drug_inter->conceptName != '')
						echo self::build_concept('interaction',$drug_inter->conceptName,$drug_inter->conceptNui,$drug_inter->conceptKind);
					
					
						foreach($drug_inter->groupInteractingDrugs->interactingDrug as $u){
							if($u->concept->conceptName != '' || $u->concept->conceptName)
							echo self::build_concept('interacting_drug',$u->concept->conceptName . ' (' . $u->severity . ')',$u->concept->conceptNui,$u->concept->conceptKind);
						}
						
					
					}else{
						// json decode moves the object around a wee bit..
						//print_r($drug_inter->groupInteractingDrugs[0]->interactingDrug);
						foreach($drug_inter->groupInteractingDrugs[0]->interactingDrug as $u){
							if($u->concept[0]->conceptName != '')
								echo self::build_concept('interacting_drug',$u->concept[0]->conceptName . ' (' . $u->severity . ')',$u->concept[0]->conceptNui,$u->concept[0]->conceptKind);
						}
					
					
					}
					
					echo "</li></ul>";
					}
					
					else{
					
						
					
				//	echo 'hi';
						// report that NUI doesn't have any reported interactions at this time...
							unset($drug_inter);
							
					}
					//die(print_r($drug_inter));
				// could just add this to the result and store that instead of making more records ?
				}	
				
				}	
		if(($_POST['ndf'] && ($_POST['r'] || $_POST['u'])) || $_POST['nui'] != ''){

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
					// getRelatedConceptsByRole nui, roleName (getRoleList()), transitive
					case 5:;break;
					//getRelatedConceptsByReverseRole( nui, roleName, transitive )
					case 6:;break;	
					//getRelatedConceptsByAssociation( nui, assocName )	
					case 7:;break;
						//getVaClassOfConcept( nui )
	
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
		
		// chacing isn't being done properly here for the basic searches .. we dont get in a couch check anywheres...
		if($cached == TRUE) return 0;
		
		

		if(($_POST['searchTerm'] || $_POST['r'] || $_POST['u']) && !$cached) {
		
			// look up inside of defined cache location
				if($_POST['id_lookup'] || $_POST['u']  || $_POST['r']){
						// add other types to support more lookup types through get variables/post
					if($_POST['u']){
						$id_type = 'UMLSCUI';
						$lookup = trim($_POST['u']);	
					}elseif($_POST['r']){
					
						$id_type = 'RXCUI';
						$lookup = $_POST['r'];
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
				if($id != '' && !$_POST['extra'] && !$_POST['r'] && !$_POST['u']) {
					echo '<p class="term_result">Term "<em>'. $_POST['searchTerm'] . ($_POST['id_lookup']? " of ID type " . $_POST['id_lookup'] : NULL) . '</em>" matches RXCUI: <em>' .$id . "</em></p>\n" ;
					self::loadRxNorm();
				}
				elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u'] ){
					//self::loadRxNorm();
					
					echo '<p class="term_result"><strong>Term'. ($_POST['id_lookup']?' of ' .$_POST['id_lookup'].' ' :' '). $_POST['searchTerm'].' not found</strong></p>';
					
					$search = (!$_POST['id_lookup']? new SimpleXMLElement($this->api->getSpellingSuggestions($_POST['searchTerm'])) : NULL);
					
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
				}elseif($_POST['r']){
				
					$id= trim($_POST['r']);
						
					}
				// Now we get the actual syntax to return the real xml object
				if($id){
				
						$xml = self::make_xml($id,$formatted);
						}
				// Modify output slightly to use filters if provided	
				if($formatted && !$_POST['drugs']){
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
	
	
	function couchCheck($json_array=false){
	
		if(COUCH && $_POST){
		// check if it already exists first ...
			$couch_token =$this->cache_token;
			$exec_line = "curl -X GET " . COUCH_HOST . "/" . COUCH_DB . "/$couch_token";
			$tester = exec($exec_line);
			if($tester =='{"error":"not_found","reason":"missing"}'){
				self::loadRxNorm();
				unset($this->cache);
				return false;
				}
			else{
				$this->cache =4;
				return json_decode($tester,$json_array);
			}
		}
	
	}

	function put_couch($xml){
		$exec_line = "curl -X PUT '" . COUCH_HOST . '/' . COUCH_DB . '/'.$this->cache_token ."' -d \ '" . $xml ."'" ;
		exec($exec_line);
		return  json_decode($xml);

	}
	function make_xml($id,$formatted=false){
	
		if(COUCH && !$this->cache){
			$xml = self::couchCheck();
			$this->api->setOutputType('json');
			}
			
		if(CACHE_XML){
			$x_token = $this->cache_token;
			$put_file = SERVER_ROOT . XML_STORE . "$x_token";
			if(file_exists($put_file)){
			// get file ?? pull in xml file as URL if it exisists...
				if(XML_URL_ACCESS){
						$xml=new SimpleXMLElement(BASE_URL.XML_STORE.$x_token,0,true);
						$this->cache = 2;
					}
				else{	
						$xml=file_get_contents($put_file);
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
	
	
	if(COUCH && !$this->cache)$xml = self::put_couch($xml);

	$return = ((XML_URL_ACCESS && CACHE_XML) || COUCH ?$xml:new SimpleXMLElement($xml));
			
	if(CACHE_XML && !$this->cache && !COUCH) file_put_contents("$put_file", $return->asXML());		
			
	return $return;
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

		foreach($xml as $value){
		// second row avoids displaying the parameter name for subsequent rows
		// parent name is used to determine what columns to display (rather than parse the xml object)
			$tty2 = $value->tty;
			
			$tty= (self::$normalElements[strtoupper($tty2)]?self::$normalElements[strtoupper($tty2)]:$tty2);

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
			if($result != $old_result) echo "\n\t<ul>\n<li>". $result. "</li>\n</ul>\n";
			$old_result = $result;
			}	
		}else{
	// messy but works for now ... need to rework this 
			foreach($xml as $key=>$json){
				if(is_object($json) || is_array($json)){
				$result = '';
					foreach($json as $key2=>$showme2){
						foreach($showme2 as $process)
					 		$result .= self::show_row($process);
					}
				}

				echo ($json->conceptProperties || $json->name ?"\n\t<ul>\n\t\t<li class='property'>".  (self::$normalElements[strtoupper($json->tty)]?self::$normalElements[strtoupper($json->tty)]:$json->tty)  ."</li>\n\t</ul>":NULL);
				if($result != $old_result) echo "\n\t<ul>\n<li>".$result."</li>\n</ul>\n";
				$old_result = $result;	
			}
		}
	}

	function show_row($rowData){
	// should combine this into the other existing row processor...
		foreach($rowData as $key=>$value){
			//echo $rowData->tty;
			if($rowData->tty == 'DF') $disable_link = true;
			else $disable_link= false;
			if(!in_array($key,$this->c_filter)){
				if( (SUPPRESS_EMPTY_COL && $value == '')) ;
				else{
					$value = htmlentities($value);
				
					// adjust here for pretty URLS
					// if(PRETTY_GET_URLS)
					// also adjust for key on dose form - prevent does form rows from having rxcui/umlscui links
					if(($key == 'rxcui' || $key == 'umlscui') && !$disable_link) $return .= "\n\t". '<li class="record_'.$key.'">'. "<a href='?".($key=='rxcui'?'r':'u')."=$value'>" .($key=='umlscui' && $value =='' && !$disable_link?'n/a':$value) . "</a></li>";
					
					else{
						if($rowData->tty == 'IN'){
							$return .= "\n\t". '<li class="record_'.$key.'"><a href="ndf.php?s='. $value.'">'.$value.'</a></li>'; 
						}else{
					
							$return .= "\n\t". '<li class="record_'.$key.'">'. ($rowData->tty=='IN'?'<a href="ndf.php?s='. $result.'">' :NULL) .($key=='umlscui' && $value ==''?'n/a':$value).($rowData->tty=='IN'?'</a>':NULL) . "</li>"; 
						}
						}
					}
				}
		}
		return "\n\t<ul>\n\t\t$return\n\t\t</ul>\n";
	}
}
 new RxNormRef;
 