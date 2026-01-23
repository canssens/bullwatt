<?php
header('Content-Type: application/json');

//ini_set('display_errors', 1);
//error_reporting(E_ALL);



$UUID = $_COOKIE["userBullWatt"];

$answer = array();
$answer["status"] = "error";
$answer["status_strava"] = "ko";

include('strava_refresh.php');

if($UUID != null)
{
    // Prepare TCX
    $json = json_decode(file_get_contents('php://input'), true);
    
    $activity_name = $json['name'];


    $filenameKey = $UUID ."-". md5($json['startTime']);
    $filename = "../output_tcx/".$filenameKey.".tcx";
    
    if (!file_exists($filename)) {
        
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
    <!-- Written by BullWatt -->
    <TrainingCenterDatabase xsi:schemaLocation="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2 http://www.garmin.com/xmlschemas/TrainingCenterDatabasev2.xsd" xmlns:ns5="http://www.garmin.com/xmlschemas/ActivityGoals/v1" xmlns:ns3="http://www.garmin.com/xmlschemas/ActivityExtension/v2" xmlns:ns2="http://www.garmin.com/xmlschemas/UserProfile/v2" xmlns="http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Activities>
    <Activity Sport="Biking">
    <Id>'.$json['startTime'].'</Id>
    <Notes>'.$json['deviceName'].'</Notes>
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

    $answer["filename"] = $filename;

    if(isset($arr["access_token"])==false)
    {
        $answer["message"] = "no access token";
        $json =  json_encode($answer);
        echo $json;
        exit();
    }
    else
    {
        // Send activity to Strava
        $actual_file = realpath($filename);

        // Replace these values with your information
        $access_token = $arr["access_token"];
        $tcx_file_path = $actual_file;

        // Strava API URL for activity uploads
        $api_url = 'https://www.strava.com/api/v3/uploads';

        // Activity data
        $activity_type = 'Ride'; // Running, Cycling, Swimming, etc.
        $activity_private = 0; // 0 for public, 1 for private

        // Create the data array to send
        $data = array(
            'name' => $activity_name,
            'activity_type' => $activity_type,
            'private' => $activity_private,
            'trainer' => '1',
            'sport_type' => 'VirtualRide',
            'data_type' => 'tcx',
            'file' => new CURLFile($tcx_file_path)
        );

        // Initialize cURL
        $ch = curl_init($api_url);

        // Configure cURL options
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token
            ),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ));

        // Execute the request
        $response = curl_exec($ch);

        // Close cURL
        curl_close($ch);

        

        $answer["message"] = $response;
        $answer["status"] = "ok";
        $answer["status_strava"] = "ok";
    }

}
else $answer["message"] = "no cookie";



$json =  json_encode($answer);
echo $json;

