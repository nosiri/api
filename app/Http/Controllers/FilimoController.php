<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FilimoController extends Controller {
    public function search(Request $request) {
        $query = trim($request->get('query'));

        $validator = Validator::make($request->all(), [
            'query' => 'required'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $query = urlencode($query);

        $response = AppHelper::instance()->filimo($query, "search");

        if (!count($response)) return AppHelper::instance()->failed("Not found", 400);
        else {
            $count = count($response) > 20 ? 20 : count($response);
            $result = [];

            for ($i = 0; $i < $count; $i++) {
                $movie = $response[$i];
                $result[] = [
                    'title' => $movie->movie_title,
                    'id' => $movie->uid
                ];
            }

            return AppHelper::instance()->success($result);
        }
    }

    public function get(Request $request) {
        $id = trim($request->get('id'));

        $validator = Validator::make($request->all(), [
            'id' => 'required|string|size:5'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $response = AppHelper::instance()->filimo($id, "movie");

        if (@empty($response->uid) || @$response->uid != $id) return AppHelper::instance()->failed("Incorrect ID", 400);
        else {
            $title = $response->movie_title;
            $image = $response->movie_img_b;
            $description = $response->description;
            $year = (int)$response->produced_year;
            $duration = (int)$response->duration;
            $imdb = $response->imdb_rate ? (float)$response->imdb_rate : null;
            $rate = $response->rate_avrage ? (float)$response->rate_avrage : null;

            $user = env('FILIMO_USER');
            $token = env('FILIMO_TOKEN');
            $link = "https://www.filimo.com/etc/api/movie/uid/$id/luser/$user/ltoken/$token/devicetype/ios";

            $genres = [];
            if (!empty($response->category_1)) $genres[] = $response->category_1;
            if (!empty($response->category_2)) $genres[] = $response->category_2;

            $result = [
                'title' => $title,
                'image' => $image,
                'description' => $description,
                'year' => $year,
                'duration' => $duration,
                'genres' => $genres,
                'rate' => [
                    'imdb' => $imdb,
                    'filimo' => $rate,
                ],
                'link' => $link
            ];

            return AppHelper::instance()->success($result);
        }
    }

    public function finder(Request $request) {
        $GUID = env('FILIMO_GUID');
        $AFCN = env('FILIMO_AFCN');

        $username = trim($request->get('username'));

        $validator = Validator::make($request->all(), [
            'username' => 'required|string'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $tv = "";
        $Mobile = [];

        #Get TV Code
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.filimo.com/etc/api/verifycodeget/devicetype/tvweb');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);

        $tvCode = json_decode($response)->verifycodeget->code;
        if (curl_errno($ch) || empty($tvCode)) return AppHelper::instance()->failed("Can't get TV Code", 502);
        curl_close($ch);

        #Get CSRF Token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.filimo.com/etc/api/formconfig/action_name/stepsigninotp?responseSite=tv&code=$tvCode");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = [];
        $headers[] = 'Host: www.filimo.com';
        $headers[] = 'X-Sabaenv: SPA';
        $headers[] = 'Accept: application/json, text/plain, */*';
        $headers[] = 'User-Agent: ' . env('FAKE_USERAGENT');
        $headers[] = 'Accept-Language: en-us';
        $headers[] = 'Referer: https://www.filimo.com/signin/password?responseSite=tv&code=ph9al';
        $headers[] = 'Dnt: 1';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        $csrf = json_decode($response)->csrf->csrf_token;
        if (curl_errno($ch) || empty($csrf)) return AppHelper::instance()->failed("Can't get CSRF Code", 502);
        curl_close($ch);


        #Get mobile first part
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.filimo.com/etc/api/signinstep1v3/usernamemo/$username?responseSite=tv&code=$tvCode");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "------WebKitFormBoundarysFDcYjZugXsvLCXH\nContent-Disposition: form-data; name=\"csrf_token\"\n\n$csrf\n------WebKitFormBoundarysFDcYjZugXsvLCXH--\n");
        curl_setopt($ch, CURLOPT_POST, 1);
        $headers = [];
        $headers[] = 'Host: www.filimo.com';
        $headers[] = 'Accept: application/json, text/plain, */*';
        $headers[] = 'X-Sabaenv: SPA';
        $headers[] = 'Accept-Language: en-us';
        $headers[] = 'Content-Type: multipart/form-data; boundary=----WebKitFormBoundarysFDcYjZugXsvLCXH';
        $headers[] = 'Origin: https://www.filimo.com';
        $headers[] = 'User-Agent: ' . env('FAKE_USERAGENT');
        $headers[] = 'Referer: https://www.filimo.com/login?responseSite=tv&code=mxfuk';
        $headers[] = 'Dnt: 1';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        $mobile[] = json_decode($response)->signinstep1new->mobile_valid;
        if (curl_errno($ch) || empty($mobile[0])) return AppHelper::instance()->failed("Error when fetching Mobile first part", 502);
        curl_close($ch);

        #Get temp_id
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.filimo.com/_/api/fa/v1/user/Authenticate/auth?devicetype=ios&afcn=$AFCN&&guid=$GUID");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, '{\"sz\":\"130.0x274.0\",\"dt\":\"iPhone*8\",\"an\":\"Aparat Filimo\",\"sdk\":\"11.4\",\"os\":\"iOS\",\"ds\":\" 2.0\",\"vn\":\"4.0.4\",\"pkg\":\"com.aparat.iFilimo\",\"id\":\"' . $GUID . '\",\"afcn\":\"' . $AFCN . '\",\"vc\":\"64\",\"camp\":\"seeb\",\"oui\":\"\"}');
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);

        $tempID = json_decode($response)->data->attributes->temp_id;
        if (curl_errno($ch) || empty($tempID)) return AppHelper::instance()->failed("Can't get Temp ID", 502);
        curl_close($ch);

        #Get mobile second part
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.filimo.com/_/api/fa/v1/user/Authenticate/signin_step1?&account=$username&temp_id=$tempID&guid=$GUID");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.75.14 (KHTML, like Gecko) Version/7.0.3 Safari/7046A194A');
        $headers = [];
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $mobile[] = json_decode($response)->data->attributes->mobile_valid;
        if (curl_errno($ch) || empty($mobile[1])) return AppHelper::instance()->failed("Error when fetching Mobile second part", 502);
        curl_close ($ch);

        #Parse Number
        if ($mobile[0] && $mobile[1]) {
            $mobile['full'] = substr($mobile[0], 0, 9);
            $mobile['full'] .= substr($mobile[1], 9, 3);
            $mobile['full'] = '0' . substr($mobile['full'], 2);
        }

        $result = [
            'mobile' => $mobile['full'],
            'tv_code' => $tvCode,
            'temp_id' => $tempID,
        ];

        return AppHelper::instance()->success($result);
    }
}