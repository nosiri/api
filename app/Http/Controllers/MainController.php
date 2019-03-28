<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Helpers\Jdate;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller {
    public function init() {
        $IP = AppHelper::instance()->realIP();
        $date = Jdate::instance()->jdate("Y/n/j", null, null, null, 'en');
        $dollar = $this->dollar();

        return response()->json(['status' => true, 'result' => ['ip' => $IP, 'date' => $date, 'dollar' => $dollar]]);
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
        @$dom->loadHTML($result);
        $dollar = (int)$dom->getElementById('usd1_top')->textContent;

        return response()->json(['status' => true, 'result' => ['dollar' => $dollar]]);
    }

    public function proxy() {
        $lastUpdate = json_decode(AppHelper::instance()->nassaab("info", "HFmHiMLkQI"), true)["info"][3]["subtitle"];
        $proxy = AppHelper::instance()->nassaab("install", "HFmHiMLkQI");

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

        $getAudio = json_decode(AppHelper::instance()->receiver($audio));

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

        $getVideo = json_decode(AppHelper::instance()->receiver($video, true));

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

    public function npm(Request $request) {
        $query = trim($request->get('query'));

        $validator = Validator::make($request->all(), [
            'query' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $search = json_decode(file_get_contents("https://api.npms.io/v2/search?q=$query&size=25&from=0"));
        if (!$search->total) return response()->json(['status' => false, 'result' => ['error' => 'not found']]);
        else {
            $packages = [];
            for ($i = 0; $i < count($search->results); $i++) {
                $package = $search->results[$i]->package;

                $packages[] = [
                    'title' => $package->name,
                    'description' => @$package->description,
                    'link' => $package->links->npm
                ];
            }
            return response()->json(['status' => true, 'result' => ['packages' => $packages]]);
        }
    }

    public function packagist(Request $request) {
        $query = trim($request->get('query'));

        $validator = Validator::make($request->all(), [
            'query' => 'required|string'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $search = json_decode(file_get_contents("https://packagist.org/search.json?q=$query&per_page=25&page=1"));
        if (!$search->total) return response()->json(['status' => false, 'result' => ['error' => 'not found']]);
        else {
            $packages = [];
            for ($i = 0; $i < count($search->results); $i++) {
                $package = $search->results[$i]->package;

                $packages[] = [
                    'title' => $package->name,
                    'description' => @$package->description,
                    'link' => $package->url
                ];
            }
            return response()->json(['status' => true, 'result' => ['packages' => $packages]]);
        }
    }

    public function gravatar(Request $request) {
        $email = trim($request->get('email'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $email = md5($email);
        $url = "https://s.gravatar.com/avatar/$email?s=256";

        return response()->json(['status' => true, 'result' => ['url' => $url]]);
    }

    public function bankDetector(Request $request) {
        $card = AppHelper::instance()->convert(trim($request->get('card')));

        $validator = Validator::make($request->all(), [
            'card' => 'required|size:16'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $banks = [
            '603799' => "ملی ایران",
            '589210' => "سپه",
            '627648' => "توسعه صادرات",
            '627961' => "صنعت و معدن",
            '603770' => "کشاورزی",
            '628023' => "مسکن",
            '627760' => "پست بانک ایران",
            '502908' => "توسعه تعاون",
            '627412' => "اقتصاد نوین",
            '622106' => "پارسیان",
            '502229' => "پاسارگاد",
            '627488' => "کارآفرین",
            '621986' => "سامان",
            '639346' => "سینا",
            '639607' => "سرمایه",
            '636214' => "تات",
            '502806' => "شهر",
            '502938' => "دی",
            '603769' => "صادرات",
            '610433' => "ملت",
            '627353' => "تجارت",
            '589463' => "رفاه",
            '627381' => "انصار",
            '639370' => "مهر اقتصاد"
        ];

        $isValid = AppHelper::instance()->bankCardCheck($card);
        $card = substr($card, 0, 6);

        if (!empty($banks[$card])) $bank = $banks[$card];
        else return response()->json(['status' => true, 'result' => ['error' => "Card number is not valid"]]);

        return response()->json(['status' => true, 'result' => ['bank' => $bank, 'valid' => $isValid]]);
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

    public function omen(Request $request) {
        $id = trim($request->get('id'));

        $validator = Validator::make($request->all(), [
            'id' => 'integer|min:1|max:159'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $omenId = !empty($id) ? (int)$id : rand(1, 159);
        $omenURL = "http://www.beytoote.com/images/Hafez/$omenId.gif";

        return response()->json(['status' => true, 'result' => ['id' => $omenId, 'url' => $omenURL]]);
    }

    public function emamsadegh(Request $request) {
        $name = trim($request->get('name'));

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4|max:255'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $source = "https://iandish.ir/web/list?qs=$name&src=1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        $html = curl_exec($ch);
        curl_close($ch);

        $result = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->getAttribute('class') == "user") {
                $value = explode("\n", trim($div->textContent));
                $user = $value[0];
                $role = trim(str_replace("-", "", $value[1]));

                $result[] = [
                    'name' => $user,
                    'role' => $role
                ];
            }
        }

        if (!count($result)) return response()->json(['status' => false, 'result' => ['error' => 'not found']]);
        return response()->json(['status' => true, 'result' => ['users' => $result]]);
    }

    public function weather(Request $request) {
        $location = trim($request->get('location'));

        $validator = Validator::make($request->all(), [
            'id' => 'string|min:4|max:20'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        if (empty($location)) {
            $ip = AppHelper::instance()->IPInfo();
            if (empty($ip['country'])) return response()->json(['status' => false, 'result' => ['error' => 'Location not found']]);
            $location = @$ip["country"];
            if (!empty(@$ip['state'])) $location .= " " . $ip["state"];
        }

        $locationName = ucwords(str_replace("\n", "" ,file_get_contents("https://wttr.in/$location?format=%l")));
        if ($locationName == "Not Found" || strstr($locationName, "Unknow location")) return response()->json(['status' => false, 'result' => ['error' => "Location not found ($location)"]]);
        $weather = str_replace("\n", "", file_get_contents("http://wttr.in/$location?format=%c+%t"));

        return response()->json(['status' => true, 'result' => ['location' => $locationName, 'weather' => $weather]]);
    }

    public function dns(Request $request) {
        $domain = trim($request->get('domain'));

        $validator = Validator::make($request->all(), [
            'domain' => 'required|url'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

        $domain = trim(str_replace(["https","http","://","/"], "", $domain));
        if (substr($domain, 0, 4) == "www.") $domain = substr($domain, 4);

        $url = "https://dnsdumpster.com/static/map/$domain.png";
        if (!@file_get_contents($url)) return response()->json(['status' => false, 'result' => ['error' => 'The domain is invalid']]);
        else return response()->json(['status' => true, 'result' => ['domain' => $domain, 'dns' => $url]]);
    }

    public function nassaab(Request $request) {
        $item = trim($request->get('item'));

        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:20'
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'result' => ['error' => $validator->errors()->first()]], 400);
        }

    }
}