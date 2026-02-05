<?php
//get the technical log and uuid
$msg = isset($_POST['log']) ? $_POST['log'] : '';
$uuid = isset($_POST['uuid']) ? $_POST['uuid'] : '';
$done = isset($_POST['done']) ? $_POST['done'] : '0';

//control @uuid format should only contain a-z A-Z 0-9 - _
if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $uuid)) {
    die('Invalid UUID format');
}

$filename = '../technical_log/tl_' . $uuid . '.log';

file_put_contents($filename, $msg."\n", FILE_APPEND | LOCK_EX);


?>