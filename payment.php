<?php

include( dirname( __FILE__ )."/../../config/config.inc.php" );
include( dirname( __FILE__ )."/../../header.php" );
include( dirname( __FILE__ )."/bankmelli.php" );
	$bankmelli= new bankmelli();
	echo '<div class="">
	<p><img width="16" height="16" src="./loader.gif"/>'.$bankmellat->l('Please Wait...').'</p>
	</div>';
	$bankmelli->execPayment($cart);
	
include_once( dirname( __FILE__ )."/../../footer.php" );
?>

