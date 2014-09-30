<?php
session_start();
$priorityArr=array("(Not specified)", "Urgent", "Immediate", "Today", "Within a week", "When possible");
$statusArr=array("Assigned","Work in progress","Completed");
$getParams=array('sysID', 'siteID', 'workOrderID', 'email', 'showAll', 'showCompleted', 'filter', 'orderBy', 'order');

function getParams($overwriteParams=array()) {
	global $getParams;
	$getArr=array();
	foreach ($_GET as $ind=>$val) {
		if (in_array($ind, $getParams)) {
			if (!isset($overwriteParams[$ind])) {
				if ($val=='') continue;
				array_push($getArr, $ind.'='.addslashes($val));
			}
		}
	}
	foreach ($overwriteParams as $ind=>$val) {
		if ($val===NULL) continue;
		array_push($getArr, $ind.'='.addslashes($val));
	}
	$s=implode('&', $getArr);
	return $s;
}
function printRequiredHiddenParam() {
	global $getParams;
	foreach ($_GET as $ind=>$val) {
		if (in_array($ind, $getParams)) {
            echo "\r\n".'<input type="hidden" name="'.$ind.'" value="'.(html($val)).'" />';
		}
	}
	echo "\r\n";
}


function redirect($l) {
	header('location: '.$l);
	exit;
}
if ($_GET['openin']!='') {
	if (!isChrome()) {
	?><!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">
<meta charset="utf-8">
<title>Redirect</title>
<link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
<script type="text/javascript" src="http://code.jquery.com/jquery.min.js"></script>
<style>
body{ background-color:#1c2132;}
.content{ background-color:#FFF; margin: 5px; padding: 10px; }
.logo{ padding: 10px 0px 15px 0px; }
.headerTxt{padding-left:10px;}
.projectTxt{ margin-top:0px; margin-bottom:5px;}
.siteTxt{ margin-top:5px; margin-bottom:0px;}

</style>
<script language="JavaScript"><!--
$(function() {
	if (location.href.toLowerCase().indexOf('https')===0) {
		location.href='googlechrome'+location.href.substring(5);
	} else {
		location.href='googlechrome'+location.href.substring(4);
	}
});
--></script>
</head>
<body>
<div class="content">
    <h4>Reopening in Chrome
    </h4>
</div>
</body>
</html>
<?php
	exit;
	}
}
function printEquipmentModalPanel() { ?>
<div id="componentDetailsModal" class="modal fade">
<div class="modal-dialog">
    <div class="modal-content form-horizontal">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title" id="titleTxt"></h4>
        </div>
        	<div class="col-xs-12 text-center">
            <img id="componentImg" style="width:100px;" />
            </div>
        <div class="form-group" id="nameTxtDiv">
            <label class="control-label col-xs-6">Component:</label>
            <div class="col-xs-6"><p class="form-control-static" id="nameTxt"></p></div>
        </div>
        <div class="form-group" id="componentNameTxtDiv">
            <label class="control-label col-xs-6">Unique Mark:</label>
            <div class="col-xs-6"><p class="form-control-static" id="componentNameTxt"></p></div>
        </div>
        <div class="form-group" id="serialNumberTxtDiv">
            <label class="control-label col-xs-6">Serial Number:</label>
            <div class="col-xs-6"><p class="form-control-static" id="serialNumberTxt"></p></div>
        </div>
        <div class="form-group" id="typeNameTxtDiv">
            <label class="control-label col-xs-6">Type Name:</label>
            <div class="col-xs-6"><p class="form-control-static" id="typeNameTxt"></p></div>
        </div>
        <div class="form-group" id="manufacturerTxtDiv">
            <label class="control-label col-xs-6">Manufacturer:</label>
            <div class="col-xs-6"><p class="form-control-static" id="manufacturerTxt"></p></div>
        </div>
        <div class="form-group" id="modelNumberTxtDiv">
            <label class="control-label col-xs-6">Model Number:</label>
            <div class="col-xs-6"><p class="form-control-static" id="modelNumberTxt"></p></div>
        </div>
        <div class="form-group" id="installationDateTxtDiv">
            <label class="control-label col-xs-6">Installation Date:</label>
            <div class="col-xs-6"><p class="form-control-static" id="installationDateTxt"></p></div>
        </div>
        <div class="form-group" id="partsWarrantyGuarantorTxtDiv">
            <label class="control-label col-xs-6">Parts Warranty Guarantor:</label>
            <div class="col-xs-6"><p class="form-control-static" id="partsWarrantyGuarantorTxt"></p></div>
        </div>
        <div class="form-group" id="partsWarrantyDurationTxtDiv">
            <label class="control-label col-xs-6">Parts Warranty Duration:</label>
            <div class="col-xs-6"><p class="form-control-static" id="partsWarrantyDurationTxt"></p></div>
        </div>
        <div class="form-group" id="labourWarrantyGuarantorTxtDiv">
            <label class="control-label col-xs-6">Labor Warranty Guarantor:</label>
            <div class="col-xs-6"><p class="form-control-static" id="labourWarrantyGuarantorTxt"></p></div>
        </div>
        <div class="form-group" id="labourWarrantyDurationTxtDiv">
            <label class="control-label col-xs-6">Labor Warranty Duration:</label>
            <div class="col-xs-6"><p class="form-control-static" id="labourWarrantyDurationTxt"></p></div>
        </div>
    </div>
</div>
</div>
<?php
}
function isMobile() {
	if (preg_match('/mobile/i', $_SERVER['HTTP_USER_AGENT'])) return true;
	return false;
}

function isIOS() {
	if (preg_match('/iPad|iPhone|iPod/i', $_SERVER['HTTP_USER_AGENT'])) return true;
	return false;
}
function isAndroid() {
	if (preg_match('/Android/i', $_SERVER['HTTP_USER_AGENT'])) return true;
	return false;
}

function isChrome() {
	if (preg_match('/Chrome/i', $_SERVER['HTTP_USER_AGENT'])) return true;
	if (preg_match('/CriOS/i', $_SERVER['HTTP_USER_AGENT'])) return true;
	return false;
}

?>