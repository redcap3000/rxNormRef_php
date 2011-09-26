<?php
include('header.html');
error_reporting(0);
echo' <link rel="stylesheet" type="text/css" href="css/ndf.css" />';



if($_GET['n'] ){
	$_POST['nui'] = $_GET['n'];
}


echo 
		'            
	</head>
	<body>
  	<div id ="page">
    	<div id = "header">
    			<h1>NDF-RT Concept Browser</h1>
				<img src="img/new_logo.gif" alt="rxnix logo"/>'.
		
	  		
	  		'<a href="./index.php">Search RxNorm</a>
	  		</div>
		
		<div id ="ndf_content">	
			<ul class ="ndf_menu">
				<li><a href="ndf.php?n=N0000000001">Pharmaceutical Preparations</a></li> 
				<li><a href="ndf.php?n=N0000000002">Chemical Ingredients</a></li> 
				<li><a href="ndf.php?n=N0000000003">Clinical Kinetics</a></li> 
				<li><a href="ndf.php?n=N0000000004">Diseases, Manifestations Or Physiologic States</a></li>  
				<li><a href="ndf.php?n=N0000000006"> Mental Disorders And Manifestations</a></li>  
				<li><a href="ndf.php?n=N0000000007"> Infectious Diseases </a></li>   
			
			</ul>
			';

// to do move cacher here ...

// allow get variables to begin NUI lookups/convert to nui id's with the ndf post var
 if($_POST['nui']) {
 	require("../config.php");
	require("../lib/obcer.php");
 	require("../lib/rxNormRef.php");
}
?>
		</div>
			
	</div>	
	
				<div id="help">
	<ul>
	<li>
						
							<p>RxNix is a simple semantic medications tool that intefaces with RxNorm - a database developed at the <a href="http://www.nlm.nih.gov/">National Library of Medicine</a>. Begin by selecting a topic from the menu above.</p>
						<p><a href="http://www.nlm.nih.gov/research/umls/rxnorm/index.html">About RxNorm</a><br><a href="http://www.nlm.nih.gov/research/umls/quickstart.html">UMLS Quickstart Guide</a></p>
					</li>
							<li><img src="img/UMLS_header_newtree.gif" alt="Unified Medication Language System" title="UMLS Logo" align="middle"><img src="http://rxnix.com/img/couchdb-icon-64px.png" alt="Unified Medication Language System" title="UMLS Logo" align="middle"><img class="bg_black" src="img/nlm-logo-white.png" alt="National Library of Medicine Logo" title="NLM Logo" align="middle"><p>


Built with <a href="https://github.com/codeforamerica/rxNorm_php">rxNorm_php</a> api library and <a href="https://github.com/codeforamerica/rxNormRef_php">rxNormRef_php</a> maintained  by <a href="http://codeforamerica.org">Code For America.</a>
</p></li>
				</ul>
			</div>
	
	
	
		</body>
</html>
