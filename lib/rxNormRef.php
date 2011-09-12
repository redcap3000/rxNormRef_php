<?php
class rxNormRef{
	static	$normalElements = Array(
			'TTY'=>'Term Type','IN'=>'Ingredients','PIN'=>'Precise Ingredient',
			'MIN'=>'Multiple Ingredients','DF'=>'Dose Forms','SCDC'=>'Semantic Clinical Drug Components',
			'SCDF'=>'Semantic Clinical Drug Forms','BN'=>'Brand Names','SBDC'=>'Semantic Branded Drug Forms',
			'SBDF'=>'Semantic Branded Drug Forms','SBD'=>'Semantic Branded Drug','SY'=>'Term Type',
			'TMSY'=>'Term Type','BPCK'=>'Brand Name Pack','GPCK'=>'Generic Pack');
	function loadRxNorm(){
		if(!class_exists('APIBaseClass')) 
			require 'APIBaseClass.php';	
		if(!class_exists('rxNormApi')){
			require 'rxNormApi.php';
			$this->api = new rxNormApi;	
			$this->api->setOutputType('json');	
		}
	}
	function loadNdf(){
		if(!class_exists('APIBaseClass')) require 'APIBaseClass.php';
			if(!class_exists('ndfRTApi')){
				require 'ndfRTApi.php';
				$this->ndfApi = new ndfRTApi;
				$this->ndfApi->setOutputType('json');
			}
	}
	
