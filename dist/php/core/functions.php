<?php
require('dbconn.php');
// получаем POST данные
function getPostData($post) {
    if (isset($post)) {
        $data = array(
            'signature' => !empty($post['signature']) ? clearString($post['signature']) : '',
            'hash' => !empty($post['hash']) ? clearString($post['hash']) : '',
            'template' => '',
            'organization' => !empty($post['organization']) ? clearString($post['organization']) : '',
            'room_id' => !empty($post['room_id']) ? clearString($post['room_id']) : '',
            'exkey' => !empty($post['externalkey']) ? clearString($post['externalkey']) : '',
            'docnum' => !empty($post['docnumber']) ? clearString($post['docnumber']) : '',
            'docdate' => !empty($post['docdate']) ? clearString($post['docdate']) : ''
        );
        strripos($data['organization'], 'Doc') ? $data['template'] = $data['organization'] : $data['template'] = $data['organization'] . 'Request';
        return $data;
    }
    return array();
}

// проверяем данные на спец символы
function clearString($str) {
    $str = strip_tags($str);
    $str = htmlspecialchars($str);
    return $str;
}

// подключаемся к БД
function dbConnect($dbconn) {

}

// готовим запрос
function createQuery($array) {
    $str = '';
    foreach ($array as $value) {
        $str .= "'" . $value . "',";
    }
    $str != '' ? $str = substr($str,0,-1) : '';

    $queries = array(
        'case_log' => "insert into case_log (date,dx_file_name,org,room_id,send_to,uuid,zip_file_name,message,receipt_status,receipt_message) values ('now()',{$str})"
    );
    return $queries;
}

// записываем данные в журнал
function dbInsertLog($dbconnect, $obj, $msg) {
    if ($dbconnect) {
        if (gettype($obj) == 'array') {
            $query = "insert into case_log (date,dx_file_name,org,room_id,send_to,uuid,zip_file_name,message,receipt_status,receipt_message) values ('now()','{$obj['dx']}','{$obj['org']}','{$obj['room_id']}','{$obj['to']}','{$obj['uid']}','{$obj['zip']}','{$msg}','','')";
        } else {
            $query = $query = "insert into case_log (date,dx_file_name,org,room_id,send_to,uuid,zip_file_name,message,receipt_status,receipt_message) values {$obj}";
        }
        $result = pg_query($dbconnect, $query);
        pg_close($dbconnect);
        return true;
    }
    return false;
}

// выводим данные журнала из БД
function dbShowLog($dbconnect) {
    $query = "select * from case_log";
    $result = pg_query($dbconnect, $query);
    $result = pg_fetch_all($result);
    echo '<table>
        <tr>
         <td>id</td>
         <td>date</td>
         <td>dx</td>
         <td>org</td>
         <td>room</td>
         <td>to</td>
         <td>uid</td>
         <td>zip</td>
         <td>msg</td>
        </tr>';

    foreach($result as $array)
    {
        echo '<tr>
                <td>'. $array['id'].'</td>
                <td>'. $array['date'].'</td>
                <td>'. $array['dx_file_name'].'</td>
                <td>'. $array['org'].'</td>
                <td>'. $array['room_id'].'</td>
                <td>'. $array['send_to'].'</td>
                <td>'. $array['uuid'].'</td>
                <td>'. $array['zip_file_name'].'</td>
                <td>'. $array['message'].'</td>
            </tr>';
    }
    echo '</table>';
    // echo '<pre>'; 
    var_dump($result);
    // echo '</pre>';
    pg_close($dbconnect);
}

// создаем файл
function createFile($folder = '', $prefix = '', $name, $body, $ext) {
    $dir = dirname(dirname(__FILE__ )) . "/../{$folder}";
    try {
        if (is_dir($dir)) {
            $filename = "{$dir}/{$prefix}{$name}.{$ext}";
            if (!file_exists($filename)) {
                $fp = fopen($filename, "w");
                fwrite($fp, $body);
                fclose($fp);
            } else {
                throw new Exception("File {$prefix}{$name} created error");
            }        
        } else {
            throw new Exception('Could not create directory');
        }
    } catch (Exception $e) {
        return $e;
    }
    return "{$prefix}{$name}.{$ext}"; 
}

// создаем папку
function createFolder($dir) {
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