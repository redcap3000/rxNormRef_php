<?php

class rxNormRef{
	static	$normalElements = Array(
			'TTY'=>'Term Type','IN'=>'Ingredients','PIN'=>'Precise Ingredient',
			'MIN'=>'Multiple Ingredients','DF'=>'Dose Forms','SCDC'=>'Semantic Clinical Drug Components',
			'SCDF'=>'Semantic Clinical Drug Forms','BN'=>'Brand Names','SBDC'=>'Semantic Branded Drug Forms',
			'SBDF'=>'Semantic Branded Drug Forms','SBD'=>'Semantic Branded Drug','SY'=>'Term Type','SCD'=>'Semantic Clinical Drug',
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
		if(COUCH_STAT == true){
			// using one letter keys for smaller footprint
			// bare bones stat options ..
			$insert['i'] = $_SERVER['REMOTE_ADDR']; 
			$insert['t'] = (float) sprintf("%.4f", (((float) array_sum(explode(' ',microtime())))-$this->start_time));
			$insert['m'] = (int) round(memory_get_usage() / 1024);
			
			if(COUCH_STAT_VERBOSE == true){
			// extra options.. user agent is the 'heaviest' .. consider converting to codes or storing unfound user agents to a agents database ?
			// perhaps couch can be smart about it and compress similar values? we'll see...
				if(COUCH_STAT_UA == true)
					$insert['ua'] = $_SERVER['HTTP_USER_AGENT'];
				$insert['rt'] = (int) $_SERVER['REQUEST_TIME'];
				$insert['ref'] = str_replace('http://','',$_SERVER['HTTP_REFERER']);
				if( trim($insert['ref']) =='' || $insert['ref'] == false || $insert['ref'] == NULL ) unset($insert['ref']);
				$insert['uri'] = $_SERVER['REQUEST_URI'];
			}
			if($this->rxcui)
				$insert ['r'] = $this->rxcui;
			elseif($this->nui){
				// remove the N to store as an integer .. maybe even remove all the zeros ?
				$insert ['n'] = explode('N',$this->nui);
				$insert ['n'] = (int)$insert ['n'][1];
				}
			$insert = json_encode($insert);
			$exec_line = "curl -X POST '" . COUCH_HOST . COUCH_STAT . '/'. "' -d \ '" . $insert ."'".' -H "Content-type: application/json"' ;
			exec($exec_line);	
		}
		if(COUCH_ERRORS == true){
			if($this->couch_errors){
				$couch_errors = "\n";
				foreach($this->couch_errors as $where=>$error){
				// give basic dump of json ? could get fancy but for debugging/logging purposes
					$couch_errors .= "\tinside of :: $where $error\n";
				}
			}
		
		}
		return ($this->cache?'<p>Rendering from Couch Database' . ($this->cache == 5? ' from view only couchdb':NULL) . '</p>' :NULL) . ($couch_errors?"\n<small><strong>Encountered couch api errors:</strong> <br/> $couch_errors</small>\n":NULL).
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
	

	// Function takes result and creates ndfrt html for screen output, while all fields are stored in the db, not all are outputted
	// Does a lot of string processing, adds spaces to long slashed names (making page flow easier), changes cases on several occasions, adds spaces to commas
	// Also checks to see if certain fields become redunant - add linkback to rxcui for rxcui crawling???
		if($result->data){
			// parent concepts
			if(is_array($result->data->parentConcepts)){
				foreach($result->data->parentConcepts as $pc_key=>$pc_value)
					$theRow ['parentConcepts'] []= $this->build_concept('pname',str_replace(array("/",','),array(' / ',', ') ,trim($pc_value->conceptName)),$pc_value->conceptNui,$pc_value->conceptKind);
			}elseif(is_object($result->data->parentConcepts)){
			
				$theRow ['parentConcepts'] []= $this->build_concept('pname', str_replace(array("/",','),array(' / ',', ') ,trim($result->data->parentConcepts->conceptName)),$result->data->parentConcepts->conceptNui,$result->data->parentConcepts->conceptKind);
			//	print_r($theRow);
			
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
						}elseif($gp_key == 'RxNorm_CUI'){
								$rxcui = $gp_value;
						}
						else{
					//	$inV = ucwords(str_replace(array("/",','),array(' / ',', '),strtolower(trim($inV))));
					//	$theRow ['groupProperties'][]= "\t\t<li class='gProperty'><strong>" . str_replace('_',' ',$inK) . '</strong> ' . (!in_array($inK,array('RxNorm_CUI','NUI','UMLS_CUI','code','MeSH_CUI','MeSH_DUI','FDA_UNII'))?strtolower($inV):$inV) . '</li>';
						}
				
				}

		}
			// wanted to show group properties first ...
			if($group_property_name || $group_level){
				echo "<ul><li class='groupPropName'><h2>". ($rxcui && $this->kind == 'DRUG_KIND'? '<a href="index.php?r='.$rxcui.'">':NULL) . ($group_property_name?ucwords(strtolower($group_property_name) .':') :NULL).  ($group_level?"  $group_level" : NULL) . ($rxcui && $this->kind == 'DRUG_KIND'? '</a>':NULL) .'</li>'. ($group_status?"<h3>$group_status</h3>":NULL). ($mesh_def?"<p>$mesh_def</p>":NULL)  . ($sym?"<br/><strong>Synonyms : <em>$sym</em>  </strong>":NULL) . '</li></ul>'  ;
			//	if($theRow['groupProperties']){
				
			//		echo self::echoProp($theRow['groupProperties']);
			//		unset($theRow['groupProperties']);
				}
			//	echo '<ul><li class="groupPropName"><h3>Related Concepts</h3></li></ul>';
				echo self::echoProp($theRow);
		//	}
			//print_r($this);
			
