<?php
error_reporting(0);
include('header.html');                	
echo '<link rel="stylesheet" type="text/css" href="css/'.($_POST['css_wide']?'wide.css':'fixed_field.css').'"'." />
		</head>
        <body>
        <div id ='page'>
        <div id = 'header'>
        <img src='img/new_logo.gif' alt='rxnix logo'/>
           
    	<form method='post' action='' class='main_form'>
    	".    	
    	
    	($_GET['a']?
    	"
					<fieldset>
					<legend>Ingredient/Packs</legend>
						<ul>
							<li><input type='checkbox' name='related[]' id='IN' value='IN' ".(in_array('IN',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'IN'>Name</label></li>
							<li><input type='checkbox' name='related[]' id='PIN' value='PIN' ".(in_array('PIN',$_POST['related']) ? ' checked ':NULL)."/> <label for= 'PIN'>Precise</label></li>
							<li><input type='checkbox' name='related[]' id='MIN' value='MIN'".(in_array('MIN',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'MIN'>Multiple</label></li>
							<li><input type='checkbox' name='related[]' id='BPCK' value='BPCK'".(in_array('BN',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'BPCK'>Brand Pack</label></li>
							<li><input type='checkbox' name='related[]' id='GPCK' value='GPCK'".(in_array('BN',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'GPCK'>Generic Pack</label></li>
						</ul>
					</fieldset>	
					<fieldset>
					<legend>Clinical Semantics</legend>
						<ul>
							<li><input type='checkbox' name='related[]' id='DF' value='DF'".(in_array('DF',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'DF'>Dose Form</label></li>
							<li><input type='checkbox' name='related[]' id='SCDC' value='SCDC'".(in_array('SCDC',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'SCDC'>Component</label></li>
							<li><input type='checkbox' name='related[]' id='SCDF' value='SCDF'".(in_array('SCDF',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'SCDF'>Form</label></li>
						</ul>
					</fieldset>
					<fieldset>
					<legend>Semantic Brandings</legend>
						<ul>
							<li><input type='checkbox' name='related[]' id='BN' value='BN'".(in_array('BN',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'BN'>Name</label></li>
							<li><input type='checkbox' name='related[]' id='SBD' value='SBD'".(in_array('SBD',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'SBD'>Drug</label></li>
							<li><input type='checkbox' name='related[]' id='SBDC' value='SBDC'".(in_array('SBDC',$_POST['related']) ? ' checked ':NULL)."/> <label for= 'SBDC'>Component</label></li>
							<li><input type='checkbox' name='related[]' id='SBDF' value='SBDF'".(in_array('SBDF',$_POST['related']) ? ' checked ':NULL)." /> <label for= 'SBDF'>Form</label></li>
						</ul>
					</fieldset>
	
					<fieldset id='id_types'>
					<legend>Id Conversion</legend>
						<ul>
							<li>
							<select title='id_conversion' name='id_lookup'>
								<option value='' >Lookup by Search String or convert id</option>
								<option value='UMLSCUI'".($_POST['id_lookup'] == 'UMLSCUI'? ' selected ':NULL).">Unified Medical Language System (UMLS)</option> 
								<option value='UNII_CODE'".($_POST['id_lookup'] == 'UNII_CODE'? ' selected ':NULL).">FDA Unique Ingredient (UNII)</option>
								<option value='SPL_SET_ID'".($_POST['id_lookup'] == 'SPL_SET_ID'? ' selected ':NULL).">FDA Structured Product Label Set</option> 
								<option value='VUID'".($_POST['id_lookup'] == 'VUID'? ' selected ':NULL).">Veterans Health(SAB:VANDF)</option> 
								<option value='NDC'".($_POST['id_lookup'] == 'NDC'? ' selected ':NULL).">Natn. Drug Code (NDC)</option> 
								<option value='NUI'".($_POST['id_lookup'] == 'NUI'? ' selected ':NULL).">Natn. Drug File (NUI) (SAB:NDFRT)</option>
								<option value='GCN_SEQNO'".($_POST['id_lookup'] == 'GCN_SEQNO'? ' selected ':NULL).">Generic Code(SAB:NDDF)</option> 
								<option value='GFC'".($_POST['id_lookup'] == 'GFC'? ' selected ':NULL).">Generic Formula(SAB:MMX)</option> 
								<option value='GPPC'".($_POST['id_lookup'] == 'GPPC'? ' selected ':NULL).">Generic Product Packaging(SAB:MDDB)</option> 
								<option value='AMPID'".($_POST['id_lookup'] == 'AMPID'? ' selected ':NULL).">Alchemy Marketed(SAB:GS)</option> 
								<option value='HIC_SEQN'".($_POST['id_lookup'] == 'HIC_SEQN'? ' selected ':NULL).">Ingredient Identifier (SAB:NDDF)</option> 
								<option value='LISTING_SEQ_NO'".($_POST['id_lookup'] == 'LISTING_SEQ_NO'? ' selected ':NULL).">Unique id #(SAB:MTHFDA)</option> 
								<option value='MESH'".($_POST['id_lookup'] == 'MESH'? ' selected ':NULL).">Subject Medical Headings (SAB:MSH)</option> 
								<option value='MMSL_CODE'".($_POST['id_lookup'] == 'MMSL_CODE'? ' selected ':NULL).">TTY+ Multum Mediasource(SAB:MMSL)</option> 
								<option value='SNOMEDCT'".($_POST['id_lookup'] == 'SNOMEDCT'? ' selected ':NULL).">SNOMED CT(SAB:SNOMEDCT)</option> 
							</select>
							</li>			
						<li><input type='text' title='Search Query' name='searchTerm' ". ($_POST['searchTerm']?' value = "'. $_POST['searchTerm'] . '" ':NULL)." /></li>
						<li><input type ='submit' title='Search' /></li>
						<li>
							<fieldset id ='records_type'>
								<input type='checkbox' name='drugs' id='drugs' ".($_POST['drugs'] != 'on' ?  NULL :' checked ' )."/><label for='drugs'>Search Drugs Only</label>
							</fieldset>
						</li>
						<li><a href='./' alt='Simple Search'>Simple Search</a></li>
					</ul>
					":"<ul><li><input type='text' title='Search Query' name='searchTerm' ". ($_POST['searchTerm']?' value = "'. $_POST['searchTerm'] . '" ':NULL)." />
						<input type ='submit' title='Search' /><a href='ndf.php'>Search NDF</a></li></ul>")."
        </form>
        </div>
		<div id ='content'>";

// make a postform class  that includes the html generator !!
// are get vars really only limited to the (script)page that calls them? Will have to make it all object oriented...
 if(!$_POST['searchTerm']){
         if($_GET['u'])
                $_POST['u'] = $_GET['u'];
         elseif($_GET['r'])
                $_POST['r'] = $_GET['r'];
  		 elseif($_GET['s'])
  				$_POST['searchTerm']= $_GET['s']; 
  }


if($_POST['searchTerm'] || $_GET['r'] || $_GET['u']){
		
if($_POST['id_lookup'] == '') unset($_POST['id_lookup']);
        //if someone checks all the boxes it is the same as checking none of them
        if($_POST['related'] && count($_POST['related']) == 13) unset($_POST['related']);
        if($_POST['id_lookup'] && $_POST['extra'] != '') unset ($_POST['extra']);
        
 	require("../config.php");
	require("../lib/obcer.php");
	include("../lib/rxNormRef.php");
}
include('footer.html');
?>

