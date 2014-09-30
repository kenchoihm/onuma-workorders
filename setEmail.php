<?php
include('config.php');
include('include.php');
if (isset($_POST['email'])) {
	$_GET['email']=$_POST['email'];
	if (isset($_GET['workOrderID'])) {
		redirect('technician.php?'.getParams(array('workOrderID'=>$_GET['workOrderID'])));
	} else {
		redirect('list.php?'.getParams(array('workOrderID'=>NULL)));
	}
}
include('../plan/authconfig.php');
include('../plan/OPS/include.php');

$site=new BIMSite($_GET['siteID']);
$siteInfo=$site->getSiteInfo();
$project=new BIMProject($site->getProjectID());
$workOrderSetting=$siteInfo->getWorkOrderSetting();
?>
<!DOCTYPE html>
<html lang="en">
<head>
		<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width">
<title>Assignment Details</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
<link rel="stylesheet" type="text/css" href="css/workorder.css">
<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
<script type="text/javascript" src="js/bootstrap.min.js"></script>
<style>
<?php if (trim($workOrderSetting->getLogoURL())!='') { ?>
.headerTxtDiv{padding-left:10px;}
<?php } ?>
</style>
</head>
<body>


<div class="container">
<ul class="breadcrumb">
	<li></li>
    <div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
    </div>
</ul>
	<div class="logo col-xs-12">
    <?php if (trim($workOrderSetting->getLogoURL())!='') { ?>
    	<div class="pull-left">
        <img src="<?php echo $workOrderSetting->getLogoURL(); ?>" border="0" />
        </div>
    <?php } ?>
    	<div class="pull-left headerTxtDiv">
            <h3 class="headerTxt1"><?php echo html($project->getName()); ?></h3>
            <h4 class="headerTxt2"><?php echo html($site->getName()); ?></h4>
        </div>
	</div>
    <div class="clearfix"></div>
    
<div class="panel-group">
        <div>
        	<p class="text-center">Please enter your email address:</p>
        </div>
            <form class="form-horizontal" method="post">
                <div class="form-group" style="margin-bottom:10px;">
	            <input type="email" name="email" value="<?php echo $_GET['email']; ?>" placeholder="Email address" class="col-xs-offset-3 col-xs-6" />
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <div class="col-xs-12 text-center">
                        <button type="submit" class="btn btn-default" style="width:150px;">Submit</button>
                    </div>
                </div>
            </form>
        </div>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</div>
</body>
</html>