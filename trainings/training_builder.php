<?php
// list all json file in the trainings directory then concatenate them into one json array
$files = glob('./catalog/*.json');
$trainings = [];
foreach ($files as $file) {
    $content = file_get_contents($file);
    $json = json_decode($content, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $trainings[] = $json;
    } else {
        echo "Error parsing JSON in file: $file\n";
        echo "  JSON error: " . json_last_error_msg() . "\n";
        echo "  File size: " . strlen($content) . " bytes\n";

        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            echo "  Hint: file starts with a UTF-8 BOM. Save it as UTF-8 without BOM.\n";
        }

        $firstBytes = unpack('H*', substr($content, 0, 16));
        echo "  First bytes: " . strtoupper($firstBytes[1]) . "\n";
    }
}   

//var_dump($trainings);
// sort the array by the "duration" key
usort($trainings, function ($a, $b) {
    return intval($a['duration']) <=> intval($b['duration']);
});

// save the result to trainings.json
file_put_contents( './trainings.json', json_encode($trainings, JSON_PRETTY_PRINT));
echo "Trainings JSON file created successfully.\n";

?>
