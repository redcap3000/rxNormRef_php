<?php

class rxNormRef{
	static	$normalElements = Array(
			'TTY'=>'Term Type','IN'=>'Ingredients','PIN'=>'Precise Ingredient',
			'MIN'=>'Multiple Ingredients','DF'=>'Dose Forms','SCDC'=>'Semantic Clinical Drug Components',
			'SCDF'=>'Semantic Clinical Drug Forms','BN'=>'Brand Names','SBDC'=>'Semantic Branded Drug Forms',
			'SBDF'=>'Semantic Branded Drug Forms','SBD'=>'Semantic Branded Drug','SY'=>'Term Type','SCD'=>'Semantic Clinical Drug',
			'TMSY'=>'Term Type','BPCK'=>'Brand Name Pack','GPCK'=>'Generic Pack');

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

	function cleanName($name,$ucwords=false){
	// function 'sanitizes' most of the 'names' returned by the api's (they are often in uppercase, 
	// sometimes have underscores, often have / and - with out spaces (making it very hard to do any text flow
		$result = str_replace(array('_','/','-'),array(' ',' / ',' - '),strtolower(trim($name)));
		return ($ucwords == false? $result : ucwords($result));
	
	}
	// proc result prints out the NDF display based on a '$result'
	function procResult($result){
		
		if($result->data){
			// create linked line from keys  instead of list item ... what about mesh defs?
			// padd rxnorm rxcui with zeros!
			if($result->data->RxNorm_CUI && $result->data->kind == 'DRUG_KIND' || $result->data->level == 'Ingredient' ) {
				$result->data->RxNorm_CUI = '<a href="index.php?r='.$result->data->RxNorm_CUI.'">'.$result->data->RxNorm_CUI .'</a>';
				if($result->data->UMLS_CUI)
					$result->data->UMLS_CUI= '<a href="index.php?u='.$result->data->UMLS_CUI.'">'.$result->data->UMLS_CUI.'</a>';
				}
			if($result->data->Rxnorm_Name && strtoupper(str_replace(' ','',($result->data->RxNorm_Name))) == trim($result->name))
			// supress rxnorm name if its the same as the result name
				unset($result->data->RxNorm_Name);
				
			if($result->data->VANDF_Record){
				$result->data->VANDF_Record= explode('</VANDF_Record>',$result->data->VANDF_Record);
				$result->data->VANDF_Record = str_replace('<VANDF_Record>','',$result->data->VANDF_Record[0]);
			}
		echo "<ul><li><h2>" . 
			ucwords(str_replace(array('[',']','/'),array('','',' / '),strtolower($result->data->label))) . ($result->data->Level? ', <em>'.$result->data->Level.'</em>':NULL). ', '. self::cleanName($result->kind) . 
				($result->data->Status !='Active'?'<em>'.$result->data->Status.'</em>':NULL).
			'</h2>'.
				($result->data->Synonym?'<h2>'.self::cleanName($result->data->Synonym,true).'</h2>':NULL).
				
				($result->data->Class_Description? '<ul><li><p>' . $result->data->Class_Description . '</p></li></ul>':NULL).
		
			// html validator may not like wrapping ul's in h4's	
			'</li></ul> <ul class="ndf_codes"><li>Code : <em>'.
				$result->data->code. '</em></li>'. 
				
				($result->data->VANDF_Record?' <li>VANDF Record : <em>'.$result->data->VANDF_Record.'</em></li>':NULL).
				($result->data->VUID?' <li>VUID : <em>' . $result->data->VUID .'</em></li>':NULL) .
				($result->data->RxNorm_CUI?'<li>RxNorm : <em>'.($result->data->Level == 'Ingredient'?'<a href="index.php?r='.$result->data->RxNorm_CUI.'">':NULL).$result->data->RxNorm_CUI . ($result->kind == 'DRUG_KIND'?'</a>':NULL).
				($result->data->UMLS_CUI? " / ".$result->data->UMLS_CUI:NULL) .'</em></li>':NULL) .
				($result->data->MeSH_CUI? '<li>MeSH : <em>'.$result->data->MeSH_CUI . " / " .$result->data->MeSH_DUI .'</em></li>':NULL) .
				($result->data->SNOMED_CID? '<li>SnoMed : <em>'. $result->data->SNOMED_CID .'</em></li>':NULL).
				'</ul>';
		
		echo ($result->data->MeSH_Definition?'<ul><li><p>' . $result->data->MeSH_Definition . '</p></li></ul>':NULL) . ($result->data->MeSH_Name && $result->data->MeSH_Name =! $result->data->Display_Name? ' MeSh Name: '.$result->data->MeSH_Name:NULL); 

		if($result->data->drugInteractions != 'none')
			echo self::displayDrugInteractions($result->data->drugInteractions);

		
		if($result->data->medline != 'none'){

			// medline needs to be converted into assoc arrays because of the @attributes key... easiest way is to do this :
			$medline = json_encode($result->data->medline);
			
			$medline = json_decode($medline,true);
			//print_r($medline);
			if(!is_array($medline['entry']['summary']) && $medline['entry']['summary'] != '') echo "<ul><li><h3>MedLine: <a href='".$medline['entry']['link']['@attributes']['href']."'>".$medline['entry']['title']."</a></h3></li><li class='medline'>".$medline['entry']['summary']."</li></ul>";
					
		}
		

		unset($result->data->medline,$result->data->drugInteractions,$result->data->Class_Description,$result->data->Class_Code,$result->data->Level,$result->data->SNOMED_CID,$result->data->MeSH_CUI,$result->data->MeSH_DUI,$result->data->label,$result->data->code,$result->data->RxNorm_CUI,$result->data->Synonym,$result->data->Display_Name,$result->data->NUI,$result->data->MeSH_Name,$result->data->MeSH_Definition,$result->data->VUID,$result->data->VANDF_Record,$result->data->UMLS_CUI,$result->data->Status);
		
		$childConcepts = $result->data->childConcepts;
		$parentConcepts = $result->data->parentConcepts;
		
		unset($result->data->childConcepts,$result->data->parentConcepts);
		// i want this to render last.. this was the most simple way
		if(trim($childConcepts) != '' || $childConcepts != NULL)$result->data->childConcepts = $childConcepts;
		if(trim($parentConcepts) != '' || $parentConcepts != NULL)$result->data->parentConcepts = $parentConcepts;
		
		unset($parentConcepts,$childConcepts);

		foreach($result->data as $prop=>$obj){
			if(is_object($obj)){
				$kind = $result->kind;
				
				if($prop == 'childConcepts'){
					$prop = 'Child Concepts';
				}
				
				if($prop == 'parentConcepts'){
					$prop = 'Parent Concepts';
				}
				
				if($prop == 'groupRoles'){
					$prop = 'Roles';
				}
				echo "<ul><li><h3>$prop</h3></li></ul><ul>";
				if(count($obj) == 1){
					foreach($obj as $kind_item=>$the_object){
						unset($temp);
						if(is_object($the_object)){
							echo "<li><ul><li><h4>".self::cleanName($kind_item,true)."</h4></li><li>";
							foreach($the_object as $nui=>$name)
								$temp .= "<li><a href='?n=" . $nui . "'>" . self::cleanName($name) . '</a></li>';
							
							echo '<ul>' . $temp . '</ul>' .'</li></ul></li>';
													
						}else
							echo "<li><a href='?n=$kind_item'>".self::cleanName($the_object)."</a></li>";
					}
					echo '</ul>';
				}else{
					foreach($obj as $obj2){
						foreach($obj2 as $key=>$value)
							echo "<li><a href='?n=$key'>$value</a></li>";
						
					echo '</ul>';
					}
				
				}
			}else
				echo "<ul><li>".self::cleanName($prop,true)." : $obj</li></ul>";
			
		}
	}

	}
	function post_check(){
		if($_POST['nui']){
			if(COUCH){
				$result = self::couchCheck();
				if($result != false) {
					$this->cache = 4;
					$this->nui = $result->nui;
					$this->kind = $result->kind;
					// move group properties to the top of the object for faster search results ?
					self::procResult($result);
					// spit it out to screen ????
				}

			}
			if(!$this->cache || $_POST['s']){
				self::loadNdf();
				if(!$this->cache){
				// BUILDS THE NDF Page - determines if its a drug kind, and then looks up interactions .. also looksup SNOMED_CID if 
				// found .. and will store the entire repsonses from medline
				// needs some variable cleanup
					self::loadNdf();
					$result2 = $this->ndfApi->getAllInfo($_POST['nui']);
					$r2 = json_decode($result2,true);
					$this->nui = $_POST['nui'];
					$this->kind = $r2['fullConcept']['conceptKind'];
					
					$r2 = $this->clean_ndf($r2['fullConcept']);
	
					if($this->kind == 'DRUG_KIND'){
						self::cleanDrugInteractions();
						
						if($this->drugInteraction){
							$r2['drugInteractions'] = json_decode($this->drugInteraction);
						
						}
					}
					
					if($r2['SNOMED_CID']){
	
							$this->loadMedLine();
							$medline = $this->medlinePlusApi->get_diagnosis($r2['SNOMED_CID'],'SNOMED');
							$medline = json_decode($medline,true);
	
							$r2['medline'] = (!$medline['entry']?'none': $medline);
							
						}
					
					$result = $r2;	
				}	
				if($result && COUCH) {
				// use put couch...
					
					$insert = '{"_id": "'.$this->cache_token.'","title":"'.$result['conceptName'].'","kind":"'.$result['conceptKind'].'"}';
					
					$insert = json_decode($insert);
	
					// determine if drug interactions is 'empty' and store a false value instead of ugly structure...
					
					// prevents lookup of non-existant drug interactions with default caching behavior
	
					if($result['drugInteractions']->name == '')
						$result['drugInteractions'] = 'none';
					// not working ....	
					// remove repetitve fields as they are not needed.. 
					
					unset($result['conceptName'],$result['conceptKind'],$result['conceptNui']);
					
					$insert->data = $result;
					$insert = json_encode($insert);
					$exec_line = "curl -X PUT '" . COUCH_HOST . '/' . COUCH_DB . '/'.$this->cache_token ."' -d \ '" . str_replace("'","'\''",$insert) ."'".' -H "Content-type: application/json"' ;
					$exec_line = exec($exec_line);
					// this is running and not checking for the appropriate record, find another way to check
					// get first couple of chars to see if 'ok:true' is found
					//{"ok":true,"id":"n0000000007__on","rev":"1-0c0aad438c4b12fdf1fbddbb4764f21a"}
					$exc_check = explode(',',$exec_line);
					
					if(trim($exc_check[0]) != '{"ok":true'){
						// render from couch instead of object.. probably not great idea ... but also checks drug interactions when available ..
						$this->couch_errors['put_couch'][]= $exec_line;	
					}else{
					// should show the record ....
						self::procResult(json_decode($insert));
					}
	
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
						// store this value...
						
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

	function cleanDrugInteractions(){
		self::loadNdf();
		// no nui to lookup
		// set the kind and check if kind is of 'DRUG_KIND"
		$drug_inter = $this->ndfApi->findDrugInteractions($this->nui,3);
		$drug_inter = json_decode($drug_inter);
		$inputNui = $drug_inter->responseType->inputNui1;
		$drug_inter = $drug_inter->groupInteractions->interactions[0];
		if($inputNui != $drug_inter->concept->conceptNui)
			$drug_inter->inputNui = $inputNui;
		else
		// dont display the comment if the nui entered is the nui resolved
			unset($drug_inter->comment);
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

		if(COUCH){
			$this->drug_inter = true;
			$drug_inter_p = json_encode($drug_inter);
			$this->drugInteraction = json_encode($drug_inter);
			// put couch SHOULD return the new record that gets stored .. instead of having to deal with two types of crap..
			//$drug_inter = self::put_couch($drug_inter_p);
		}
	}
	
	function couchCheck(){
		if(COUCH && $_POST){
		// check if it already exists first ...
		// always get fresh cache token ...
			$couch_token = obcer::cache_token('db') . ($type == '_ml' || $type == '_di'?$type:NULL);

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
				unset($this->cache);
				return false;
				}
			else{
			// mainly for reporting where the cache is coming from for debugging purposes
				$this->cache =4;
				return json_decode($tester);

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
							if($data[$a]['conceptName']){
								$data[$a][$data[$a]['conceptNui']] = $data[$a]['conceptName'] . ', ' . strtolower(str_replace('_',' ',$data[$a]['conceptKind']));
								unset($data[$a]['conceptNui'],$data[$a]['conceptName'],$data[$a]['conceptKind']);
								// what about kind ? most of the time the kind is identical to the currently selected member... append to name for now and deal w/ later...
							}else
								// do multiple concepts in same fashion ..
								foreach($data[$a] as $key=>$concept)
									$data[$key][$concept['conceptNui']] = $concept['conceptName'] . ', ' . strtolower(str_replace('_',' ',$concept['conceptKind']));
							}
						else{
						// here we process each NUI find concept field ... 
							if($b[0]['concept']) {
								$data[$a] = $b[0]['concept'];
								
								foreach($data[$a] as $loc=>$concept){
								// not sure how i feel about storing the concept kind as another array key.. seems like all parent/children always
								// have the same kind as the original nui record..
									$data[$a][$concept['conceptKind']][$concept['conceptNui']] = $concept['conceptName'];
									unset($data[$a][$loc]);
								}
							}
							elseif($b[0]['role']){ 
							// we do a little bit of cleanup on 'roles' and combine fields
								$data[$a] = $b[0]['role'];
								foreach($b[0]['role'] as $r_key=>$r_value){
									if(!is_array($r_value)) $dat2[$r_value['roleName']] = $r_value['concept'];
									else $dat2[$r_value['roleName']] [$r_value['concept'][0]['conceptNui']]= $r_value['concept'][0]['conceptName'];
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
									$data[$item['propertyName']] = $item['propertyValue'];
									unset($data['groupProperties']);
								}
							}
							elseif($data['groupRoles']){ 
							// this could probably be deleted ??
								foreach($data['groupRoles'] as $x=>$y){
									foreach($y as $n=>$m){
										if(is_array($m) && count($m) == 1)
											$data['groupRoles'][$x] = $m;
									
									}
									$data['groupRoles'][$x] = $y;
								}
								$data[$a] = $data['groupRoles'][0];
								
								foreach($data[$a] as $role=>$concepts)
									$data[$a][$concepts['conceptNui']] = $concepts['conceptName'] .', ' . str_replace('_',' ',strtolower($concepts['conceptKind']));								
							}
						}
					}
				}
			}

			// remove nui cause its already there..
			
			if(strtoupper($data['Display_Name']) == strtoupper($data['label']) || strtoupper($data['RxNorm_Name']) == strtoupper($data['label'])  ){
				unset($data['Display_Name']);
			}
			unset($data['NUI'],$data['kind']);
		return $data;
	}

	function put_couch($xml,$r=NULL){
		// re work this function to process both rxnorm ndf records
		if($r!=NULL){
				// stores regular rxnorm record 
					// re render cache token just incase the term isn't properly rendered
					$this->cache_token = obcer::cache_token('db');
					$insert = '{"_id": "'.$this->cache_token.'",
					"rxcui":"'.($r!=NULL?$r:'').'",
					"data":'.$xml.'}';
					
		}
		
		$exec_line = "curl -X PUT '" . COUCH_HOST . '/' . COUCH_DB . '/'.$this->cache_token ."' -d \ '" . $insert ."'".' -H "Content-type: application/json"' ;
		$exec_line = exec($exec_line);
		
		$exc_check = explode(',',$exec_line);
		if(trim($exc_check[0]) != '{"ok":true')
			$this->couch_errors ['put_couch']= $exec_line;

		// don't have to return anything do i?
		// return the inserted record to be decoded json ...
		return  ($xml?json_decode($insert): false);

	}
	
