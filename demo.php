<?php
$param='<?php echo $param; ?>';
if (is_file('C:\\htdocs\\wo\\config.php')) {
	$param='sysID=3&siteID=34';
}

?><!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width">
	<title>Onuma System Work Orders System Demo Page</title>
	<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
	<link rel="stylesheet" type="text/css" href="css/workorder.css">
	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/jquery.cookie.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
	<style>
	</style>
</head>
<body>


<div class="container">
	<div class="logo col-xs-12">
    	<div class="pull-left" style="margin-right:10px;">
        	<img src="../plan/logo/onumalogo_50.png" border="0">
        </div>
    	<div class="pull-left headerTxtDiv">
            <h1 class="headerTxt1">Onuma System Work Orders System Demo Page</h1>
        </div>
	</div>
    <div class="clearfix"></div>
    <p>
		This page is to demonstrate the Work Orders module of Onuma System.
		<br />
		<br />
		In this demo, the system is used by the college (Solano Community College - Vallejo Center)
		and it allows the college staff or students to submit any building work orders via the web page.
	</p>
	<h2>
	Requester
	</h2>
    <p>
		When a college staff or student spots any deficiencies in the campus that requires maintaince work,
		he/she can submit a new work order from <a href="request.php?<?php echo $param; ?>" target="workOrderRequest"><b>this page</b></a>.
	</p>
	<button onclick="window.open('request.php?<?php echo $param; ?>', 'newWorkOrder');" class="btn btn-default">
		Request New Work Order
	</button>
	<h2>
	Technician
	</h2>
    <p>
		The new added work order will then be automatically forwarded to the technician.
		The technician can access the work orders from <a href="list.php?<?php echo $param; ?>&email=ken%40onuma.com" target="technician"><b>this page</b></a>,
		where he can log down the maintaince work that he does for the work orders,
		and upon the completion of the work order, he submit it as completed.
	</p>
	<button onclick="window.open('list.php?<?php echo $param; ?>&email=ken%40onuma.com', 'newWorkOrder');" class="btn btn-default">
		View Technician's Work Order
	</button>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</body>
</html>