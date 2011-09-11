<?php
include('header.html');
error_reporting(0);
echo' <link rel="stylesheet" type="text/css" href="css/ndf.css" />';



if($_GET['n'] ){
	$_POST['nui'] = $_GET['n'];$_POST['findConcepts'] = 'on';
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
				<li><a href="ndf.php?n=N0000000008"> Absorption</a></li>  
				<li><a href="ndf.php?n=N0000000009"> Affected By Food / Other Nutrients</a></li>  
			</ul>
			';

// to do move cacher here ...

// allow get variables to begin NUI lookups/convert to nui id's with the ndf post var
 if($_POST['nui']) {
 	require("../config.php");
	require("../lib/obcer.php");
 	require("../lib/rxNormRef.php");
}

include('footer.html');