	function make_xml($id,$formatted=false){
	// get rid of this function and handle via couchCheck /put_couch
	// needs to be re-written ... . and these checks handled via post check
		if(COUCH && !$this->cache){
			$xml = self::couchCheck();
			if($xml != false){
			// wait a sec... this is only required if we are searching rxnorm ...
				//$xml = json_decode($xml);
				echo self::displayRxNorm($xml->data);
				}
			}

		if(!$this->cache){
			self::loadRxNorm();
			$xml = $this->api->getAllRelatedInfo($id);

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
				// only thing to use put_couch for now... put couch bloated and unuseful...
					$return = self::put_couch($xml,$rxcui);
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
			$return .= "\n\t<ul><li><ul>\n\t\t<li class='property'>".  (self::$normalElements[strtoupper($key)]?self::$normalElements[strtoupper($key)]:$key)  ."</li>\n\t</ul></li><li><ul class='".$key."'>";
			foreach($value as $rxcui=>$prop_object){
				unset($prop_object->umlscui);
				$return .= "\n\t". '<li><ul><li>'. "<h3><a href='?r=$rxcui'>" . str_replace(array('/','-',','),array(' / ','-', ','),trim($prop_object->name)) . "</a></h3>" .  "</li>" . ($prop_object->synonym?"<li>$prop_object->synonym</li>":NULL) . ($prop_object->umlscui?"<li class='uml'><a href='?u=$prop_object->umlscui'>$prop_object->umlscui</a></li>":NULL)  .'</ul></li>';
			}
			$return .= "</ul></li></ul>";
				}
		return ($return?$return:false);
	}
	
	function displayDrugInteractions($drug_inter){

		echo '
			<ul>
			<li class="a_title"><h3>Drug Interactions</h3></li>
			'.($drug_inter->comment?'<li class="d_int_comment"><ul><li><p>'.$drug_inter->comment."</p></li>":NULL)."</ul></li>";
				// json decode moves the object around a wee bit..
			if($drug_inter->interactions)	

				foreach($drug_inter->interactions as $sev=>$drug){
					// sev = severity, drug is the single line in the array 
					$title = $sev;
					echo "<li><ul><li><strong class='int_name'>$title</strong></li><li><ul>";
					foreach($drug as $nui=>$name)
						// make a link in comma sep. list ..
						echo "<li><a href='ndf.php?n=$nui'>".ucwords(strtolower($name))."</a></li>";
					echo "</ul></li></ul></li>";
					
					}

		echo "</ul>";
	
	}
	
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
	
	function loadMedLine(){
			if(!class_exists('APIBaseClass')) require 'APIBaseClass.php';
			if(!class_exists('medlinePlusApi')){
				require 'medlinePlusApi.php';
				// little override that should hope to return json responses... with some conversion..
				$this->medlinePlusApi = new medlinePlusApi(null,'json');
			}
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
	}
 new RxNormRef;