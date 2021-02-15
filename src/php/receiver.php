<?php
require_once('core/functions.php');
require('core/parametrs.php');
require('components/receiveFromRabbit.php');

createFolder(dirname(__DIR__) . "/{$INPUT_FOLDER_NAME}");
receiveFromRabbit();