	function loadRxTerms(){
		if(!class_exists('APIBaseClass')) require 'APIBaseClass.php';
			if(!class_exists('rxTermsApi')){
				require 'rxTermsApi.php';
				$this->rxTermsApi = new rxTermsApi(null,'json');
				$this->rxTermsVersion = $this->rxTermsApi->getRxTermsVersion();
				
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
		return ($this->cache?'<p>Rendering from Couch Database</p>':NULL) .
			"<em>Memory use: " . round(memory_get_usage() / 1024) . 'k'. "</em> <p><em>Load time : "
	. sprintf("%.4f", (((float) array_sum(explode(' ',microtime())))-$this->start_time)) . " seconds</em></p><p><em>Overhead memory : ".$this->oh_memory." k</em></p>";

	}
	function build_concept($value,$c_name,$c_nui,$c_kind=NULL){
		return '<li class="'.htmlentities($value).'"><ul><li class="conceptName">'. '<a href="?n='.$c_nui. '">' . htmlentities(ucwords(strtolower($c_name))) . "</a></li></ul></li>\n";
	}
	function echoProp($prop,$key=false){
		foreach($prop as $k=>$x){
			if($title != false)
				$result.= "\n<ul><li>$k</li></ul>\n";
			
			if(is_array($x)){
				$x = array_unique($x);

				foreach($x as $here)
					$result .= "\n\t<ul>\n\t\t" . $here . "\n\t\t\n\t</ul>\n";
				}
			else
				$result .= "\n\t<ul>\n\t\t" . $x . "\n\t\t\n\t</ul>\n";
			}
			return $result;
	}
	function procResult($result){
	// this conversion is for compat. reasons with older versions of php, otherwise running json_decode($xmlstring,true) will return all assoc. arrays (this is bullshit)
	// do per item object oriented processing
		if($result->data){
			// parent concepts
			if(is_array($result->data->parentConcepts)){
				foreach($result->data->parentConcepts as $pc_key=>$pc_value)
					$theRow ['parentConcepts'] []= $this->build_concept('pname', 'Parent Name:'.str_replace(array("/",','),array(' / ',', ') ,trim($pc_value->conceptName)),$pc_value->conceptNui,$pc_value->conceptKind);
			}elseif(is_object($result->data->parentConcepts)){
				$theRow ['parentConcepts'] = $this->build_concept('pname', 'Parent Concept '.str_replace(array("/",','),array(' / ',', ') ,trim($result->data->parentConcepts->conceptName)),$result->data->parentConcepts->conceptNui,$result->data->parentConcepts->conceptKind);
			}
			// child concepts
			if(is_array($result->data->childConcepts))
				foreach($result->data->childConcepts as $pc_key=>$pc_value)
					$theRow ['childConcepts'] []= $this->build_concept('cname', str_replace(array("/",','),array(' / ',', ') ,trim($pc_value->conceptName)),$pc_value->conceptNui,$pc_value->conceptKind);
			elseif(is_object($result->data->childConcepts))
				$theRow ['childConcepts'] = $this->build_concept('cname', str_replace(array("/",','),array(' / ',', ') ,trim($result->data->childConcepts->conceptName)),$result->data->childConcepts->conceptNui,$result->data->childConcepts->conceptKind);
			if($result->data->groupRoles){
				foreach($result->data->groupRoles as $gr_key=>$gr_value){
					if($gr_key == 'has_Ingredient' || $gr_key == 'has_PE'|| $gr_key == 'has_MoA'){
					// these need wording changes .. add other keys as encountered
						$gr_key = 'has';
					}
					$theRow ['groupRoles'] []= $this->build_concept('gRole', str_replace(array("/",',','_','KIND'),array(' / ',', ',' ',' ') ,$gr_key . ' ' .$gr_value->conceptKind . ' : ' . trim($gr_value->conceptName)),$gr_value->conceptNui,$gr_value->conceptKind);
				}
		
			}
			if($result->data->groupProperties){
				
				foreach($result->data->groupProperties as $gp_key=>$gp_value){
					if ($gp_key == 'Display_Name' || $gp_key == 'label' || $gp_key == 'kind'){
							if($gp_key == 'label'){
							
								// could do this better maybe with multiple string functions..
								$gp_value = explode('[',str_replace('/',' / ',$gp_value));
								
								$group_property_name = $gp_value[0];
							}elseif($gp_key == 'kind' && $group_property_name){
								$group_property_name .= '<br/> ( ' . str_replace(array('_','KIND'),array(' ',''),$gp_value) . ' )';
							}
						}
						elseif(($gp_key == 'Synonym' && $gp_value == $group_property_name) || ($gp_key == 'label' && $gp_value == $group_property_name) || ($gp_key == 'MeSH_Name' && $gp_value == $group_property_name) || ($gp_key == 'RxNorm_Name' && strtoupper($gp_value) == $group_property_name)  || ($gp_key == 'NUI' || $gp_key == 'kind') || $gp_key == 'VANDF_Record'){
						// if they synonym is equal to the property name don't show it...
							;
						}
						elseif($gp_key== 'Level'){
							$group_level = $gp_value;
						}elseif($gp_key== 'Status'){
							$group_status = $gp_value;
						}elseif($gp_key == 'kind'){
							$kind = $gp_value;
						}
						elseif($gp_key == 'MeSH_Definition'){
							$mesh_def = $gp_value;
						}elseif($gp_key== 'Synonym'){
							if(!$sym)
								$sym = $gp_value;
							else{
								$sym .= ", $gp_value";
								}
						}
						else{
					//	$inV = ucwords(str_replace(array("/",','),array(' / ',', '),strtolower(trim($inV))));
					//	$theRow ['groupProperties'][]= "\t\t<li class='gProperty'><strong>" . str_replace('_',' ',$inK) . '</strong> ' . (!in_array($inK,array('RxNorm_CUI','NUI','UMLS_CUI','code','MeSH_CUI','MeSH_DUI','FDA_UNII'))?strtolower($inV):$inV) . '</li>';
						}
				
				}

		}
			// wanted to show group properties first ...
			if($group_property_name){
				echo "<ul><li class='groupPropName'><h2>".ucwords(strtolower($group_property_name)).  ($group_level?" : $group_level</h2>" : '</h2>') .'</li>'. ($group_status?"<h3>$group_status</h3>":NULL). ($mesh_def?"<p>$mesh_def</p>":NULL)  . ($sym?"<br/><strong>Synonyms: </strong>$sym":NULL) . '</li></ul>'  ;
			//	if($theRow['groupProperties']){
				
			//		echo self::echoProp($theRow['groupProperties']);
			//		unset($theRow['groupProperties']);
			//	}
			//	echo '<ul><li class="groupPropName"><h3>Related Concepts</h3></li></ul>';
				echo self::echoProp($theRow);
			}elseif(!$scd){
				echo '<ul><li class="groupPropName"><h2>No Record</h2><p>A record could not be found for the corresponding NUI, please check back later.</h2></li></ul>';
			}	
		}	
	}
	function post_check(){
		if($_POST['nui']){
					if(COUCH){
						$result = self::couchCheck();
						if($result != false) {
							$this->cache = 4;
							$result = json_decode($result);
							$this->nui = $result->nui;
							$this->kind = $result->kind;
							
							self::procResult($result);
							// spit it out to screen ????
						}
					}
					if(!$this->cache || $_POST['s']){
						self::loadNdf();
					// run curl in background??	
						// this processing is for when people want to search by name or they click a link to search the term in RxNorm (COMING SOON!!)
						if($_POST['findConcepts'] != 'on'){
						// change this around ?
							$result = $this->ndfApi->findConceptsByName($_POST['nui'],'DRUG_KIND');
							$result = json_decode($result);
							$result_count = $result->groupConcepts[0]->concept;
								// figure out result count ...

							// switch result output based on xml or json...
							if($result_count == 0)
								{ echo"<p class='term_result'>Term <em>" .$_POST['nui'] . "</em> did not return any matching concepts. Please check your spelling.</p>";
									unset($result);
									}

							if(!$this->cache && $result){
								$return .= '<ul>';
								foreach($result->groupConcepts[0]->concept as $ic)
									$return .=  self::build_concept($ic->conceptKind,str_replace('_',' ',$ic->conceptKind) .' ' . $ic->conceptName,$ic->conceptNui)  ;
								$return .= '</ul>';
								
								echo $return;
								// exit the logical flow... 
								return true;
							}
						}
						elseif(!$this->cache){
							self::loadNdf();
							$result = $this->ndfApi->getAllInfo($_POST['nui']);
						if(!is_object($result)){
						// does this friggin work ?!
									$result = json_decode($result,true);
									echo self::procResult($result);
								}
						}	

						if($result && COUCH) $result = self::put_couch($result);

				}
				// is this needed for ndf ?
				if($result && !$_POST['nui']){
				// renders full output without making any mods .... 
				// doesn't store to couch db for some odd reason ?
				// will need to rewrite this
				echo '<ul>';
					foreach($result as $key=>$value){
						if($key=='fullConcept'){
							foreach($value as $key2=>$value2){	
								if($key2 == 'parentConcepts' && $value2[0] != ''){
									echo '<li class="a_title">Parent Concepts</li>' . "\n";
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
									
									if(is_a($value2[0],'stdClass'))
										$value2 = $value2[0]->property;
									
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
										$valueT = $value2[0]->role;
										unset($result);
										// weird json error... had to hack it to work forloops not liking the above value
										foreach($valueT as $role){
											$result .="\n<li>\n<ul>\n<li class='".$role->concept[0]->conceptName."'>".str_replace('_',' ',$role->roleName). ' '. $role->concept[0]->conceptName."</li>\n<li class='nui'><a href='?n=".$role->concept[0]->conceptNui."'>".$role->concept[0]->conceptNui."</a></li>\n</ul>\n</li>\n";
											}

									if($result)
										echo '<li class="a_title">Group Roles</li>' . $result. '</ul>';
								}
								
							}
						}
					}
					}
				// loads post 'drug interactions' specfically from NDFrt but I think it may link itself to the rxnorm set..
				// this stuff needs to be cached, not all terms have drug interactions so it might be good to limit this lookup to 'DRUG_KIND''s
				if($_POST['drug_inter'] != 'on' && $result && $this->kind == 'DRUG_KIND'){	
					// check for drug interactions, store in database as another record append di to the cache token
					$drug_inter = self::couchCheck('di');
					// obviously if couch is disabled this below will run (not supported for xml/json file caching just yet)
					if($drug_inter == false){
						self::loadNdf();
						// set the kind and check if kind is of 'DRUG_KIND"
						$drug_inter = $this->ndfApi->findDrugInteractions($this->nui,3);
						if(COUCH){
						// uh oh how to retreve record if it exists ??
							self::put_couch($drug_inter);
						}
						
						$drug_inter = json_decode($drug_inter);
						$drug_inter = $drug_inter->groupInteractions->interactions;
						
					}else{
					// if we want to do file caching need to change couchCheck to
					// drug interaction response could use rewriting groupInteractingDrugs->interactingDrug->(interactiongDrug[0]->concept
						$drug_inter = json_decode($drug_inter);
						$drug_inter = $drug_inter->data->groupInteractions->interactions;

					}

					if($drug_inter[0]){ 
							$drug_inter = $drug_inter[0];
						echo '
						<ul>
						<li class="a_title">Drug Interactions</li>
						<li class="d_int_comment"><ul><li>'.$drug_inter->comment."</li></ul></li>";
						
							// json decode moves the object around a wee bit..
							foreach($drug_inter->groupInteractingDrugs[0]->interactingDrug as $u){
								if($u->concept[0]->conceptName != '')
									echo self::build_concept('interacting_drug',$u->concept[0]->conceptName . ' (' . $u->severity . ')',$u->concept[0]->conceptNui,$u->concept[0]->conceptKind);
							}

					echo "</ul>";
					}
					
					else{
				//	echo 'hi';
						// report that NUI doesn't have any reported interactions at this time...
							unset($drug_inter);
							
					}
				// could just add this to the result and store that instead of making more records ?
				}	
				
				}	
		
		// some simple array processing for the post variables when arrays are present


		$cached=obcer::ob_cacher();
		// chacing isn't being done properly here for the basic searches .. we dont get in a couch check anywheres...
		if($cached == TRUE) return 0;
		// if we have a search term, or someone wants to look up via rxcuid or umscui (rxNorm)
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
					
						// do couch processing here ...
					$xml = $this->api->findRxcuiByID($id_type,$lookup);
					$xml = json_decode($xml);
					// may have multiples :/
					$id = $xml->idGroup->rxnormId[0];
			
				// finds the ID for the record to render (rxNorm)
				}elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u']){
					self::loadRxNorm();
					// if we have post extra than we can skip and set id properly?
					// this doesn't support json does it ??
					$xml = json_decode($this->api->findRxcuiByString($_POST['searchTerm']));
					// find some way to cache this search query if (COUCH) modify searchTerm storage to add field to check ?
					$id = $xml->idGroup->rxnormId[0];
				}
				// if term matches a search term
				if($id != '' && !$_POST['extra'] && !$_POST['r'] && !$_POST['u']) {
					echo '<p class="term_result">Term "<em>'. $_POST['searchTerm'] . ($_POST['id_lookup']? " of ID type " . $_POST['id_lookup'] : NULL) . '</em>" matches RXCUI: <em>' .$id . "</em></p>\n" ;
					self::loadRxNorm();

				}
				// suggestion loop
				elseif(!$_POST['extra'] && !$_POST['r'] && !$_POST['u'] ){
					echo '<p class="term_result"><strong>Term'. ($_POST['id_lookup']?' of ' .$_POST['id_lookup'].' ' :' '). $_POST['searchTerm'].' not found</strong></p>';

					$this->api->setoutputType('json');
					// cache search result too ???
					$search = (!$_POST['id_lookup']? json_decode($this->api->getSpellingSuggestions($_POST['searchTerm'])) : NULL);

				if($search->suggestionGroup->suggestionList->suggestion){
						echo '<em>Did you mean?</em>' ;
						foreach($search->suggestionGroup->suggestionList->suggestion as $loc=>$value)
							echo "\n\t<strong class='suggestion'><a href='?s=$value'>$value</a></strong>\t\n";
						// also if cache enabled check to see if the RXCUI file already exists?
						$first = $search->suggestionGroup->suggestionList->suggestion[0] . '';
						$_POST['searchTerm'] =$first;
						$xml= json_decode($this->api->findRxcuiByString($first));
						$id= $xml->idGroup->rxnormId[0];

						unset($search);
						echo '<p><em>Showing first suggestion '.$first.'.</em></p>';
						// so we don't store incorrect search values as cache!
					}

				}elseif($_POST['r'] && !$id){
					$id= trim($_POST['r']);	
				}
				// Now we get the actual syntax to return the real xml object
				if($id){
					unset($scd);
				// make xml is the only way we can cache something? wtf
					$xml = self::make_xml($id,$formatted);
					// check couch for rxTerms...
						self::loadRxTerms();
						$rxTerms = $this->rxTermsApi->getAllRxTermInfo($id);
						
						if($rxTerms == '{"rxtermsProperties":null}' || $rxTerms == '' || $rxTerms == false){
							;
						}else{
							$scd=true;
							$rxTerms = json_decode($rxTerms);
						
							echo '<ul><li><h2>RxTerms Properties</h2></li>';
							foreach($rxTerms->rxtermsProperties as $name=>$value){
								if(trim($value) != '')
									{
										switch ($name) {
											case 'brandName':
												echo"<li class='$name'><h2>$value</h2></li>";
												break;
											case 'displayName':
												echo"<li class='$name'><h3>$value</h3></li>";
												break;
											case 'synonym':
											
												echo"<li class='$name'><h4>Synonym: $value</h4></li>";
												 break;
											case 'fullName':
												$fullName = $value;
												; break;
											case 'fullGenericName':
												if($value != $fullGenericName){
												echo "<li class='$name'><em>$value</em></li>";
												}
												
												; break;	
											case 'strength':
												; break;	
											case 'rxtermsDoseForm':
												; break;
											case 'route':
												echo "<li><strong>$value</strong></li>";
												 break;
											case 'rxnormDoseForm':
												; break	;
											case 'genericRxcui':
												; break	;	
											
										}
									}


							}
							echo '</ul>';
							// show and cache the term? put couch ??
						}
						
						}
						
