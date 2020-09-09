<html>
<head>
<style>
p.inline {display: inline-block;}
span { font-size: 13px;}
</style>
<style type="text/css" media="print">
    @page 
    {
        size: auto;   /* auto is the initial value */
        margin: 0mm;  /* this affects the margin in the printer settings */

    }
</style>
</head>
<body onload="window.print();">
	<div style="margin-left: 5%">
		<?php
		include 'barcode128.php';
		// $product = $_POST['product'];
		$reference  = $_GET['reference'];
		$product_id = $_GET['productId'];
		$rate       = $_GET['price'];
		// print_r($product_id)

		for($i=1;$i<=25;$i++){
			// echo "<p class='inline'><span ><b>$product</b></span>".bar128(stripcslashes($_POST['product_id']))."<span ><b>Prix: ".number_format($rate, 0, ',', '.')." Frs </b><span></p>&nbsp&nbsp&nbsp&nbsp";
			echo "<p class='inline'><span ><b>$reference</b></span>".bar128(stripcslashes($product_id))."<span ><b>Prix: ".number_format($rate, 0, ',', '.')." Frs </b><span></p>&nbsp&nbsp&nbsp&nbsp";
		}

		?>
	</div>
</body>
</html>