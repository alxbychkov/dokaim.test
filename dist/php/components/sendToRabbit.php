<?php
require_once(dirname(__DIR__) . '../php_amqplib/vendor/autoload.php'); 
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendToRabbit($message,$data) {
    include dirname(__DIR__) . '/core/parametrs.php';
    if (isset($QUEUE_IN) && $QUEUE_IN != '' && isset($CONNECT_RABBIT)) {
        $connection = new AMQPStreamConnection($CONNECT_RABBIT['host'], $CONNECT_RABBIT['port'], $CONNECT_RABBIT['login'], $CONNECT_RABBIT['password']);
        $channel = $connection->channel();
        $channel->queue_declare($QUEUE_IN, true, false, false, false);
        $msg = new AMQPMessage($message);
        $channel->basic_publish($msg, '', $QUEUE_IN);
        $data['status'] = true;
        $data['message'] .= 'Message send to Rabbit; ';
        $channel->close();
        $connection->close();
    } else {
        $data['status'] = false;
        $data['errorMessage'] .= 'No data to connect to Rabbit; ';
    }

    return $data;
}
