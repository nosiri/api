<?php
namespace App\Helpers;

class AppHelper {
    public function nassaab($action, $id, $email = "sahmmadh@gmail.com") {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://handle.ahmadhashemi.com/nassaab/special/$action.php");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Nassaab/1 CFNetwork/897.15 Darwin/17.5.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email&id=$id");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        return curl_exec($ch);
    }

    public function receiver($link, $isNoFilter = 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://receiverdl.com/api/extract/action.php?link=$link&isNoFilter=$isNoFilter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $Result = curl_exec($ch);
        curl_close($ch);
        return $Result;
    }

    function convert($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    function bankCardCheck($card) {
        $card = (string) preg_replace('/\D/', '', $card);
        $length = strlen($card);
        if ($length != 16) return false;
        if (!in_array($card[0], [2, 4, 5, 6, 9])) return false;

        $res = [];
        for ($i=0; $i < $length; $i++) {
            $res[$i] = $card[$i];
            if (($length % 2) == ($i % 2)) {
                $res[$i] *= 2;
                if ($res[$i] > 9) $res[$i] -= 9;
            }
        }
        return array_sum($res) % 10 == 0? true : false;
    }

    public static function instance() {
        return new AppHelper();
    }
}