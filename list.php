<?php
include('config.php');
include('include.php');
include('../plan/authconfig.php');
include('../plan/OPS/include.php');
if (isset($_POST['email'])) {
	if (isset($_GET['workOrderID'])) {
		redirect('technician.php?'.getParams(array('workOrderID'=>$_GET['workOrderID'])));
	} else {
		redirect('list.php?'.getParams(array('workOrderID'=>NULL)));
	}
}
if ((isset($_GET['email']))&&($_GET['email']=='')) redirect('setEmail.php?'.getParams());
$site=new BIMSite($_GET['siteID']);
$siteInfo=$site->getSiteInfo();
$project=new BIMProject($site->getProjectID());
$workOrderSetting=$siteInfo->getWorkOrderSetting();
$statusArr=array("Assigned","Work in progress","Completed");

if ($_GET['showCompleted']==0 && $_GET['filter']=='Completed Confirmed') $_GET['filter']='';
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
	<li class="active">My Task Assignments</li>
    <div class="pull-right" style="padding-left:10px;">
        <a href="../plan/OpsBug.php?<?php echo getParams(); ?>" target="bugs">Bugs</a>
    </div>
	<div class="clearfix">
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
    <form class="form-horizontal" style="padding-right:5px;">
    <div>
        Work orders assigned to <b><?php echo $_GET['email']; ?></b> (<a href="list.php?<?php echo getParams(array('email'=>'', 'workOrderID'=>'')); ?>">Log out</a>):
    </div>
    <div class="checkbox">
        <label><input type="checkbox" name="showCompleted"<?php if ($_GET['showCompleted']==1) echo ' checked="checked"'; ?> onClick="
            	document.location='list.php?<?php echo getParams(array('showCompleted'=>($_GET['showCompleted']==1?0:1))); ?>';
            " /> Show work orders with "Completed confirmed" status</label>
    </div>
    <div class="checkbox" style="margin-bottom:10px;">
        <label><input type="checkbox" name="showAll"<?php if ($_GET['showAll']==1) echo ' checked="checked"'; ?> onClick="
            	document.location='list.php?<?php echo getParams(array('showAll'=>($_GET['showAll']==1?0:1))); ?>';
            " /> Show work orders for the entire studio</label>
    </div>
    <div style="margin-bottom:10px;">
		<div class="pull-left" style="margin-top:8px;">
        	Show only the work orders with the following status: &nbsp;
		</div>
		<div class="pull-left">
		<select name="statusFilter" class="form-control" onChange="
            	document.location='technician.php?<?php echo getParams(); ?>&filter='+this.value;
		" style="width:200px;">
			<option value=""<?php if ($_GET['filter']=='') echo ' selected="selected"'; ?>>(No filters)</option>
			<?php foreach ($statusArr as $status) { ?>
			<option value="<?php echo $status; ?>"<?php if ($_GET['filter']==$status) echo ' selected="selected"'; ?>><?php echo $status; ?></option>
			<?php } ?>
			<?php if ($_GET['showCompleted']==1) { $status='Completed Confirmed'; ?>
			<option value="<?php echo $status; ?>"<?php if ($_GET['filter']==$status) echo ' selected="selected"'; ?>><?php echo $status; ?></option>
			<?php } ?>
		</select>
		</div>
		<div class="clearfix"></div>
    </div>
	
	 
    <div class="table-responsive">
        <table class="table table" style="margin-bottom:0px;">
            <thead>
                <tr>
                    <th class="col-xs-2 active"><a href="list.php?<?php echo getParams(array('orderBy'=>'assignedPriority', 'order'=>(($_GET['orderBy']=='assignedPriority' && $_GET['order']=='ASC')?'DESC':'ASC'))); ?>">Priority</a> <?php
						if ($_GET['orderBy']=='assignedPriority') { echo '<span class="glyphicon glyphicon-sort-by-attributes'.($_GET['order']=='ASC'?'':'-alt').'"></span>';}  
					?></th>
                    <th class="col-xs-2 active"><a href="list.php?<?php echo getParams(array('orderBy'=>'requestNumber', 'order'=>(($_GET['orderBy']=='requestNumber' && $_GET['order']=='ASC')?'DESC':'ASC'))); ?>">Request #</a> <?php
						if ($_GET['orderBy']=='requestNumber') { echo '<span class="glyphicon glyphicon-sort-by-attributes'.($_GET['order']=='ASC'?'':'-alt').'"></span>';}  
					?></th>
                    <th class="col-xs-2 active"><a href="list.php?<?php echo getParams(array('orderBy'=>'requestDescription', 'order'=>(($_GET['orderBy']=='requestDescription' && $_GET['order']=='ASC')?'DESC':'ASC'))); ?>">Request Description</a> <?php
						if ($_GET['orderBy']=='requestDescription') { echo '<span class="glyphicon glyphicon-sort-by-attributes'.($_GET['order']=='ASC'?'':'-alt').'"></span>';}  
					?></th>
                    <th class="col-xs-2 active"><a href="list.php?<?php echo getParams(array('orderBy'=>'status', 'order'=>(($_GET['orderBy']=='status' && $_GET['order']=='ASC')?'DESC':'ASC'))); ?>">Status</a> <?php
						if ($_GET['orderBy']=='status') { echo '<span class="glyphicon glyphicon-sort-by-attributes'.($_GET['order']=='ASC'?'':'-alt').'"></span>';}  
					?></th>
                    <th class="col-xs-2 active"><a href="list.php?<?php echo getParams(array('orderBy'=>'requestDate', 'order'=>(($_GET['orderBy']=='requestDate' && $_GET['order']=='ASC')?'DESC':'ASC'))); ?>">Date</a> <?php
						if ($_GET['orderBy']=='requestDate') { echo '<span class="glyphicon glyphicon-sort-by-attributes'.($_GET['order']=='ASC'?'':'-alt').'"></span>';}  
					?></th>
                </tr>
            </thead>
            <tbody>
		<?php
		$orderBy="assignedDate DESC, assignedPriority DESC, requestNumber, requestDescription";
		if (isset($_GET['orderBy'])) {
			$orderBy=$_GET['orderBy'];
			if (isset($_GET['order'])) $orderBy.=' '.$_GET['order'];
		} else {
			$orderBy=" ID DESC";
		}
		$where='';
		if ($_GET['showCompleted']==0) $where.=' AND status!="Completed Confirmed"';
		if ($_GET['showAll']==0) $where.=' AND siteID='.$site->getID();
		if ($_GET['filter']!='') $where.=' AND status="'.addslashes($_GET['filter']).'"';
		$sql="SELECT WorkOrder.* FROM WorkOrder
		LEFT JOIN onuma_plan.contact ON assignedContactID=contact.ID
		LEFT JOIN Bldg ON WorkOrder.bldgID=Bldg.ID
		LEFT JOIN Site ON siteID=Site.ID
		WHERE email='".$_GET['email']."' ".$where." AND Site.ID IS NOT NULL".
		" ORDER BY ".$orderBy;
		?>
        <?php
		$conn=new Conn();
		$conn->execute($sql);
		$workOrderCounter=0;
		while ($conn->next()) {
			$workOrder=new WorkOrder();
			$workOrder->populate($conn->row);
			$workOrderCounter++;
			?>
            <tr>
            	<td valign="top"><?php echo $priorityArr[$workOrder->getAssignedPriority()]; ?></td>
                <td valign="top"><a href="technician.php?<?php echo getParams(array('workOrderID'=>$workOrder->getID())); ?>"><?php echo htmlentities($workOrder->getRequestNumber()); ?></a></td>
            	<td valign="top"><?php echo htmlentities($workOrder->getRequestDescription()); ?></td>
            	<td valign="top"><?php echo $workOrder->getStatus(); ?></td>
            	<td valign="top"><?php echo date('m/d/y', strtotime($workOrder->getAssignedDate())); ?></td>
            </tr>
            <?php
		}
		if ($workOrderCounter==0) {
				?>
				<tr>
					<td colspan="5" align="center">(No tasks to display)</td>
				</tr>
				<?php
		}
		?>
            </tbody>
        </table></div>
            </form>
        </div>
        <p class="text-center">&copy; <?php echo date('Y'); ?> Onuma</p> 
</div>
</div>
</body>
</html>