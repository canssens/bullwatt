<?php
header('Content-Type: application/json');

/*
if(isset($_POST['activity'])) $activity =$_POST['activity'];
else {http_response_code(400);    exit(0);}
$json = json_decode($activity);
*/

$json = json_decode(file_get_contents('php://input'), true);

$answer = [];

$filenameKey = $json['UUID'] ."-". md5($json['startTime']);

$filename = "./output_tcx/".$filenameKey.".tcx";

if (!file_exists($filename)) {

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<!-- Written by BullWatt -->
<TrainingCenterDatabase xsi:schemaLocation="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd" xmlns:ns5="http://www.garmin.com/xmlschemas/ActivityGoals/v1" xmlns:ns3="http://www.garmin.com/xmlschemas/ActivityExtension/v2" xmlns:ns2="http://www.garmin.com/xmlschemas/UserProfile/v2" xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <Activities>
  <Activity Sport="Biking">
   <Id>'.$json['startTime'].'</Id>
   <Lap StartTime="'.$json['startTime'].'">
    <TotalTimeSeconds>'.$json['duration'].'</TotalTimeSeconds>
    <DistanceMeters>'.($json['distance']*1000).'</DistanceMeters>
    <!--<MaximumSpeed>12.8</MaximumSpeed>-->
    <Calories>0</Calories>
    <Intensity>Active</Intensity>
    <!--<Cadence>57</Cadence>-->
    <TriggerMethod>Manual</TriggerMethod>
    <Track>
    ';

    foreach($json['timeseries'] as $value)
    {

        $xml .= '
            <Trackpoint>
            <Time>'.$value['time'].'</Time>
            <DistanceMeters>'.$value['distance'].'</DistanceMeters>
            <Cadence>'.$value['cadence'].'</Cadence>
            <Extensions>
                <TPX xmlns="http://www.garmin.com/xmlschemas/ActivityExtension/v2">
            ';

        if(isset($value['speed']))  $xml .= '<Speed>'.($value['speed']/ 3.6).'</Speed>
        ';
        if(isset($value['power']))  $xml .= '<Watts>'.$value['power'].'</Watts>
        ';
            
        $xml .= '
                </TPX>
            </Extensions>
        </Trackpoint>';
        
    }


    $xml .= '    
    </Track>
    </Lap>
    </Activity>
    </Activities>
    </TrainingCenterDatabase>';


    file_put_contents($filename, $xml);
}

$answer['filename'] = $filename;
$json =  json_encode($answer);
echo $json;


?>