			if((!$scd && !$this->cache) && $_POST['nui']){
				// this may not be catching properly...
				;
			//	echo '<ul><li class="groupPropName"><h2>No Record</h2><p>A record could not be found for the corresponding NUI, please check back later.</h2></li></ul>';
			}	
		}
		else{
		// attempt to clean data and re-proc result ... ???
	//	print_r($result['fullConcept']);
			
			//print_r($result);
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
								;
							//	$result = json_decode($result,true);
								
								
								
								// set other stuff here too ??
								// so we're not putting the couch properly.... arghhhh because json decode is an array !
								
								//$result->data = self::clean_ndf($result_a['fullConcept']);
								
								//echo self::procResult($result);
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
				if($result && $this->kind == 'DRUG_KIND'){	
				
					// check for drug interactions, store in database as another record append di to the cache token
					$drug_inter = self::couchCheck('di');
					
				
					// obviously if couch is disabled this below will run (not supported for xml/json file caching just yet)
					if($drug_inter == false){
						self::loadNdf();
						// set the kind and check if kind is of 'DRUG_KIND"
						$drug_inter = $this->ndfApi->findDrugInteractions($this->nui,3);
						
						$drug_inter = json_decode($drug_inter);
						
						
						//print_r($drug_inter);
						
						$inputNui = $drug_inter->responseType->inputNui1;
						
						$drug_inter = $drug_inter->groupInteractions->interactions[0];
						
						
						if($inputNui != $drug_inter->concept->conceptNui)
							$drug_inter->inputNui = $inputNui;
						else{
						// dont display the comment if the nui entered is the nui resolved
							unset($drug_inter->comment);
						}	
						
						$drug_inter->concept = $drug_inter->concept[0];
						
						$drug_inter->name = $drug_inter->concept->conceptName;
						
						
						$drug_inter->nui= $drug_inter->concept->conceptNui;
						if($drug_inter->concept->conceptKind != 'DRUG_KIND')
							$drug_inter->kind= $drug_inter->concept->conceptKind;
						unset($drug_inter->concept);
						
						$drug_inter->groupInteractingDrugs = $drug_inter->groupInteractingDrugs[0]->interactingDrug;
						
						foreach($drug_inter->groupInteractingDrugs as $loc=>$drug){
							$drug->name = $drug->concept[0]->conceptName;
							$drug_nui = $drug->concept[0]->conceptNui;
							$drug_sev = $drug->severity;
							if($drug->concept[0]->conceptKind != 'DRUG_KIND')
								$drug->kind = $drug->concept[0]->conceptKind;
							unset($drug->concept);
							unset($drug_inter->groupInteractingDrugs);
							unset($drug->severity);
							
							// for interactions .. are currently only checked for drug_kinds.. but just incase this is here..
							// display functions will need to be modified..
							if($drug->kind)
								$drug_inter->interactions[$drug_sev][$drug_nui] = $drug;
							else
								$drug_inter->interactions[$drug_sev][$drug_nui] = $drug->name;
						
						}
						
						//die(print_r($drug_inter));
						if(COUCH){
						// uh oh how to retreve record if it exists ??
							$this->drug_inter = true;
							// does this need to  be json i'm assumming...
							$drug_inter_p = json_encode($drug_inter);
							self::put_couch($drug_inter_p);
							//$drug_inter = json_decode($drug_inter);
							//$drug_inter = $drug_inter->data->groupInteractions->interactions;
						}
						
						//$drug_inter = json_decode($drug_inter);
						//$drug_inter = $drug_inter->groupInteractions->interactions;
						
					}else{
					// if we want to do file caching need to change couchCheck to
					// drug interaction response could use rewriting groupInteractingDrugs->interactingDrug->(interactiongDrug[0]->concept
						$this->drug_inter = true;
						$drug_inter = json_decode($drug_inter);

					}

					if($this->drug_inter = true && $drug_inter->data->interactions || $drug_inter->interactions){ 
					// interactions not always showing.. might need to move rewriting of interactions to an external functions like the rxnorm ...
						//print_r($drug_inter);	
						echo '
						<ul>
						<li class="a_title">Drug Interactions</li>
						'.($drug_inter->data->comment?'<li class="d_int_comment"><ul><li>'.$drug_inter->data->comment."</li>":NULL)."</ul></li>";

							// json decode moves the object around a wee bit..

						if($drug_inter->data->interactions)	
							$drug_inter->interactions = $drug_inter->data->interactions; 
						foreach($drug_inter->interactions as $sev=>$drug){
							// sev = severity, drug is the single line in the array 
							$title = $sev;
							//die($title);
							echo "<ul><li><ul><li><strong>$title</strong></li></ul><li><ul>";
							foreach($drug as $nui=>$name){
								// make a link in comma sep. list ..
									
									echo "<li><a href='ndf.php?n=$nui'>".strtolower($name)."</a></li>";
									
								}
							echo "</ul></li>";
							
							}

					echo "</ul>";
					}
					
					else{
							print_r($drug_inter);
						// report that NUI doesn't have any reported interactions at this time...
							unset($drug_inter);
							
					}
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
					unset($this->scd);
				// make xml is the only way we can cache something? wtf
					$xml = self::make_xml($id,$formatted);
					if(!is_object($xml)){
						$xml = json_decode($xml);
						$this->cache = 4;
					}
					// check couch for rxTerms...
						self::loadRxTerms();
						$rxTerms = $this->rxTermsApi->getAllRxTermInfo($id);
						
						if($rxTerms == '{"rxtermsProperties":null}' || $rxTerms == '' || $rxTerms == false){
							;
						}else{
							$this->scd=true;
							$rxTerms = json_decode($rxTerms);
						
							echo '<ul class="rxterms"><li><h2 class="property">RxTerms Properties</h2></li>';
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
				if($xml->rxcui){
				// rewrite the rxnorm data model to have concept groups set to the tty and remove empty concept groups
					$this->rxcui = $xml->rxcui;
				}
				// Modify output slightly to use filters if provided	
				}
	}
	
	function couchCheck($type=false){
		if(COUCH && $_POST){
		// check if it already exists first ...
		// always get fresh cache token ...
			$couch_token = obcer::cache_token('db') . ($type == 'di'?'_di':NULL);
			if(!class_exists('APIBaseClass') && !$this->couch){
				require('APIBaseClass.php');
			}
			
			if(!$this->couch){
				$this->couch = new APIBaseClass();
	
				if(COUCH_VIEW != true)
				// if this fails just get it from where it was put - perhaps rep. hasn't happened ..
					$this->couch->new_request(COUCH_HOST . "/" . COUCH_DB);
				elseif(COUCH_VIEW_HOST){
					
					$this->couch->new_request(COUCH_VIEW_HOST);
					
					}
					
			}
			$tester = trim($this->couch->_request("/$couch_token",GET));
			
			if($tester =='{"error":"not_found","reason":"missing"}' || $tester == '{"error":"not_found","reason":"deleted"}' || $tester == '' || $tester == false){
					$this->couch_errors['couchCheck'] = $tester;
				if(COUCH_VIEW == true){
					
					$this->couch->new_request(COUCH_HOST . "/" . COUCH_DB);
					$tester = trim($this->couch->_request("/$couch_token",GET));
					if($tester !='{"error":"not_found","reason":"missing"}' && $tester != '{"error":"not_found","reason":"deleted"}' && $tester != '' && $tester != false){
						// let stats know that it was served up from 'view only' couch db
						$this->cache = 5;
						return $tester;
						}else{
						
						$this->couch_errors['couchCheck_COUCH_VIEW'] = $tester;
						}
				}

				if($type !='di')
					unset($this->cache);
				else
					unset($this->cache_di);
				return false;
				}
			else{
			// mainly for reporting where the cache is coming from for debugging purposes
				if(COUCH_VIEW == true){
					$this->cache = 5;
					return $tester;
				}

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

	// couch stores a slightly more efficent model for easier lookups and does not store empty concept groups
		// stores it flat out i dont want that ...
		if($_POST['nui'] && $this->kind != 'DRUG_KIND'){
			$result = (!is_array($xml)?json_decode($xml,true):$xml);
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

	
		$exec_line = "curl -X PUT '" . COUCH_HOST . '/' . COUCH_DB . '/'.$this->cache_token .($this->kind=='DRUG_KIND' && $this->drug_inter?'_di':NULL) ."' -d \ '" . $insert ."'".' -H "Content-type: application/json"' ;
		
		
		// if this line fails.. and its common just put back out there (json utf issue that needs some work)
		$exec_line = exec($exec_line);
		// this is running and not checking for the appropriate record, find another way to check
		// get first couple of chars to see if 'ok:true' is found
		//{"ok":true,"id":"n0000000007__on","rev":"1-0c0aad438c4b12fdf1fbddbb4764f21a"}
		$exc_check = explode(',',$exec_line);
		
		if(trim($exc_check[0]) == '{"ok":true'){
		// render from couch instead of object.. probably not great idea ... but also checks drug interactions when available ..
			self::procResult(json_decode($insert));
			// this needs to be different for durg interaction ???
		}else{
			// log error (not cached)
			$this->couch_errors ['put_couch']= $exec_line;
			self::procResult(json_decode($insert));
		}
		// don't have to return anything do i?
		return  ($xml?true: false);

	}
	function make_xml($id,$formatted=false){
	// get rid of this function and handle via couchCheck /put_couch
		if(COUCH && !$this->cache){
			$xml = self::couchCheck();
			if($xml != false){
			// wait a sec... this is only required if we are searching rxnorm ...
				$xml = json_decode($xml);
				echo self::displayRxNorm($xml->data);
				}
			}

		if(!$this->cache){
			self::loadRxNorm();
			$xml = $this->api->getAllRelatedInfo($id);
		//	if(COUCH) self::put_couch($xml,$id);
			
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

				// set $this->rxcui ?? for stats ?	
				$return = $xml;
				$return = json_decode($return);
				
				$return = $return->allRelatedGroup;
				
				if(count($return->conceptGroup) > 1){
					foreach($return->conceptGroup as $concept){
						foreach($concept->conceptProperties as $item){
						// reform the json document, set tty's as array keys, and rxcui's as array keys for the concept properties 
						// results in more logical selection, and smaller record sizes, could take step further and remove use of keys for concept properties
							$tty = $item->tty;
							$rxcui = $item->rxcui;
							unset($item->rxcui,$item->tty);
						// suppress empty values, repetitive values, including the 'language' and 'suppress' columns (which are almost 100% 'ENG' and 'N'
							foreach($item as $key2=>$value2)					
								if( ($value2 == '' || $value2 == ' ' || trim($value2) == '' )   ||  ($key2=='language' && $value2 == 'ENG')   || ($key2=='suppress' && $value2 == 'N')  )
									unset($item->$key2);
							$new_object[$tty] [$rxcui]= $item;

						}
						
					}
					// to do rewrite the function that processes the 'make_xml' result ... change the name of 'make_xml' to make_rxnorm
					// store drug interactions in a similar format in same record ?
					// show this new object properly... not sure how ... the list_2d_xmle is effing up ...
					unset($return);
					
					
					echo self::displayRxNorm($new_object);
					
					$xml = json_encode($new_object);
				}
				
				if(COUCH && !$this->cache){
					self::put_couch($xml,$rxcui);
					}
				
			}
		}
		return $return;
		 }


	function displayRxNorm($new_object){
	// to do .. turn dose forms into a comma seperated list , without links.
	// do the same for brand names (if multiples exist) but w create links. MIN PIN IN
	// reorder the items to display more logically ...  Example : dose forms and semantical clinical drug forms are essentially the same thing ..
	// turn things into drop down menus ?
	
		foreach($new_object as $key=>$value){
				
					if(is_array($value) && count($value) > 1){
					// now we have multiples of the same subject heading 
					// tty becomes $key
					
						;
					
					}
					
					$return .= "\n\t<ul><li><ul>\n\t\t<li class='property'>".  (self::$normalElements[strtoupper($key)]?self::$normalElements[strtoupper($key)]:$key)  ."</li>\n\t</ul></li><li><ul class='".$key."'>";
					foreach($value as $rxcui=>$prop_object){
						unset($prop_object->umlscui);
						$return .= "\n\t". '<li><ul><li>'. "<h3><a href='?r=$rxcui'>" . str_replace(array('/','-',','),array(' / ','-', ','),trim($prop_object->name)) . "</a></h3>" .  "</li>" . ($prop_object->synonym?"<li>$prop_object->synonym</li>":NULL) . ($prop_object->umlscui?"<li class='uml'><a href='?u=$prop_object->umlscui'>$prop_object->umlscui</a></li>":NULL)  .'</ul></li>';
					
					}
					$return .= "</ul></li></ul>";
				
				
				}
		return ($return?$return:false);
	}
}
 new RxNormRef;