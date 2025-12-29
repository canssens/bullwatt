<?php

include('strava_settings.php');


//login to strava
$filename = "../user_session/".$UUID.".json";
$file = file_get_contents($filename, true);

if($file != null && isset($_GET['code'])!=true)
{
    
    $arr = json_decode($file, true);

    if($arr["refresh_token"] != null)
    {
        
        if($arr["expires_at"] <= time())
        {
            

            $url_access_token = "client_id=".$CLIENT_ID;
            $url_access_token .= "&client_secret=".$CLIENT_SECRET;
            $url_access_token .= "&refresh_token=".$arr["refresh_token"];
            $url_access_token .= "&grant_type=refresh_token";


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $STRAVA_ACCESS_TOKEN_URI);
            curl_setopt($ch, CURLOPT_POST, 1);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $url_access_token);

            // Receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $server_output = curl_exec($ch);

            $data = json_decode($server_output, true);

            if(isset($data["access_token"]))
            {
                $arr["access_token"] = $data["access_token"];
                $arr["refresh_token"] = $data["refresh_token"];
                $arr["expires_at"] = $data["expires_at"];
                //$arr["athlete.id"] = $data["athlete"]["id"];
                //$arr["athlete.sex"] = $data["athlete"]["sex"];
                //$arr["athlete.weight"] = $data["athlete"]["weight"];
                
            }
            curl_close ($ch);

            //update session file
            $content = json_encode($arr);
            // Save user session
            file_put_contents($filename, $content);

            // load history
            $filename_history = "../user_session/strava/".$arr["athlete.id"].".json";
            $file_history = file_get_contents($filename_history, true);
            if($file_history != false && $file_history != null)
            {
                $arr_history = json_decode($file_history, true);
                $answer["athlete.history"] = $arr_history;
            }
            

        }
    }
    else $answer["message"] = "error refresh token";
}
else $answer["message"] = "error session";

?>