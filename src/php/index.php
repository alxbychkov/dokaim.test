<?php
require_once('core/functions.php');
require('core/parametrs.php');
require_once('components/sendToRabbit.php');
include('classes/makeDxFile.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $element = new makeDxFile(getPostData($_POST), $_FILES);
    $element->prepare($SEND_FOLDER_NAME);
    $element->createFolder("{$PATH}/{$INPUT_FOLDER_NAME}");
    $element->init();
    $data = $element->returnData();
    if ($data['status']) {
        $data = sendToRabbit(file_get_contents("{$PATH}/{$SEND_FOLDER_NAME}/{$data['dxFile']}"), $data);
        $data['db'] = $element->putDocumentsInDB();
        if (dbInsertLog($dbconnect, $data['db'], 'Files send to Rabbit')) {
            $data['message'] .= 'Files added to DB; ';
        } else {
            $data['errorMessage'] .= 'Files do not added to DB; ';
        }
    } else {
        $data['status'] = false;
        $data['errorMessage'] .= 'Error with log files; ';
    }
} else {
    $data['status'] = false;
    $data['errorMessage'] = 'Invalid method';
}

echo json_encode($data);