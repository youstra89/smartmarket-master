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
		// $product_id = $_POST['product_id'];
		// $rate = $_POST['rate'];

		// for($i=1;$i<=$_POST['print_qty'];$i++){
		// 	echo "<p class='inline'><span ><b>$product</b></span>".bar128(stripcslashes($_POST['product_id']))."<span ><b>Prix: ".number_format($rate, 0, ',', ' ')." F</b><span></p>&nbsp&nbsp&nbsp&nbsp";
		// }

		$tab = [
			0 => [
				"product" => "Les singes",
				"product_id" => "12546893",
				"rate" => "5000"
			],
			1 => [
				"product" => "L'ordonnance",
				"product_id" => "48795623",
				"rate" => "3500"
			],
			2 => [
				"product" => "De retour",
				"product_id" => "9786413568",
				"rate" => "8500"
			]
		];
		// $product = $_POST['product'];
		// $product_id = $_POST['product_id'];
		// $rate = $_POST['rate'];

		foreach ($tab as $key => $value) {
			for($i=1;$i<=20;$i++){
				echo "<p class='inline'><span ><b>".$value["product"]."</b></span>".bar128(stripcslashes($value['product_id']))."<span ><b>Prix: ".number_format($value['rate'], 0, ',', ' ')." F</b><span></p>&nbsp&nbsp&nbsp&nbsp";
			}
			echo"<br>";
		}

		?>
	</div>
</body>
</html>