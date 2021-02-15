<?php
class xmlDataParametrs {
    public static function orgName() {
        return 'AM00.1';
    }
    public static function dxRequest() {
        return 'dxRequest';
    }
    public static function dxReceipt() {
        return 'dxReceipt';
    }
    public static function getFullRoom($name) {
        switch ($name) {
            case 'fssp':
                $out = 'FSSP01';
                break;
            case 'bid':
                $out = 'FSSP01';
                break;
            case 'kazna':
                $out = 'RKZN02';
                break;
            case 'minfin':
                $out = 'MFIN03_3T';
                break;
            case 'kaznaDocReceipt':
                $out = 'RKZN02';
                break;
            case 'minfinDocReceipt':
                $out = 'MFIN03_3T';
                break;
            default:
                $out = '';
                break;
        }
        return $out;
    }
}