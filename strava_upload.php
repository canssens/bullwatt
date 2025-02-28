<?php
header('Content-Type: application/json');

//ini_set('display_errors', 1);
//error_reporting(E_ALL);



$UUID = $_COOKIE["userBullWatt"];

$answer = array();
$answer["status"] = "error";

include('strava_refresh.php');

if($UUID != null)
{
    //prepare TCX
    $json = json_decode(file_get_contents('php://input'), true);
            
    $filenameKey = $UUID ."-". md5($json['startTime']);
    
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
        //SEND Activity to strava
        $actual_file = realpath($filename);

        // Remplacez ces valeurs par vos informations
        $access_token = $arr["access_token"];
        $tcx_file_path = $actual_file;

        // URL de l'API Strava pour les téléchargements d'activités
        $api_url = 'https://www.strava.com/api/v3/uploads';

        // Données de l'activité
        $activity_name = 'BullWatt Training';
        $activity_type = 'Ride'; // Course, Vélo, Natation, etc.
        $activity_private = 0; // 0 pour public, 1 pour privé

        // Création du tableau des données à envoyer
        $data = array(
            'name' => $activity_name,
            'activity_type' => $activity_type,
            'private' => $activity_private,
            'trainer' => '1',
            'data_type' => 'tcx',
            'file' => new CURLFile($tcx_file_path)
        );

        // Initialisation de cURL
        $ch = curl_init($api_url);

        // Configuration des options de cURL
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token
            ),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ));

        // Exécution de la requête
        $response = curl_exec($ch);

        // Fermeture de cURL
        curl_close($ch);

        $answer["message"] = $response;
        $answer["status"] = "ok";
    }

}
else $answer["message"] = "no cookie";



$json =  json_encode($answer);
echo $json;

