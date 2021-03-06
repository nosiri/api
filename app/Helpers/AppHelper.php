<?php
namespace App\Helpers;

class AppHelper {
    public static function nassaab($action, $id, $email = "sahmmadh@gmail.com") {
        $version = "3.16";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://handle.ahmadhashemi.com/nassaab/special/$action.php");
        curl_setopt($ch, CURLOPT_USERAGENT, 'Nassaab/1 CFNetwork/975.0.3 Darwin/18.2.0');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "email=$email&id=$id&version=$version");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public static function receiver($link, $isNoFilter = 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://receiverdl.com/api/extract/action.php?link=$link&isNoFilter=$isNoFilter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function convert($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١','٠'];

        $num = range(0, 9);
        $convertedPersianNums = str_replace($persian, $num, $string);
        $englishNumbersOnly = str_replace($arabic, $num, $convertedPersianNums);

        return $englishNumbersOnly;
    }

    public static function bankCardCheck($card) {
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
        return array_sum($res) % 10 == 0 ? true : false;
    }

    public static function realIP() {
        $IP = null;
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        }
        $client  = @$_SERVER['HTTP_CLIENT_IP'];
        $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $remote  = $_SERVER['REMOTE_ADDR'];

        if (filter_var($client, FILTER_VALIDATE_IP)) $IP = $client;
        else if (filter_var($forward, FILTER_VALIDATE_IP)) $IP = $forward;
        else $IP = $remote;

        return $IP;
    }

    public function IPInfo($IP = null, $purpose = "location") {
        $output = null;
        if (empty($IP)) $IP = $this->realIP();
        if (!filter_var($IP, FILTER_VALIDATE_IP)) return false;
        $purpose = str_replace(["name", "\n", "\t", " ", "-", "_"], null, strtolower(trim($purpose)));
        $support = ["country", "countrycode", "state", "region", "city", "location", "address"];
        $continents = [
            "AF" => "Africa",
            "AN" => "Antarctica",
            "AS" => "Asia",
            "EU" => "Europe",
            "OC" => "Australia (Oceania)",
            "NA" => "North America",
            "SA" => "South America"
        ];
        if (filter_var($IP, FILTER_VALIDATE_IP) && in_array($purpose, $support)) {
            $ipdat = @json_decode(file_get_contents("http://www.geoplugin.net/json.gp?ip=$IP"));
            if (@strlen(trim($ipdat->geoplugin_countryCode)) == 2) {
                switch ($purpose) {
                    case "location":
                        $output = [
                            "city" => @$ipdat->geoplugin_city,
                            "state" => @$ipdat->geoplugin_regionName,
                            "country" => @$ipdat->geoplugin_countryName,
                            "country_code" => @$ipdat->geoplugin_countryCode,
                            "continent" => @$continents[strtoupper($ipdat->geoplugin_continentCode)],
                            "continent_code" => @$ipdat->geoplugin_continentCode
                        ];
                        break;
                    case "address":
                        $address = array($ipdat->geoplugin_countryName);
                        if (@strlen($ipdat->geoplugin_regionName) >= 1) $address[] = $ipdat->geoplugin_regionName;
                        if (@strlen($ipdat->geoplugin_city) >= 1) $address[] = $ipdat->geoplugin_city;
                        $output = implode(", ", array_reverse($address));
                        break;
                    case "city":
                        $output = @$ipdat->geoplugin_city;
                        break;
                    case "state":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "region":
                        $output = @$ipdat->geoplugin_regionName;
                        break;
                    case "country":
                        $output = @$ipdat->geoplugin_countryName;
                        break;
                    case "countrycode":
                        $output = @$ipdat->geoplugin_countryCode;
                        break;
                }
            }
        }
        return $output;
    }

    public static function ping($domain) {
        $startTime = microtime(true);
        $file = @fsockopen($domain, 80, $errno, $errstr, 3);
        $stopTime = microtime(true);
        $status = 0;

        if (!$file) $status = null;
        else {
            fclose($file);
            $status = ($stopTime - $startTime) * 1000;
            $status = floor($status);
        }
        return $status;
    }

    public static function success($result) {
        return response()->json(['ok' => true, 'result' => $result]);
    }

    public static function failed($error, $code) {
        $error = str_replace(" ", "_", strtoupper($error));
        return response()->json(['ok' => false, 'error' => $error], $code);
    }
    
    public static function instance() {
        return new AppHelper();
    }
}