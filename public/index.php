<?php
error_reporting(0);
include('header.html');                	
echo '<link rel="stylesheet" type="text/css" href="css/main.css"'." />
		</head>
        <body>
        <div id ='page'>
        <div id = 'header'>
        <img src='img/new_logo.gif' alt='rxnix logo'/>
           
    	<form method='post' action='' class='main_form'>
    	<ul><li><input type='text' title='Search Query' name='searchTerm' ". ($_POST['searchTerm']?' value = "'. $_POST['searchTerm'] . '" ':NULL)." />
						<input type ='submit' title='Search' /><a href='ndf.php'>Search NDF</a></li></ul>
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

