<?php
header('Content-Type: application/json');

//ini_set('display_errors', 1);
//error_reporting(E_ALL);

$UUID = $_COOKIE["userBullWatt"];

$answer = array();
$answer["status"] = "error";
$answer["status_strava"] = "ko";

include('strava_refresh.php');

if($UUID != null && isset($arr["access_token"])==true)
{
    $access_token = $arr["access_token"];
    // Strava API URL for activity get
    $api_url = 'https://www.strava.com/api/v3/athlete/activities';

    // type of activity
    $activity_type = 'Ride'; 

    $data = [
        'per_page' => 100
    ];

    $url_with_params = $api_url . '?' . http_build_query($data);

    // Initialisztion cURL
    $ch = curl_init($url_with_params);

    // Options configuration for cURL
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true, 
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $access_token
        )
    ));

    // Request execution
    $response = curl_exec($ch);

    // Check status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        $answer["message"] = "Strava API error: HTTP code " . $http_code;

    }
    else
        {
                        // Analyze response
                $activities = json_decode($response, true);

                $activities_date_list = [];

                foreach ($activities as $activity) {
                    if ($activity['type'] === $activity_type) {
                        // Process the activity as needed
                        $activities_date_list[] = $activity['start_date'];
                    }
            }

            $answer["status"] = "ok";
            $answer["status_strava"] = "ok";
            $answer["activities_date"] = $activities_date_list;
        }
    // Closing cURL
    curl_close($ch);



}
else $answer["message"] = "no cookie";



$json =  json_encode($answer);
echo $json;

