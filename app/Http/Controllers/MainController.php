<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Helpers\Jdate;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller {
    public function init(Request $request) {
        $IP = AppHelper::instance()->realIP();
        $date = Jdate::instance()->jdate("Y/n/j", null, null, null, 'en');
        $dollar = $this->dollar()->original["result"]["dollar"];
        $weather = $this->weather($request)->original;

        $result = [
            'ip' => $IP,
            'date' => $date,
            'dollar' => $dollar,
            'weather' => $weather
        ];
        return AppHelper::instance()->success($result);
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

        $result = [
            'dollar' => $dollar
        ];
        return AppHelper::instance()->success($result);
    }

    public function proxy() {
        $lastUpdate = json_decode(AppHelper::instance()->nassaab("info", "HFmHiMLkQI"), true)["info"][3]["subtitle"];
        $proxy = AppHelper::instance()->nassaab("install", "HFmHiMLkQI");

        $result = [
            'proxy' => $proxy,
            'last_update' => $lastUpdate
        ];
        return AppHelper::instance()->success($result);
    }

    public function soundcloud(Request $request) {
        $audio = trim($request->get('link'));

        $validator = Validator::make($request->all(), [
            'link' => ['required', 'url', 'regex:/^https?:\/\/((www|m)\.)?soundcloud\.com\/.+/i']
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $getAudio = json_decode(AppHelper::instance()->receiver($audio));

        if (@!in_array($getAudio->status, ['alert', 'ok']) || count($getAudio->groups[0]->items) == 0 || empty($getAudio->groups[0]->items[0]->link)) {
            $error = 'Gateway error';
            return AppHelper::instance()->failed($error, 502);
        }
        else {
            $link = $getAudio->groups[0]->items[0]->link;

            $result = [
                'link' => $link
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function youtube(Request $request) {
        $video = trim($request->get('link'));

        $validator = Validator::make($request->all(), [
            'link' => ["required", "url", "regex:/http[s]?:\/\/(?:(?:m\.)|(?:www\.))?(?:youtube.com|youtu.be)\/.*/"]
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $getVideo = json_decode(AppHelper::instance()->receiver($video, true));

        if (!in_array(@$getVideo->status, ['alert', 'ok']) || count($getVideo->groups[0]->items) == 0) {
            $error = 'Gateway error';
            return AppHelper::instance()->failed($error, 502);
        }
        else {
            $getVideo = $getVideo->groups[0]->items[0];
            $title = $getVideo->title;
            $link = $getVideo->link;

            if ($title == " " || substr($link, -5) == "_.mp4") {
                $error = 'Censored video';
                return AppHelper::instance()->failed($error, 403);
            }

            $result = [
                'title' => $title,
                'link' => $link
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function npm(Request $request) {
        $query = trim($request->get('query'));

        $validator = Validator::make($request->all(), [
            'query' => 'required|string'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $search = json_decode(file_get_contents("https://api.npms.io/v2/search?q=$query&size=25&from=0"));
        if (!$search->total) {
            $error = 'Not found';
            return AppHelper::instance()->failed($error, 400);
        }
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
            $result = [
                'packages' => $packages
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function packagist(Request $request) {
        $query = trim($request->get('query'));

        $validator = Validator::make($request->all(), [
            'query' => 'required|string'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $search = json_decode(file_get_contents("https://packagist.org/search.json?q=$query&per_page=25&page=1"));
        if (!$search->total) {
            $error = 'Not found';
            return AppHelper::instance()->failed($error, 400);
        }
        else {
            $packages = [];
            for ($i = 0; $i < count($search->results); $i++) {
                $package = $search->results[$i];

                $packages[] = [
                    'title' => $package->name,
                    'description' => @$package->description,
                    'link' => $package->url
                ];
            }
            $result = [
                'packages' => $packages
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function gravatar(Request $request) {
        $email = trim($request->get('email'));

        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $email = md5($email);
        $url = "https://s.gravatar.com/avatar/$email?s=256";

        $result = [
            'url' => $url
        ];
        return AppHelper::instance()->success($result);
    }

    public function bankDetector(Request $request) {
        $card = AppHelper::instance()->convert(trim($request->get('card')));

        $validator = Validator::make($request->all(), [
            'card' => 'required|size:16'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
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
        else {
            $error = 'Card number is not valid';
            return AppHelper::instance()->failed($error, 400);
        }

        $result = [
            'bank' => $bank,
            'valid' => $isValid
        ];
        return AppHelper::instance()->success($result);
    }

    public function dictionary(Request $request) {
        $query = trim($request->get('query'));
        $source = env('VAJEHYAB_SOURCE');

        $validator = Validator::make($request->all(), [
            'query' => 'required|max:12|regex:/[ا-ی]/'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $query = urlencode($query);
        $time = (int)round(microtime(true) * 1000);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.vajehyab.com/$source/$query?_=$time");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-vy-ajax: true']);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (@!$response->response->status) {
            $error = 'Not found';
            return AppHelper::instance()->failed($error, 400);
        }

        $response->word->text = trim(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\r\n", $response->word->text)));

        $result = [];

        foreach ($response->info as $item) {
            $result[$item->name] = $item->text;
        }
        $result["database"] = $response->word->source;
        $result["text"] = $response->word->text;

        return AppHelper::instance()->success($result);
    }

    public function omen(Request $request) {
        $id = trim($request->get('id'));

        $validator = Validator::make($request->all(), [
            'id' => 'integer|min:1|max:159'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $omenId = !empty($id) ? (int)$id : rand(1, 159);
        $omenURL = "http://www.beytoote.com/images/Hafez/$omenId.gif";

        $result = [
            'id' => $omenId,
            'url' => $omenURL
        ];
        return AppHelper::instance()->success($result);
    }

    public function emamsadegh(Request $request) {
        $name = trim($request->get('name'));

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:4|max:255'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
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

        if (!count($result)) {
            $error = 'Not found';
            return AppHelper::instance()->failed($error, 400);
        }
        else {
            $result = [
                'users' => $result
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function weather(Request $request) {
        $location = trim($request->get('location'));

        $validator = Validator::make($request->all(), [
            'id' => 'string|min:4|max:20'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        if (empty($location)) {
            $ip = AppHelper::instance()->IPInfo();
            if (empty($ip['country'])) {
                $error = 'Not found';
                return AppHelper::instance()->failed($error, 400);
            }
            $location = @$ip["country"];
            if (!empty(@$ip['state'])) $location .= " " . $ip["state"];
        }

        $locationName = ucwords(str_replace("\n", "" , file_get_contents("https://wttr.in/$location?format=%l")));
        if ($locationName == "Not Found" || strstr($locationName, "Unknow location")) {
            $error = 'Not found (' . $location . ')';
            return AppHelper::instance()->failed($error, 400);
        }
        $weather = str_replace("\n", "", file_get_contents("http://wttr.in/$location?format=%c+%t"));

        $result = [
            'location' => $locationName,
            'weather' => $weather
        ];
        return AppHelper::instance()->success($result);
    }

    public function dns(Request $request) {
        $domain = trim($request->get('domain'));

        $validator = Validator::make($request->all(), [
            'domain' => 'required|url'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $domain = trim(str_replace(["https","http","://","/"], "", $domain));
        if (substr($domain, 0, 4) == "www.") $domain = substr($domain, 4);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://dnsdumpster.com/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "targetip=$domain&csrfmiddlewaretoken=MKVFCoUkX4PO3mlx7YdMPVGPQPxxzNus");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        curl_exec($ch);
        curl_close($ch);

        $url = "https://dnsdumpster.com/static/map/$domain.png";
        if (!@file_get_contents($url)) {
            $error = 'The domain is invalid';
            return AppHelper::instance()->failed($error, 400);
        }
        else {
            $result = [
                'domain' => $domain,
                'dns' => $url
            ];
            return AppHelper::instance()->success($result);
        }
    }

    public function nassaab(Request $request) {
        $item = trim($request->get('item'));

        $validator = Validator::make($request->all(), [
            'item' => 'required|string|max:20'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

    }
}