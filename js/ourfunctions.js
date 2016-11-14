$(function() {
	$('input').change(function() {
		getCourses();
	});
	$('select').change(function() {
		getCourses();
	});
	$("#target_term").change(function(){
    //when the target term is changed set flag to re-pull from the gimme
	cacheFlag="true";
	});
    $("#target_program").change(function(){
    //when the program type is changed set flag to re-pull from the gimme
	cacheFlag="true";
	});
	getCourses();
});


//set the row color by seats
function rowStyle(row, index) {
	var numbers = row.reg.split(/ \/ /g);
	var classes = '';
	if(numbers[1] == 0) {
		classes = 'info';
	} else if(numbers[1] - numbers[0] > 5) {
		classes = 'success';
	} else if(numbers[1] - numbers[0] <= 0) {
		classes = 'danger';
	} else {
		classes = 'warning';
	}
	return {
		classes: classes
	}
}

function getCourses() {
    //prepare checkboxes for php processing
	var instructionalMethods = '';
	$('#target_im input:checked').each(function() {
		instructionalMethods = instructionalMethods + ($(this).attr('id')) +',';
	});
	var meetingDays = '';
	$('#target_days input:checked').each(function() {
		meetingDays = meetingDays + ($(this).attr('id'));
	});
	var hideFull = $('#hideFullCheck').prop('checked');

	var filters = {
		program: $('#target_program').val(),
		term: $('#target_term').val(),
		department: $('#target_dept').val(),
		instructionalMethods: instructionalMethods,
		meetingDays: meetingDays,
		beginTime: $('#beg_time').val(),
		endTime: $('#end_time').val(),
		hideFull: hideFull
	};
    //request json spreadsheet from fetch.php
	$.ajax({
		url: "includes/php/fetch.php",
		data: filters,
		type: 'POST',
		dataType: 'json',
		beforeSend: function() {
			$('#coursesTable').bootstrapTable('showLoading');
		},
		success: function(data) {
			$('#coursesTable').bootstrapTable('hideLoading');
			$('#coursesTable').bootstrapTable('load', data);
		},
		error: function(jqXHR, status, error) {
			//Log error to console
			//Apologize to the user
		}
	});
}

var refresh;
function refreshInterval() {
	var refreshPeriod = $('#target_refresh').val();
	if(refreshPeriod === 'never') {
		window.clearInterval(refresh);
	} else {
		refresh = setInterval(function() {
			getCourses();
		}, refreshPeriod * 1000);
	}
}

function regSorter(a,b){
	var arrayOne = a.split("/")
	var arrayTwo= b.split("/")
	
	if ((parseFloat(arrayOne[0])/parseFloat(arrayOne[1])) >(parseFloat(arrayTwo[0])/parseFloat(arrayTwo[1]))){
		return 1;
	}
	if ((parseFloat(arrayOne[0])/parseFloat(arrayOne[1])) < (parseFloat(arrayTwo[0])/parseFloat(arrayTwo[1]))){
		return -1;
	}
	return 0;
}