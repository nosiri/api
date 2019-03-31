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
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function receiver($link, $isNoFilter = 0) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://receiverdl.com/api/extract/action.php?link=$link&isNoFilter=$isNoFilter");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
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
        return array_sum($res) % 10 == 0 ? true : false;
    }

    public function realIP() {
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

    function IPInfo($IP = null, $purpose = "location") {
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

    public function success($result) {
        return response()->json(['ok' => true, 'result' => $result]);
    }

    public function failed($error, $code) {
        return response()->json(['ok' => false, 'error' => $error], $code);
    }
    
    public static function instance() {
        return new AppHelper();
    }
}