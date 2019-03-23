<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Helpers\Jdate;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResponseController extends Controller {
    public function date() {
        $date = Jdate::instance()->jdate("Y/m/d");
        return response()->json(['status' => true, 'result' => ['date' => $date]]);
    }

    public function dollar() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://bonbast.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        $result = curl_exec($ch);
        curl_close($ch);
        $dom = new DOMDocument();
        $dom->loadHTML($result);
        $dollar = $dom->getElementById('usd1_top')->textContent;

        return response()->json(['status' => true, 'result' => ['dollar' => $dollar]]);
    }

    public function proxy() {
        $lastUpdate = json_decode(AppHelper::instance()->Nassaab("info", "HFmHiMLkQI"), true)["info"][3]["subtitle"];
        $proxy = AppHelper::instance()->Nassaab("install", "HFmHiMLkQI");

        return response()->json(['status' => true, 'result' => ['proxy' => $proxy, 'last_update' => $lastUpdate]]);
    }

    public function soundcloud(Request $request) {
        $audio = trim($request->get('link'));

        $validator = Validator::make($request->all(), [
            'link' => 'required|url|regex:/soundcloud.com\/.*/i'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $getAudio = json_decode(AppHelper::instance()->Receiver($audio));

        if (!in_array($getAudio->status, ['alert', 'ok']) || count($getAudio->groups[0]->items) == 0 || empty($getAudio->groups[0]->items[0]->link)) {
            return response()->json(['status' => false, 'result' => ['error' => 'can\'t fetch audio source']], 503);
        }
        else {
            $link = $getAudio->groups[0]->items[0]->link;

            return response()->json(['status' => true, 'result' => ['link' => $link]]);
        }
    }

    public function youtube(Request $request) {
        $video = trim($request->get('link'));

        $validator = Validator::make($request->all(), [
            'link' => 'required|url'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $getVideo = json_decode(AppHelper::instance()->Receiver($video, true));

        if (!in_array($getVideo->status, ['alert', 'ok']) || count($getVideo->groups[0]->items) == 0) {
            return response()->json(['status' => false, 'result' => ['error' => 'can\'t fetch video source']], 503);
        }
        else {
            $getVideo = $getVideo->groups[0]->items[0];
            $title = $getVideo->title;
            $link = $getVideo->link;

            return response()->json(['status' => true, 'result' => ['title' => $title, 'link' => $link]]);
        }
    }

    public function translate(Request $request) {
        $query = urlencode($request->get('query'));
        $key = env('YANDEX_TOKEN');

        $validator = Validator::make($request->all(), [
            'query' => 'required|max:512'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $translate = json_decode(file_get_contents("https://translate.yandex.net/api/v1.5/tr.json/translate?key=$key&format=plain&lang=fa&text=$query"));
        if ($translate->code != 200) return response()->json(['status' => false, 'result' => ['error' => 'Translator error']], 503);
        else {
            $json = Storage::disk('local')->get('isoCodes.json');
            $isoCodes = json_decode($json);
            $explode = explode("-", $translate->lang)[0];

            $fromLang = $isoCodes->$explode->name;
            $translated_text = $translate->text[0];

            return response()->json(['status' => true, 'result' => ['from_lang' => $fromLang, 'translated_text' => $translated_text]]);
        }
    }

    public function npm() {

    }

    public function packagist() {

    }

    public function gravatar() {

    }

    public function bankDetector() {

    }

    public function dictionary(Request $request) {
        $query = trim($request->get('query'));
        $vajehYab = env('VAJEHYAB_TOKEN');
        $vajehYabSource = env('VAJEHYAB_SOURCE');

        $validator = Validator::make($request->all(), [
            'query' => 'required|max:12'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://api.vajehyab.com/v3/word?token=$vajehYab&title=$query&db=$vajehYabSource&num=1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        $result = json_decode(curl_exec($ch))->word;
        curl_close($ch);

        return response()->json(['status' => true, 'result' => [$result]]);
    }

    public function omen() {

    }

    public function emamsadegh() {

    }

    public function weather() {

    }

    public function dns() {

    }
}