function loadModalContent(id) {
	var c=componentArr[id];
	var setText=function(t, s) {
		if ($.trim(s)=='') {
			if (!$('#'+t+'TxtDiv').hasClass('hidden')) $('#'+t+'TxtDiv').addClass('hidden');
		} else {
			if ($('#'+t+'TxtDiv').hasClass('hidden')) $('#'+t+'TxtDiv').removeClass('hidden');
			$('#'+t+'Txt').html(s);
		}
		
	}
	$('#titleTxt').html(c.displayName);
	$('#componentImg').attr('src', c.imageURL);
	var attr=['name',
			  'componentName',
			  'serialNumber',
			  'installationDate',
			  'typeName',
			  'manufacturer',
			  'modelNumber',
			  'partsWarrantyGuarantor',
			  'partsWarrantyDuration',
			  'labourWarrantyGuarantor',
			  'labourWarrantyDuration']
	$.each(attr, function(key, value) {
		setText(value, c[value]);
	});
}
