<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" dir="ltr">
	<head>
		<title>RxNIX</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		 <link rel="stylesheet" type="text/css" href="css/ndf.css" />
<?php
error_reporting(0);

// <link rel="stylesheet" type="text/css" href="css/'.($_POST['css_wide']?'wide.css':'fixed_field.css').'" />

if($_POST['property'] != '' ||$_POST['role'] != ''|| $_POST['association'] != ''   || $_POST['nui_a'] != '1')
{
	// going in reverse order of the way they are displayed - so latest field with a value gets selected
	if($_POST['association'] != '')unset($_POST['property'],$_POST['role'],$_POST['nui_a']);
	if($_POST['role'] !='')unset($_POST['property'],$_POST['association'],$_POST['nui_a']);
	if($_POST['property'] !='')unset($_POST['role'],$_POST['association'],$_POST['nui_a']);
	if($_POST['nui_a'] !='')unset($_POST['property'],$_POST['association'],$_POST['role']);
}

function html_form($type,$array,$id,$label=true,$legend=true,$blank_item=true,$fieldset=true,$inner_container=NULL){
// could ditch the type specification and assume that if not provided we are making a checkbox, but if we want to handle other variables
// we'll have to do something else to tell it its not a checkbox value
// check array if assoc, if so then use the value as its label
	if(is_array($array))
		foreach($array as $loc=>$prop){
			$select = (!is_int($loc)? $loc: str_replace('_',' ',$prop));
			switch ($type) {
			case "option":
				$result.= '<option value="'.$prop.'" '.($_POST[$id] == $prop? " selected ":NULL).'>'. $select . '</option>';
				break;
			case "radio":
				$result .= '<input type="radio" name="'.$id.'" id="scope_1" value="'.$prop.'" '.($_POST[$id] == $prop ? ' checked ':NULL).' /> '.($label==true?'<label for= "'.$prop.'">'.$select.'</label>':NULL);
				break;
			}
			if($inner_container !=NULL && is_array($inner_container)) $result .= $inner_container[0] . $result . $inner_container[1];
			}
	elseif($type='checkbox' && is_string($array))
	// this can't use won't need an inner array... also support an array of checkboxes (given an assoc array)
	// if the count of the passed items is equal to the default then automatically set label/legend/blank item / fieldset to false
		 $result .= '<input type="checkbox" name="'.$id.'" id="'.$id.'" '.($_POST[$id] != 'on' ?  NULL :' checked ' ).'/>';
		// check box processing
		return ($fieldset!=true?NULL:'<fieldset id="'.$id.'">')
				.
				($legend!=true?NULL:'<legend>Show '.$id.' value</legend>')
				.
				($type=='option'?
				'<select title="'.$id.'" name="'.$id.'">'.
					($blank_item != true?NULL:'<option value ="">Select '.$id.'</option>') .$result . '</select>':$result)
				.
				($fieldset!=true?NULL:'</fieldset>');
}
echo 
		'            
	</head>
	<body>
  	<div id ="page">
    	<div id = "header">
				<img src="img/rxnix_logo.gif" alt="rxnix logo"/>
				<form method="post" action="" class="main_form">
					<fieldset id="nui">
						<label for="nui">Enter NUI</label>
							<input type="text" id="nui" title="Nui Entry1" name="nui"  '. ($_POST['nui']?' value = "'. $_POST['nui'] . '" ':NULL).' />'
						;
						
						// to do make the select action check boxes for Child Concepts / Concept Properties / Parent Concepts ....
						//getConceptProperties( "N0000022046", "VUID" )
				
  				// turn the below into a function so i can make this page smaller/faster for each item that has a function the api that returns a list of its available values
  				// property, roles, concepts ??.		
  				// not quite supported but wanted to push to github...
				//		echo '<input type="checkbox" name="advanced" id="advanced" '.($_POST['advanced'] != 'on' ?  NULL :' checked ' ).'/><label for="advanced">Advanced Search</label></fieldset>';
						// also if advanced load the form class @!!
						echo($_POST['advanced']!='on'?NULL:'<fieldset>'. 
						'<em>Pick one*</em>
			
						<select title="nui_a" name="nui_a">
							<option value=""'.($_POST["nui_a"] != ""? NULL:" selected ").'>Select Other Action
							<option value="1"'.($_POST["nui_a"] == "1"? " selected ":NULL).'>All Info</option> 
							<option value="2"'.($_POST["nui_a"] == "2"? " selected ":NULL).'>Child Concepts</option>
							<option value="3"'.($_POST["nui_a"] == "3"? " selected ":NULL).'>Concept Properties</option>
							<option value="4"'.($_POST["nui_a"] == "4"? " selected ":NULL).'>Parent Concepts</option>
							<option value="8"'.($_POST["nui_a"] == "8"? " selected ":NULL).'>Va Class Of Concept</option>
						</select>
						
						'.
						html_form('option',array( "CS_Federal_Schedule", "Class_Code", "Class_Description", "Display_Name", "FDA_UNII",
							  "Level", "MeSH_CUI", "MeSH_DUI", "MeSH_Definition", "MeSH_Name", "NUI",
							  "Print_Name", "RxNorm_CUI", "RxNorm_Name", "SNOMED_CID", "Severity", "Status",
							  "Strength", "Synonym", "UMLS_CUI", "Units", "VANDF_Record", "VA_National_Formulary_Name",
							  "VUID", "code", "kind"),'property',true,false,true,false).html_form('option',array("CI_ChemClass", "CI_MoA", "CI_PE", "CI_with effect_may_be_inhibited_by", "has_Chemical_Structure",
  "has_Ingredient", "has_MoA", "has_PE", "has_PK", "has_TC", "has_active_metabolites", "induces",
  "may_diagnose", "may_prevent", "may_treat", "may_treat_or_prevent", "metabolized_by",
  "site_of_metabolism"),'role',true,false,true,false) . html_form('option',array("Heading_Mapped_To", "Ingredient_1", "Ingredient_2", "Product_Component", "Product_Component-2"),'association',true,false,true,false) . '</fieldset>'
						.
					'
					</fieldset>
					
					<fieldset id="nui_opt">
						<legend>NUI Options</legend>
						'.html_form('radio',array(1,2,3),'scope')
						.'
						<input type="checkbox" name="trans" id="trans" '.($_POST['trans'] != 'on' ?  NULL :' checked ' ).'/><label for="trans">Transitive</label></fieldset>
					').
					'<input type="submit"/>
	  		</form>
			</div>
		<div id ="ndf_content">';

// to do move cacher here ...

// allow get variables to begin NUI lookups/convert to nui id's with the ndf post var
 if($_POST['nui']) {
 	require("../config.php");
	require("../lib/obcer.php");
 	require("../lib/rxNormRef.php");
}

echo"
		</div>
				<div id = 'help'>
				<ul>
					<li><img class='bg_black' src='img/nlm-logo-white.png' alt = 'National Library of Medicine Logo' title='NLM Logo' align='middle'/>
						<em>What is RxNix</em>
						<p>RxNix is a simple semantic medications tool that intefaces with RxNorm - a database developed at the <a href='http://www.nlm.nih.gov/'>National Library of Medicine</a>.</p>
						<a href=;http://www.nlm.nih.gov/research/umls/rxnorm/index.html'>About RxNorm</a>
					</li>
						
					<li><img src='img/UMLS_header_newtree.gif' alt= 'Unified Medication Language System' title='UMLS Logo' align='middle'/>
						<em>UMLS</em>
						<p>RxNorm uses the <a href='http://www.nlm.nih.gov/research/umls/'>Unified Medication Language System</a> to enable a variety of systems to communicate with each other.</p> <a href='http://www.nlm.nih.gov/research/umls/quickstart.html'>UMLS Quickstart Guide</a>
					</li>
						
					<li>
						<em>Searching</em>
						<p>You may begin by entering an ingredient, drug name, concept or other in the Search field. The Advanced search page allows filtering on concept titles, <strong>and id conversions from one system (UMLSCUI/NDC/UNII and others) to RXCUI</strong></p>
					</li>
			
					<li>
					<em>About</em>
						<p>Built with <a href='https://github.com/codeforamerica/rxNorm_php'>rxNorm_php</a> api library and <a href='https://github.com/codeforamerica/rxNormRef_php'>rxNormRef_php</a> maintained  by <a href='http://codeforamerica.org'>Code For America.</a>
							</p>
							<img src ='http://www.totalvalidator.com/images/valid_s_us508.gif' alt='Total Validator Badge' title='Validation Badge'/>
					</li>
				</ul>
			</div>
	</div>		
		</body>
</html>";
