<?php
require_once('parametrs.php');
require_once('send.php');
// include 'uuid/UUID.php';

// создаем папку
function createFolder($name) {
    $dir = $name;
    if(!is_dir($dir))
    {
        mkdir($dir, 0755, true);
    }
}

// создаем файл с подписью
function createSgnFile($name, $signature) {
    $filename = 'uploads/' . $name . '.sig';
    if (!file_exists($filename)) {
        $fp = fopen($filename, "w");
        fwrite($fp, $signature);
        fclose($fp);
    }
    return $name . '.sig';
}

// создаем файл xml
function createXmlFile($name, $string) {
    $filename = 'receives/' . $name . '.xml';
    if (!file_exists($filename)) {
        $fp = fopen($filename, "w");
        fwrite($fp, $string);
        fclose($fp);
    }
    return $name . '.xml';
}

// создаем архив
function createZip($file, $key) {
    $data = array(
        'output' => '',
        'status' => ''
    );
    $fileName = pathinfo($file, PATHINFO_FILENAME);
    $zip = new ZipArchive(); //Создаём объект для работы с ZIP-архивами
    $zip_name = 'uploads/' . $fileName . '.zip'; // имя файла
    if ($zip->open($zip_name, ZIPARCHIVE::CREATE) !==TRUE) { //Открываем (создаём) архив
        $data['output'] = 'Sorry ZIP creation failed at this time';
        $data['status'] = 'error';
    }
    $zip->addFile('uploads/' . $file, $file);
    $zip->addFile('uploads/' . $key, $key);
    $zip->close(); //Завершаем работу с архивом
    $data['output'] = $fileName . '.zip';
    $data['status'] = 'ok';
    return $data;
}

// кодируем архив в base64
function encodeFile($file) {
    $data = array(
        'output' => '',
        'status' => ''
    );
    $fileName = 'uploads/' . $file;
    if (file_exists($fileName)) {
        $data['output'] = base64_encode(file_get_contents($fileName));
        $data['status'] = 'ok';
    } else {
        $data['output'] = 'No such file or directory'; 
        $data['status'] = 'error';
    }
    return $data;
}

// выбираем шаблон документа для отправки
function insertTemplateInXml($data) {
    $orgName = $data['template'];
    $archiveName = $data['archive'];
    $archive64 = $data['archive64'];
    $dir = '../assets/templates/';
    $fileName = $dir . $orgName . '*.txt';
    $files = array();
    foreach(glob($fileName) as $file) {
        $files[] = basename($file);	
    } 
    if ($files['0'] !== 0) {
        $data = replaceDataInFile($dir . $files['0'], $archiveName, $archive64, $data);
    }
    return $data;
}

// создаем документ для отправки
function replaceDataInFile($file, $archiveName, $archive64, $data) {
    include 'parametrs.php';
    $template = file_get_contents($file);
    $dxTemplate = file_get_contents('../assets/templates/dxRequest.txt');
    $v4uuid = UUID::v4();
    $dxInfo = array(
        'requestId' => $v4uuid,
        'requestDate' => date('Y-m-d\TH:i:sO'),
        'clientKey' => $CLIENT_KEY,
        'receiverDepartmentCode' => $data['room_id'],
        'documentId' => $data['exkey'],
        'documentDate' => $data['docdate'],
        'documentNumber' => $data['docnum'],
        'fileName' => $archiveName,
        'packId' => $v4uuid,
        'documentContent' => '',
        'fileContent' => $archive64
    );
    $arrSearch = ['[# th:utext="${requestId}" /]', '[# th:utext="${requestDate}" /]','[# th:utext="${clientKey}" /]','[# th:utext="${receiverDepartmentCode}" /]','[# th:utext="${documentId}" /]','[# th:utext="${documentDate}" /]','[# th:utext="${documentNumber}" /]', '[# th:utext="${fileName}" /]'];
    $arrReplace = [$dxInfo['requestId'], $dxInfo['requestDate'], $dxInfo['clientKey'], $dxInfo['receiverDepartmentCode'], $dxInfo['documentId'], $dxInfo['documentDate'], $dxInfo['documentNumber'], $dxInfo['fileName']];
    $dxInfo['documentContent'] = '<![CDATA[' . str_replace($arrSearch, $arrReplace, $template) . ']]>';

    $arrDxSearch = ['[# th:utext="${clientKey}" /]', '[# th:utext="${packId}" /]', '[# th:utext="${documentContent}" /]', '[# th:utext="${fileName}" /]', '[# th:utext="${fileContent}" /]'];    
    $arrDxReplace = [$dxInfo['clientKey'], $dxInfo['packId'], $dxInfo['documentContent'], $dxInfo['fileName'], $dxInfo['fileContent']];

    $newDxXML = str_replace($arrDxSearch, $arrDxReplace, $dxTemplate);

    $filename = 'uploads/dx_' . pathinfo($archiveName, PATHINFO_FILENAME) . '.xml';
    if (!file_exists($filename)) {
        $fp = fopen($filename, "w");
        fwrite($fp, $newDxXML);
        fclose($fp);
    }
    return sendToRabbit(file_get_contents($filename), $data);
}

// получаем файл из rabbit
function getReceipt($file) {
}

// получаем POST данные
function getPostData($post) {
    if (isset($post)) {
        $data = array(
            'signature' => !empty($post['signature']) ? $post['signature'] : '',
            'hash' => !empty($post['hash']) ? $post['hash'] : '',
            'template' => '',
            'organization' => !empty($post['organization']) ? $post['organization'] : '',
            'room_id' => !empty($post['room_id']) ? $post['room_id'] : '',
            'exkey' => !empty($post['externalkey']) ? $post['externalkey'] : '',
            'docnum' => !empty($post['docnumber']) ? $post['docnumber'] : '',
            'docdate' => !empty($post['docdate']) ? $post['docdate'] : ''
        );
        strripos($data['organization'], 'Doc') ? $data['template'] = $data['organization'] : $data['template'] = $data['organization'] . 'Request';
        return $data;
    }
    return array();
}