				// Modify output slightly to use filters if provided	
				if($formatted && !$_POST['drugs']){
					// this is still in testing phases - for the relationship checker- returns raw object for now
					if($_POST['relatedBy'])
						self::list_2d_xmle($xml);
					else
						self::list_2d_xmle($xml->relatedGroup->conceptGroup);
					}
				elseif($xml->allRelatedGroup)
					self::list_2d_xmle($xml->allRelatedGroup->conceptGroup);
				elseif($xml->data->allRelatedGroup)
					self::list_2d_xmle($xml->data->allRelatedGroup->conceptGroup);
				}
	}
	
	function couchCheck($type=false){
		if(COUCH && $_POST){
		// check if it already exists first ...
		// always get fresh cache token ...
			$couch_token = obcer::cache_token('db') . ($type == 'di'?'_di':NULL);
			//$exec_line = "curl -X GET " . COUCH_HOST . "/" . COUCH_DB . "/$couch_token";
			//$tester = exec($exec_line);
			if(!class_exists('APIBaseClass') && !$this->couch){
				require('APIBaseClass.php');
			}
			
			if(!$this->couch){
				$this->couch = new APIBaseClass();
				$this->couch->new_request(COUCH_HOST . "/" . COUCH_DB);
			}
			$tester = trim($this->couch->_request("/$couch_token",GET));
			if($tester =='{"error":"not_found","reason":"missing"}' || $tester == '{"error":"not_found","reason":"deleted"}' || $tester == '' || $tester == false){

				if($type !='di')
					unset($this->cache);
				else
					unset($this->cache_di);
				return false;
				}
			else{
				//print_r(json_decode($tester));	
				if($type !='di'){
			
					$this->cache =4;
					return $tester;
				}else{
				
					$this->cache_di = true;
					return $tester;
				}
			}
		}
	}
	
	function clean_ndf($data){
	// creates new more efficent ndf data model
		foreach($data as $a=>$b){
			// removes empty concepts groupings (parentConcept,ChildConcepts, modifys array structures to reduce substructures
				if(is_array($b) && count($b[0]) == 0)
					unset($data[$a]);
				else{
					if(is_array($b) && count($b[0]) == 1){
						if(count($b[0]['concept']) == 1){
						// so if an element only has one element, it is set to that single element instead of double embedded array structures
							$data[$a] = $b[0]['concept'][0];
							}
						else{
						// here we process each NUI find concept field ... 
							if($b[0]['concept']) $data[$a] = $b[0]['concept'];
							elseif($b[0]['role']){ 
							// we do a little bit of cleanup on 'roles' and combine fields
								$data[$a] = $b[0]['role'];
								foreach($b[0]['role'] as $r_key=>$r_value){
									if(!is_array($r_value)) $dat2[$r_value['roleName']] = $r_value['concept'];
									else $dat2[$r_value['roleName']] = $r_value['concept'][0];
								}
								if($dat2){
									unset($data[$a]);
									$data[$a] = $dat2;
									unset($dat2);
									}
							}
							elseif($b[0]['properties']) {
								$data[$a] = $b[0]['properties'];
							}
							elseif($data['groupProperties'][0]) {
								$data[$a] = $data['groupProperties'][0]['property'];
								foreach($data[$a] as $loc=>$item){
									$data[$a][$item['propertyName']] = $item['propertyValue'];
									unset($data[$a][$loc]);
								}
							}
							elseif($data['groupRoles']){ 
								foreach($data['groupRoles'] as $x=>$y){
									foreach($y as $n=>$m){
										if(is_array($m) && count($m) == 1)
											$data['groupRoles'][$x] = $m;
									
									}
									$data['groupRoles'][$x] = $y;
								}
								$data[$a] = $data['groupRoles'][0];
							
							}
						}
					}
				}
			}
		return $data;
	}

	function put_couch($xml,$r=NULL,$n=NULL){

	//die('put_couch' . print_r($this));
	// couch stores a slightly more efficent model for easier lookups and does not store empty concept groups
		// stores it flat out i dont want that ...
		if($_POST['nui'] && $this->kind != 'DRUG_KIND'){
			$result = (!is_array($xml)?json_decode($xml):$xml);

			$data = $result['fullConcept'];
			$nui = $data['conceptNui'];
			$name = $data['conceptName'];
			$kind = $data['conceptKind'];
			unset($data['conceptNui'],$data['conceptName'],$data['conceptKind']);
			// group associations need work too @!! gotta write this for the non 'find concepts == on' too 
			$data = self::clean_ndf($data);
			// next append the couch stuff to the data .. not sure how ... could just decode them both before inserting...
			$data = json_encode($data);
			$insert = '{"_id": "'.$this->cache_token.'",
			"nui":"'.$nui.'",
			"name":"'.$name.'",
			"kind":"'.$kind.'",
			"data":'.$data.'}';
		}elseif($r!=NULL){
		// stores regular rxnorm record 
			// re render cache token just incase the term isn't properly rendered
			$this->cache_token = obcer::cache_token('db');
			$insert = '{"_id": "'.$this->cache_token.'",
			"rxcui":"'.($r!=NULL?$r:'').'",
			"data":'.$xml.'}';
		
		}elseif($this->nui && $this->kind == 'DRUG_KIND'){
		// since nui drugkind is set after the cache is returned this should be ok 
		// this is for drug interactions with NUI's ... this is slightly confusing
		// add drug interaction to cache token so we can retreve it based on the post var ..
			
			$insert = '{"_id": "'.obcer::cache_token('db') . '_di'.'",
			"nui":"'.$this->nui.'",
			"kind":"DRUG_INTERACTION",
			"data":'.$xml.'}';

		}
	// hijack base class??
	// having probs sending the curl so use exec for now .. :/
	/*
		if($this->couch){
			$couch_exec =  '/'.$this->cache_token .($this->kind=='DRUG_KIND'?'_di':NULL) ."' -d \ '" . $insert ."'";
			die($this->couch->_request($couch_exec,'PUT',false,array('Content-type: application/json')));
			
		}
	*/
		$exec_line = "curl -X PUT '" . COUCH_HOST . '/' . COUCH_DB . '/'.$this->cache_token .($this->kind=='DRUG_KIND'?'_di':NULL) ."' -d \ '" . $insert ."'".' -H "Content-type: application/json"' ;

		exec($exec_line);
		$xml = self::couchCheck(  ($this->kind == 'DRUG_KIND'?'di':NULL));
		if($xml)
		// show the newly created record in its intended format
			self::procResult(json_decode($xml));
		// don't have to return anything do i?
		return  ($xml?$xml: false);

	}
	function make_xml($id,$formatted=false){

		if(COUCH && !$this->cache){
			$xml = self::couchCheck();
			if($xml != false)
				return json_decode($xml);
			}

		if(!$this->cache){
			self::loadRxNorm();
			$xml = $this->api->getAllRelatedInfo($id);
			if(COUCH) $xml = self::put_couch($xml,$id);
				$check = json_decode($xml);
				$check2 = $check->allRelatedGroup->conceptGroup;
				foreach($check2 as $object){
					if($count > 1)
						$count = (count((array)$object));
				}	
			if($count == 1){
				echo  ('Record did not return any matching RxNorm records');
				return false;
			}else{
				if(COUCH && !$this->cache)
					$xml = self::put_couch($xml);
				$return = $xml;
			}
		}

		return $return;
		 }


	function list_2d_xmle($xml){
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
						if($rowData->tty == 'IN')
							$return .= "\n\t". '<li class="record_'.$key.'"><a href="ndf.php?s='. $value.'">'.$value.'</a></li>'; 
						else
							$return .= "\n\t". '<li class="record_'.$key.'">'. ($rowData->tty=='IN'?'<a href="ndf.php?s='. $result.'">' :NULL) .($key=='umlscui' && $value ==''?'n/a':$value).($rowData->tty=='IN'?'</a>':NULL) . "</li>"; 
						
						}
					}
				}
		}
		return "\n\t<ul>\n\t\t$return\n\t\t</ul>\n";
	}
}
 new RxNormRef;