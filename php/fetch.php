<?php
header('Content-type: application/json');
//check to see if a new year or program has been chosen or if the cache is empty
if((!(isset($_POST['cacheFlag']))) || ($_POST['cacheFlag'] = "true") || (time() > ($timeStamp + 300))){
	list($year, $sess) = explode("|", $_POST['term']);
	$program = $_POST['program'];
	$coursesArray = Parse("http://gimme.letu.edu/courseschedule/$program/full/$year/$sess");
	$_POST['cacheFlag'] = "false";
	$timeStamp = time();
}
$jsonCourses = array();
//fill json array from XML data pulled from the gimme
foreach($coursesArray['CourseSection'] as $course) {
	$jsonCourse = array();
	$jsonCourse['coursenumber'] = $course['coursenumber'];
	$jsonCourse['sectionnumber'] = $course['sectionnumber'];
	$jsonCourse['coursetitle'] = $course['coursetitle'];
	$jsonCourse['hours'] = intval($course['coursehours']);
	$jsonCourse['fee'] = "$" . intval($course['coursefees']);
	$jsonCourse['reg'] = $course['currentnumregistered'] . ' / ' . $course['maxsize'];
    //check and see if more than one meeting time or type exists
	if(array_key_exists('profname', $course['meeting'])) {
		AddMeetingInfo($jsonCourse, $course['meeting']);
		if(Filter($jsonCourse)){
			array_push($jsonCourses, $jsonCourse);
		}
	} else {
			$tempjsonCourse=$jsonCourse;
			AddMeetingInfo($jsonCourse, $course['meeting'][0]);
			$firstFlag = Filter($jsonCourse);			
			
			$secondFlag = false;
			for($x=1;$x < count($course['meeting']);$x++){
				AddMeetingInfo($tempjsonCourse, $course['meeting'][$x]);
				if ($secondFlag == false){
					$secondFlag = Filter($tempjsonCourse);
				}			
				$jsonCourse['meetingtypetext']= $jsonCourse['meetingtypetext'] ."<br>". $tempjsonCourse['meetingtypetext'];
				$jsonCourse['meetingdaysofweek'] = $jsonCourse['meetingdaysofweek'] ."<br>". $tempjsonCourse['meetingdaysofweek'];
				$jsonCourse['times'] = $jsonCourse['times'] ."<br>". $tempjsonCourse['times'];
				$jsonCourse['meetingbuilding'] = $jsonCourse['meetingbuilding'] ."<br>". $tempjsonCourse['meetingbuilding'];
				$jsonCourse['meetingroom'] = $jsonCourse['meetingroom'] ."<br>". $tempjsonCourse['meetingroom'];
			}
			if($firstFlag || $secondFlag){
				array_push($jsonCourses,$jsonCourse);
			}
	}
}
//return the filled spreadsheet to ourfunctions.js
echo json_encode($jsonCourses);

function Parse ($url) {
	$fileContents = file_get_contents($url);
	$fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
	$fileContents = trim(str_replace('"', "'", $fileContents));
	$simpleXml = simplexml_load_string($fileContents);
	return json_decode(json_encode($simpleXml), TRUE);
}

function AddMeetingInfo(&$jsonCourse, $meeting) {
	$jsonCourse['profname'] = $meeting['profname'];
	$jsonCourse['meetingtypetext'] = $meeting['meetingtypetext'];
	$jsonCourse['meetingdaysofweek'] = $meeting['meetingdaysofweek'];
	if($meeting['meetingstarttime'] == 0 && $meeting['meetingendtime'] == 0){
		$jsonCourse['times'] = "TBA";
	} else {
		$startTime = new DateTime(sprintf('%04d', $meeting['meetingstarttime']));
		$endTime = new DateTime(sprintf('%04d', $meeting['meetingendtime']));
		if(!$startTime || !$endTime) {
			die(print_r($meeting));
		}
		$startTimeFormat = $startTime->format('g:ia');
		$endTimeFormat = $endTime->format('g:ia');
		$jsonCourse['times'] = $startTimeFormat . ' - ' . $endTimeFormat;
	}
	if(!is_array($meeting['meetingbuilding'])){
		$jsonCourse['meetingbuilding'] = $meeting['meetingbuilding'];
	}else{
		$jsonCourse['meetingbuilding'] = "";
	}
	
	if(!is_array($meeting['meetingroom'])){
		$jsonCourse['meetingroom'] = $meeting['meetingroom'];
	}else{
		$jsonCourse['meetingroom'] = "";
	}
}

function Filter ($course) {
	//Department filter
	if (!empty($_POST['department']) && substr($course['coursenumber'],0,4) != $_POST['department']){
		return false;
	}
	
	//Instructional method filter
	if(!empty($_POST['instructionalMethods'])) {
		$methodsFlag = false;
		$instructionalMethods = explode(',',$_POST['instructionalMethods']);
		unset($instructionalMethods[count($instructionalMethods)-1]);
		foreach($instructionalMethods as $iType){
			if ($iType == $course['meetingtypetext']){
				$methodsFlag = true;
			}
		}
		if(!$methodsFlag)
			return $methodsFlag;
	}	
	
	//Meeting days filter
	if(!empty($_POST['meetingDays']) && $course['meetingdaysofweek'] != '-------') {
		$daysFlag = false;
		for ($i = 0; $i < strlen($_POST['meetingDays']); $i++) {
			if (strpos($course['meetingdaysofweek'], substr($_POST['meetingDays'], $i, 1))) {
				$daysFlag = true;
			}
		}
		if(!$daysFlag)
			return $daysFlag;
	}
	
	//Starting after filter
	if(!empty($_POST['beginTime'])) {
		$filterTime = DateTime::createFromFormat('G:i', $_POST['beginTime']);
		$courseTime = DateTime::createFromFormat('g:ia', explode(" - ", $course['times'])[0]);
		if($courseTime < $filterTime)
			return false;
	}
	
	//End before filter
	if(!empty($_POST['endTime'])) {
		$filterTime = DateTime::createFromFormat('G:i', $_POST['endTime']);
		$courseTime = DateTime::createFromFormat('g:ia', explode(" - ", $course['times'])[1]);
		if($courseTime > $filterTime)
			return false;
	}
	
	//Class Full Filter
	list($seats, $total) = explode("/",$course['reg']);
	$seats = (int)$seats;
	$total = (int)$total;
	if(($_POST['hideFull']=="true") && (($total-$seats) <= 0) && ($total != 0)){
		return false;
	}

	return true;
}
?>