<?php
require_once(dirname(__DIR__) . '../core/functions.php');
require_once(dirname(__DIR__) . '../php_amqplib/vendor/autoload.php');
use PhpAmqpLib\Connection\AMQPStreamConnection;

function receiveFromRabbit() {
    include dirname(__DIR__) . '/core/parametrs.php';
    if (isset($QUEUE_OUT) && $QUEUE_OUT != '' && isset($CONNECT_RABBIT)) {
        $connection = new AMQPStreamConnection($CONNECT_RABBIT['host'], $CONNECT_RABBIT['port'], $CONNECT_RABBIT['login'], $CONNECT_RABBIT['password']);
        $channel = $connection->channel();
        echo " [*] Waiting \n";
        $channel->queue_declare($QUEUE_OUT, true, false, false, false);
        echo " [*] Waiting for messages. To exit press CTRL+C\n\n";
        $callback = function ($msg) {
            include dirname(__DIR__) . '/core/parametrs.php';
            include dirname(__DIR__) . '/core/dbconn.php';
            include_once dirname(__DIR__) . '/components/sendToRabbit.php';
            $data = parseMessageFromRabbit($msg);
            echo $data['out'] . "\n";
            if (dbInsertLog($dbconnect, $data['query'], '')) {
                echo " [x] Logs input to database\n";
                // if ($data['dxPackId'] != '') {
                //     $receipt = prepareReceiptToRabbit($data);
                //     if ($receipt) {
                //         sendToRabbit($receipt, []);
                //         echo " [x] Send receipt to Rabbit;\n";
                //     } else {
                //         echo " [x] Could not send receipt to Rabbit;";
                //     }
                // }
            } else {
                echo " [x] Error to log in database\n";
            }
        };
        $channel->basic_consume($QUEUE_OUT, '', false, true, false, false, $callback);
        
        while ($channel->is_consuming()) {
            try{
                $channel->wait(null, false , 20);
            }catch(PhpAmqpLib\Exception\AMQPTimeoutException $e){
                $channel->close();
                $connection->close();
                exit;
            }
        } 
    
        $channel->close();
        $connection->close();
    }  else {
        $data['status'] = false;
        $data['errorMessage'] .= 'No data to connect to Rabbit; ';
    }
}

function parseMessageFromRabbit($msg) {
    $xml = simplexml_load_string($msg->body) or die("Error: Cannot create object");

    $data = array(
        'xml' => '',
        'dxDirectionSender' => $xml->DXDirection->Sender->Org ? $xml->DXDirection->Sender->Org : '',
        'dxDirectionRecipient' => $xml->DXDirection->Recipient->Org ? $xml->DXDirection->Recipient->Org : '',
        'dxPackId' => '',
        'dxReceiptId' => '',
        'dxReceiptReplyToId' => '',
        'dxReceiptStatus' => '',
        'dxReceiptErrorMessage' => '',
        'dxReceiptReceiverId' => '',
        'dxAttachmentFile' => '',
        'dxAttachmentData' => '',
        'receipt' => false
    );

    if ($xml->DXReceipt) {
        $data['dxReceiptId'] = $xml->DXReceipt['id'] ? $xml->DXReceipt['id'] : '';
        $data['dxReceiptReplyToId'] = $xml->DXReceipt['reply_to'] ? $xml->DXReceipt['reply_to'] : '';
        $data['dxReceiptStatus'] = $xml->DXReceipt->Status ? $xml->DXReceipt->Status : '';
        $data['dxReceiptErrorMessage'] = $xml->DXReceipt->ErrorMessage ? $xml->DXReceipt->ErrorMessage : '';
        $data['dxReceiptReceiverId'] = $xml->DXReceipt->DocRef['receiver_id'] ? $xml->DXReceipt->DocRef['receiver_id'] : '';
        $data['dxAttachmentFile'] = $xml->DXReceipt->Attachments->Attachment->File ? $xml->DXReceipt->Attachments->Attachment->File : '';
        $data['dxAttachmentData'] = $xml->DXReceipt->Attachments->Attachment->Data ? $xml->DXReceipt->Attachments->Attachment->Data : '';
    }

    if ($xml->DXPack) {
        $data['dxPackId'] = $xml->DXPack['id'] ? $xml->DXPack['id'] : '';
        $data['dxAttachmentFile'] = $xml->DXPack->Attachments->Attachment->File ? $xml->DXPack->Attachments->Attachment->File : '';
        $data['dxAttachmentData'] = $xml->DXPack->Attachments->Attachment->Data ? $xml->DXPack->Attachments->Attachment->Data : '';
        $data['receipt'] = true;
    }

    $xmlFileName = filterName($data['dxReceiptId'], $data['dxPackId']); 
    $res = createFile('input', 'receive_', $xmlFileName, $msg->body, 'xml');

    if ($data['dxAttachmentFile'] != '' && $data['dxAttachmentData'] != '') {
        $dxExt = pathinfo($data['dxAttachmentFile'], PATHINFO_EXTENSION);
        $dxData = base64_decode($data['dxAttachmentData']);
        $dxFile = createFile('input', 'receive_', $xmlFileName, $dxData, $dxExt);
    } 
    
    $data['out'] = " [x] File {$res} and {$dxFile} created and saved in 'input' folder.\n";
    $data['query'] = "('now()','{$res}','{$data['dxDirectionSender']}','','{$data['dxDirectionRecipient']}','{$xmlFileName}','{$dxFile}','File receive from Rabbit','{$data['dxReceiptStatus']}','{$data['dxReceiptErrorMessage']}')";
    return $data;
}

function filterName($a, $b) {
    if ($a == '' && $b == '') {
        return '';
    } else {
        if ($a != '') {
            return $a;
        }
        if ($b != '') {
            return $b;
        }
    }
}

function findTemplate($template) {
    $dir = dirname(dirname(__DIR__)) . "/assets/templates/";
    $fileName = "{$dir}{$template}.txt";
    $files = array();
    foreach(glob($fileName) as $file) {
        $files[] = basename($file);	
    }
    if ($files['0'] !== 0) {
        return file_get_contents("{$dir}{$files['0']}");  
    } else {
        $this->data['errorMessage'] .= "{$template} Template file not found; ";
        return false;
    }
}

function prepareReceiptToRabbit($data) {
    include dirname(__DIR__) . '/core/parametrs.php';
    include_once dirname(__DIR__) . '/classes/UUID.php';
    $receiptTemplate = findTemplate('dxReceipt');
    if ($receiptTemplate) {
        $arrSearch = [
            '[# th:utext="${clientKey}" /]',
            '[# th:utext="${receiptId}" /]',
            '[# th:utext="${replyTo}" /]'
        ];
        $arrReplace = [$CLIENT_KEY,UUID::v4(),$data['dxPackId']];
        return str_replace($arrSearch, $arrReplace, $receiptTemplate);
    }
    return false;
}