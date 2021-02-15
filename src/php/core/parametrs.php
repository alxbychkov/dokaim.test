<?php

// подключение к rabbit
$QUEUE_IN = 'testQueue';
$QUEUE_OUT = 'testQueue';
$CONNECT_RABBIT = array(
    'host' => 'localhost',
    'port' => 5672,
    'vhost' => '/',
    'login' => 'guest',
    'password' => 'guest'
);
// $QUEUE_IN = 'viv.client.AM00.1.in';
// $QUEUE_OUT = 'viv.client.AM00.1';
// $CONNECT_RABBIT = array(
//     'host' => '172.22.202.102',
//     'port' => 5672,
//     'vhost' => '/',
//     'login' => 'admin',
//     'password' => 'adminmq'
// );

$CLIENT_KEY = 'AM00.1';

$ORG_TEMPLATE_SEARCH = ['[# th:utext="${requestId}" /]', '[# th:utext="${requestDate}" /]','[# th:utext="${clientKey}" /]','[# th:utext="${receiverDepartmentCode}" /]','[# th:utext="${documentId}" /]','[# th:utext="${documentDate}" /]','[# th:utext="${documentNumber}" /]', '[# th:utext="${fileName}" /]'];

$PATH = $_SERVER['DOCUMENT_ROOT'];

$SEND_FOLDER_NAME = 'send';
$INPUT_FOLDER_NAME = 'input';

$DB_CONN_STRING = 'host=localhost port=5432 dbname=amirs user=postgres password=""';