<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" dir="ltr">
        <head>
                <title>RxNIX</title>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta http-equiv="Content-Style-Type" content="text/css" />
			<?php
			error_reporting(0);
			echo '<link rel="stylesheet" type="text/css" href="css/'.($_POST['css_wide']?'wide.css':'fixed_field.css').'" />
                
        </head>
        <body>
        <div id ="page">
        <div id = "header">
             <img src="img/rxnix_logo.gif" alt="rxnix logo"/>
	<form method="post" action="" class="main_form">
					<fieldset id="simple_form">
					<legend>Concept Search</legend>
			<input type="text" title="Search Query" name="searchTerm"  '. ($_POST['searchTerm']?' value = "'. $_POST['searchTerm'] . '" ':NULL).' /><input type ="submit" title="Search" />
			
			<a href="advanced.php" title="Filter Concepts">Advanced Search</a>
					</fieldset>
	  </form>
        </div>
		<div id ="content">';
// consider making pretty urls with mod_url...
 if(!$_POST['searchTerm']){
	 if($_GET['u'])
		$_POST['u'] = $_GET['u'];
	 elseif($_GET['r'])
		$_POST['r'] = $_GET['r'];
  }	
 if($_POST['searchTerm'] || $_GET['r'] || $_GET['u'])	require('lib/rxNormRef.php'); 
 
 ?>
		</div>
				<div id = 'help'>
				<ul>
					<li><img class='bg_black' src="img/nlm-logo-white.png" alt = "National Library of Medicine Logo" title="NLM Logo" align="middle"/>
						
						<em>What is RxNix</em>
						<p>RxNix is a simple semantic medications tool that intefaces with RxNorm - a database developed at the <a href="http://www.nlm.nih.gov/">National Library of Medicine</a>.</p>
						<a href="http://www.nlm.nih.gov/research/umls/rxnorm/index.html">About RxNorm</a>
						</li>
						
					<li><img src="img/UMLS_header_newtree.gif" alt= "Unified Medication Language System" title="UMLS Logo" align="middle"/>
						<em>UMLS</em>
						
						<p>RxNorm uses the <a href="http://www.nlm.nih.gov/research/umls/">Unified Medication Language System</a> to enable a variety of systems to communicate with each other.</p> <a href="http://www.nlm.nih.gov/research/umls/quickstart.html">UMLS Quickstart Guide</a>
					</li>
						
					<li>
						<em>Searching</em>
						<p>You may begin by entering an ingredient, drug name, concept or other in the Search field. The Advanced search page allows filtering on concept titles, <strong>and id conversions from one system (UMLSCUI/NDC/UNII and others) to RXCUI</strong></p>
					</li>
			
					<li>
					<em>About</em>
						<p>Built with <a href="https://github.com/codeforamerica/rxNorm_php">rxNorm_php</a> api library and <a href="https://github.com/codeforamerica/rxNormRef_php">rxNormRef_php</a> maintained  by <a href="http://codeforamerica.org">Code For America.</a>
							</p>
							<img src ="http://www.totalvalidator.com/images/valid_s_us508.gif" alt="Total Validator Badge" title="Validation Badge"/>
					</li>
		
				</ul>
			</div>
	</div>		
		</body>
</html>
