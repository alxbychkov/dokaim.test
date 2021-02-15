<?php
require_once("functions.php");

$data = array(
    'status' => '',
    'file' => '',
    'key' => '',
    'archive' => '',
    'archive64' => '',
    'template' => '',
    'organization' => '',
    'room_id' => '',
    'message' => '',
    'exkey' => '',
    'docnum' => '',
    'docdate' => ''
);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $signature = !empty($_POST['signature']) ? $_POST['signature'] : '';
    $hash = !empty($_POST['hash']) ? $_POST['hash'] : '';
    $template = !empty($_POST['organization']) ? $_POST['organization'] : '';
    $data['organization'] = !empty($_POST['organization']) ? $_POST['organization'] : '';
    $data['room_id'] = !empty($_POST['room_id']) ? $_POST['room_id'] : '';
    $data['exkey'] = !empty($_POST['externalkey']) ? $_POST['externalkey'] : '';
    $data['docnum'] = !empty($_POST['docnumber']) ? $_POST['docnumber'] : '';
    $data['docdate'] = !empty($_POST['docdate']) ? $_POST['docdate'] : '';

    if (strripos($data['organization'], 'Doc') !== false) {
        $data['template'] = $data['organization'];
    } else {
        $data['template'] = $data['organization'] . 'Request';
    }

    createFolder('uploads');

    if ($_FILES && $_FILES['file']['error'] == UPLOAD_ERR_OK)
    {
        $v4uuidName = 'piev_' . UUID::v4() . '.xml';
        $name = 'uploads/' . $v4uuidName;
        move_uploaded_file($_FILES['file']['tmp_name'], $name);
        $data['file'] = $v4uuidName;
        $data['message'] .= 'Файл загружен ';
        if ($signature !== '') {
            $key = createSgnFile($data['file'], $signature);
            $data['key'] = $key;
        }
    }

    $archiveName = createZip($data['file'], $key);
    if ($archiveName['status'] != 'error') {
        $data['archive'] = $archiveName['output'];
        $encodeStr = encodeFile($archiveName['output']);
        $data['archive64'] = $encodeStr['output'];
    } else {
        $data['message'] .= 'Ошибка архива ';
    }

    $data = insertTemplateInXml($data);
} else {
    $data['status'] = 'error';
    $data['message'] .= 'Invalid method ';
}

echo json_encode($data);




