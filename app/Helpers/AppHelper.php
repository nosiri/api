<?php
namespace App\Helpers;

class AppHelper {
    public function Nassaab($action, $id, $email = "sahmmadh@gmail.com") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://handle.ahmadhashemi.com/nassaab/special/$action.php");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Nassaab/1 CFNetwork/897.15 Darwin/17.5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email&id=$id");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        return curl_exec($ch);
    }

    public function Receiver($link, $isNoFilter = 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://receiverdl.com/api/extract/action.php?link=$link&isNoFilter=$isNoFilter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $Result = curl_exec($ch);
        curl_close($ch);
        return $Result;
    }

    public static function instance() {
        return new AppHelper();
    }
}