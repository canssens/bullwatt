<?php
//get the technical log and uuid
$msg = isset($_POST['log']) ? $_POST['log'] : '';
$uuid = isset($_POST['uuid']) ? $_POST['uuid'] : '';

$filename = './technical_log/tl_' . $uuid . '.log';

file_put_contents($filename, $msg."\n", FILE_APPEND | LOCK_EX);

?>