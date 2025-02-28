<?php
header('Content-Type: application/json');

//ini_set('display_errors', 1);
//error_reporting(E_ALL);


$UUID = $_COOKIE["userBullWatt"];

$answer = array();
$answer["status"] = "error";

include('strava_refresh.php');


if(isset($_GET['code'])==true)
{
    $code = $_GET['code'];
    
    $ch = curl_init();



    curl_setopt($ch, CURLOPT_URL, $STRAVA_ACCESS_TOKEN_URI);
    curl_setopt($ch, CURLOPT_POST, 1);

    //curl_setopt($ch, CURLOPT_POSTFIELDS, "postvar1=value1&postvar2=value2&postvar3=value3");

    $url_access_token = "client_id=".$CLIENT_ID;
    $url_access_token .= "&client_secret=".$CLIENT_SECRET;
    $url_access_token .= "&code=".$code;
    $url_access_token .= "&grant_type=authorization_code";

    curl_setopt($ch, CURLOPT_POSTFIELDS, $url_access_token);

    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);


    $data = json_decode($server_output, true);

    //$txt_output .="<h1>AT</h1>".$data["access_token"];

    if(isset($data["access_token"]))
    {
        $arr = [];
        $arr["access_token"] = $data["access_token"];
        $arr["refresh_token"] = $data["refresh_token"];
        $arr["expires_at"] = $data["expires_at"];
        $arr["athlete.id"] = $data["athlete"]["id"];
        $arr["athlete.sex"] = $data["athlete"]["sex"];
        $arr["athlete.weight"] = $data["athlete"]["weight"];

        curl_close ($ch);
        
        $content = json_encode($arr);

        // Save user session
        file_put_contents($filename, $content);

        header('Location: ' . $REDIRECT_URI);
        exit();

    }
    else $answer["message"] = "Error Login";


}
elseif(isset($_GET['logout'])==true && isset($arr["access_token"])==true)    
{
    $answer["message"] = "logout";
    // send log out request
    // 2. Build the Request Parameters
    $params = [
        'access_token' => $arr["access_token"],
    ];

    // 3. Initialize cURL
    $ch = curl_init('https://www.strava.com/oauth/deauthorize');

    // 4. Set cURL Options
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, // Return the response as a string
        CURLOPT_POST => true, // Use POST method
        CURLOPT_POSTFIELDS => http_build_query($params), // Encode parameters
    ]);


    // 5. Execute the Request
    $response = curl_exec($ch);

    $answer["message"] = "logout ok";
    $answer["status"] = "ok";
    unlink($filename);

    // 7. Close cURL
    curl_close($ch);

    header('Location: ' . $REDIRECT_URI);
    exit();

   
}
elseif(isset($arr["access_token"])==true)
{
    $answer["status"] = "ok";
    $answer["message"] = "logged";
}




$json =  json_encode($answer);
echo $json;


?>
