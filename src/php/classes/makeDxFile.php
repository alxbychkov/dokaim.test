<?php
include('UUID.php');
include('xmlDataParametrs.php');

class makeDxFile {
    function __construct($data, $files) {
        $this->data = $data;
        $this->files = $files;
        $this->data['status'] = '';
        $this->data['message'] = '';
        $this->data['errorMessage'] = '';
    }
    // создаем папку
    public function createFolder($dir) {
        try {
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    throw new Exception('Could not create directory');
                }
            }
        } catch (Exception $e) {
            $this->data['errorMessage'] .= $e . '; ';
        } 
    }
    // создаем файл
    static function createFile($folder = '', $prefix = '', $name, $body, $ext) {
        $filename = "{$folder}/{$prefix}{$name}.{$ext}";
        if (!file_exists($filename)) {
            $fp = fopen($filename, "w");
            fwrite($fp, $body);
            fclose($fp);
        } else {
            $this->data['errorMessage'] .= "File {$prefix}{$name} was created before; ";
        }
        return "{$prefix}{$name}.{$ext}";
    }
    // создаем архив
    static function createZip($folder, $name, $files = array()) {
        $fileName = pathinfo($name, PATHINFO_FILENAME);
        $zip_name = "{$folder}/{$fileName}.zip"; // имя файла
        $zip = new ZipArchive(); //Создаём объект для работы с ZIP-архивами
        if ($zip->open($zip_name, ZIPARCHIVE::CREATE) !== TRUE) { //Открываем (создаём) архив
            $this->data['errorMessage'] .= 'Sorry ZIP creation failed at this time; ';
        }
        foreach($files as $file) {
            $zip->addFile("{$folder}/$file", $file);
        }
        $zip->close(); //Завершаем работу с архивом
        return "{$fileName}.zip";
    }
    // кодируем архив в base64
    static function encodeFile($folder = '', $file) {
        $fileName = "{$folder}/{$file}";
        if (file_exists($fileName)) {
            return base64_encode(file_get_contents($fileName));
        } else {
            $this->data['errorMessage'] .= 'No such file to encode; ';
        }
        return $fileName;
    }
    // подготавливаем папку
    public function prepare($folder) {
        $this->sendFolder = "{$_SERVER['DOCUMENT_ROOT']}/{$folder}";
        $this->createFolder($this->sendFolder);
        $this->data['status'] = true;
        $this->data['message'] .= 'inizialize data and create folder; ';
    }
    // запускаем инициализацию
    public function init() {
        $this->data['xmlFile'] = $this->saveFiles($this->sendFolder, $this->files);
        // $this->data['xmlFile'] || $this->data['errorMessage'] .= 'Problems to save file; ';
        if (!$this->data['xmlFile']) {
            $this->data['errorMessage'] .= 'Problems to save file; ';
            $this->data['status'] = false;
            return $this->data;
        }
        $this->data['sigFile'] = $this->createFile($this->sendFolder, '', $this->data['xmlFile'], $this->data['signature'], 'sig');
        $this->data['archive'] = $this->createZip($this->sendFolder, $this->data['xmlFile'], [$this->data['xmlFile'], $this->data['sigFile']]);
        $this->data['archive64'] = $this->encodeFile($this->sendFolder, $this->data['archive']);
        $this->replaceDataAndMakeDxFile();
        $this->data['parametrs'] = xmlDataParametrs::OrgName();
    }
    // возвращаем объект
    public function returnData() {
        return $this->data;
    }
    // загружаем файл в папку
    private function saveFiles($folder, $files) {
        if ($files && $files['file']['error'] == UPLOAD_ERR_OK) {
            $fileName = 'piev_' . UUID::v4() . '.xml';
            $filePath = "{$folder}/{$fileName}";
            move_uploaded_file($files['file']['tmp_name'], $filePath);
            return $fileName;
        }
        return false;
    }
    // ищем шаблон организации
    private function findTemplate($template) {
        $dir = "{$_SERVER['DOCUMENT_ROOT']}/assets/templates/";
        $fileName = "{$dir}{$template}*.txt";
        $files = array();
        foreach(glob($fileName) as $file) {
            $files[] = basename($file);	
        }
        if ($files['0'] !== 0) {
            return file_get_contents("{$dir}{$files['0']}");  
        } else {
            $this->data['errorMessage'] .= "{$template} Template file not found; ";
            return;
        }
    }
    // заменяем переменные в шаблоне
    public function replaceDataAndMakeDxFile() {
        $orgTemplate = $this->findTemplate($this->data['template']);
        $dxTemplate = $this->findTemplate(xmlDataParametrs::dxRequest());
        $this->v4uuid = UUID::v4();
        $dxInfo = array(
            'requestId' => $this->v4uuid,
            'requestDate' => date('Y-m-d\TH:i:sO'),
            'clientKey' => xmlDataParametrs::OrgName(),
            'receiverDepartmentCode' => $this->data['room_id'],
            'documentId' => $this->data['exkey'],
            'documentDate' => $this->data['docdate'],
            'documentNumber' => $this->data['docnum'],
            'fileName' => $this->data['archive'],
            'packId' => $this->v4uuid,
            'documentContent' => "",
            'fileContent' => $this->data['archive64']
        );

        $arrSearch = [
            '[# th:utext="${requestId}" /]',
            '[# th:utext="${requestDate}" /]',
            '[# th:utext="${clientKey}" /]',
            '[# th:utext="${receiverDepartmentCode}" /]',
            '[# th:utext="${documentId}" /]',
            '[# th:utext="${documentDate}" /]',
            '[# th:utext="${documentNumber}" /]',
            '[# th:utext="${fileName}" /]'
        ];
        $dxInfoReplace = [
            $dxInfo['requestId'], 
            $dxInfo['requestDate'], 
            $dxInfo['clientKey'], 
            $dxInfo['receiverDepartmentCode'], 
            $dxInfo['documentId'], 
            $dxInfo['documentDate'], 
            $dxInfo['documentNumber'], 
            $dxInfo['fileName']
        ];
        $this->orgTemplateBody = $this->changeData($arrSearch, $dxInfoReplace, $orgTemplate);
        $dxInfo['documentContent'] = "<![CDATA[{$this->orgTemplateBody}]]>";
        
        $arrSearch = [
            '[# th:utext="${clientKey}" /]',
            '[# th:utext="${packId}" /]',
            '[# th:utext="${documentContent}" /]',
            '[# th:utext="${fileName}" /]',
            '[# th:utext="${fileContent}" /]'
        ];
        $dxInfoReplace = [
            $dxInfo['clientKey'],
            $dxInfo['packId'],
            $dxInfo['documentContent'],
            $dxInfo['fileName'],
            $dxInfo['fileContent']
        ];
        $this->dxXMLBody = $this->changeData($arrSearch, $dxInfoReplace, $dxTemplate);

        $this->data['dxFile'] = $this->createFile($this->sendFolder, 'dx_', pathinfo($this->data['archive'], PATHINFO_FILENAME), $this->dxXMLBody, 'xml');
    }
    // пишем данные в журнал
    public function putDocumentsInDB() {
        $output = array(
            'date' => date('Y-m-d'),
            'uid' => $this->v4uuid,
            'dx' => $this->data['dxFile'],
            'zip' => $this->data['archive'],
            'org' => xmlDataParametrs::OrgName(),
            'to' => xmlDataParametrs::getFullRoom($this->data['organization']),
            'room_id' => $this->data['room_id'],
            'message' => 'DB',
            'receipt_status' => '',
            'receipt_message' => ''
        );
        return $output;
    }
    // вспомогательная функция для замены
    private function changeData($arrSearch, $arrReplace, $template) {
        return str_replace($arrSearch, $arrReplace, $template);
    }
}
