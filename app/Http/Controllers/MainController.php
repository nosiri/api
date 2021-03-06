<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper as Helper;
use App\Helpers\Jdate;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller {
    public function init() {
        $Jdate = Jdate::instance();
        $IP = Helper::realIP();
        $date = $Jdate->jdate("Y/n/j", null, null, null, 'en');
        $dollar = $this->currency()->original["result"]["dollar"];

        $result = [
            'ip' => $IP,
            'date' => $date,
            'dollar' => $dollar,
        ];
        return Helper::success($result);
    }

    public function status() {
        $result = [];

        $result["ahmadhashemi"] = Helper::ping("handle.ahmadhashemi.com");
        $result["receiver"] = Helper::ping("receiverdl.com");
        $result["filimo"] = Helper::ping("filimo.com");
        $result["namava"] = Helper::ping("namava.ir");
        $result["soundcloud"] = Helper::ping("soundcloud.com");
        $result["youtube"] = Helper::ping("youtube.com");
        $result["bonbast"] = Helper::ping("bonbast.com");
        $result["weather"] = [
            'main' => Helper::ping("api.weather.com"),
            'opencage' => Helper::ping("api.opencagedata.com")
        ];
        $result["vajehyab"] = Helper::ping("vajehyab.com");
        $result["sokhanak"] = Helper::ping("api.sokhanak.com");
        $result["emamsadegh"] = Helper::ping("iandish.ir");
        $result["npm"] = Helper::ping("npms.io");
        $result["packagist"] = Helper::ping("packagist.org");

        return Helper::success($result);
    }

    public function currency() {
        if (env('APP_CACHE') && Cache::has('currency')) $result = Cache::get('currency');
        else {
            $Jdate = Jdate::instance();

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://bonbast.com/');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $html = curl_exec($ch);
            curl_close($ch);

            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $hash = $dom->getElementById("hash")->textContent;
            $lastUpdate = $Jdate->jdate("Y/n/j G:i:s", strtotime($dom->getElementById('last_modified')->textContent), null, null, 'en');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://bonbast.com/json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
            curl_setopt($ch, CURLOPT_REFERER, 'https://bonbast.com/');
            curl_setopt($ch, CURLOPT_POSTFIELDS, "hash=$hash");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);

            $dollar = (int)$response->usd1;
            $euro = (int)$response->eur1;
            $gold = (int)$response->gol18;
            $bitcoin = (int)$response->bitcoin * $dollar;
            $emamiCoin = (int)$response->emami1;

            $result = [
                'last_update' => $lastUpdate,
                'dollar' => $dollar,
                'euro' => $euro,
                'gold' => $gold,
                'bitcoin' => $bitcoin,
                'emami_coin' => $emamiCoin
            ];

            if (env('APP_CACHE')) Cache::put('currency', $result, 2 * 60);
        }

        return Helper::success($result);
    }

    public function proxy() {
        $lastUpdate = json_decode(Helper::nassaab("info", "HFmHiMLkQI"), true)["info"][3]["subtitle"];
        $proxy = Helper::nassaab("install", "HFmHiMLkQI");

        $result = [
            'proxy' => $proxy,
            'last_update' => $lastUpdate
        ];
        return Helper::success($result);
    }

    public function soundcloud(Request $request) {
        Validator::make($request->all(), [
            'link' => ['required', 'url', 'regex:/^https?:\/\/((www|m)\.)?soundcloud\.com\/.+/i']
        ])->validate();

        $audio = trim($request->get('link'));

        $getAudio = json_decode(Helper::receiver($audio));

        if (@!in_array($getAudio->status, ['alert', 'ok']) || count($getAudio->groups[0]->items) == 0 || empty($getAudio->groups[0]->items[0]->link))
            return Helper::failed("Gateway error", 502);
        else {
            $link = $getAudio->groups[0]->items[0]->link;

            $result = [
                'link' => $link
            ];
            return Helper::success($result);
        }
    }

    public function youtube(Request $request) {
        Validator::make($request->all(), [
            'link' => ["required", "url", "regex:/http[s]?:\/\/(?:(?:m\.)|(?:www\.))?(?:youtube.com|youtu.be)\/.*/"]
        ])->validate();

        $video = trim($request->get('link'));

        $getVideo = json_decode(Helper::receiver($video, true));

        if (!in_array(@$getVideo->status, ['alert', 'ok']) || count($getVideo->groups[0]->items) == 0)
            return Helper::failed("Gateway error", 502);
        else {
            $getVideo = $getVideo->groups[0]->items[0];
            $title = $getVideo->title;
            $link = $getVideo->link;

            if ($title == " " || substr($link, -5) == "_.mp4")
                return Helper::failed("Censored video", 403);

            $result = [
                'title' => $title,
                'link' => $link
            ];
            return Helper::success($result);
        }
    }

    public function npm(Request $request) {
        Validator::make($request->all(), [
            'query' => 'required|string'
        ])->validate();

        $query = trim($request->get('query'));

        $search = json_decode(file_get_contents("https://api.npms.io/v2/search?q=$query&size=25&from=0"));
        if (!$search->total) return Helper::failed("Not found", 400);
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
            return Helper::success($result);
        }
    }

    public function packagist(Request $request) {
        Validator::make($request->all(), [
            'query' => 'required|string'
        ])->validate();

        $query = trim($request->get('query'));

        $search = json_decode(file_get_contents("https://packagist.org/search.json?q=$query&per_page=25&page=1"));
        if (!$search->total) return Helper::failed("Not found", 400);
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
            return Helper::success($result);
        }
    }

    public function gravatar(Request $request) {
        Validator::make($request->all(), [
            'email' => 'required|email'
        ])->validate();

        $email = trim($request->get('email'));

        $email = md5($email);
        $url = "https://s.gravatar.com/avatar/$email?s=256";

        $result = [
            'url' => $url
        ];
        return Helper::success($result);
    }

    public function bankDetector(Request $request) {
        Validator::make($request->all(), [
            'card' => 'required|size:16'
        ])->validate();

        $card = Helper::convert(trim($request->get('card')));

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

        $isValid = Helper::bankCardCheck($card);
        $card = substr($card, 0, 6);

        if (!empty($banks[$card])) $bank = $banks[$card];
        else return Helper::failed("Invalid card", 400);

        $result = [
            'bank' => $bank,
            'valid' => $isValid
        ];
        return Helper::success($result);
    }

    public function dictionary(Request $request) {
        Validator::make($request->all(), [
            'query' => 'required|max:12|regex:/[ا-ی]/'
        ])->validate();

        $query = urlencode(trim($request->get('query')));
        $source = env('VAJEHYAB_SOURCE');
        $time = (int)round(microtime(true) * 1000);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.vajehyab.com/$source/$query?_=$time");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-vy-ajax: true']);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (@!$response->response->status) return Helper::failed("Not found", 400);

        $response->word->text = trim(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\r\n", $response->word->text)));

        $result = [];

        foreach ($response->info as $item) {
            $result[$item->name] = $item->text;
        }
        $result["database"] = $response->word->source;
        $result["text"] = $response->word->text;

        return Helper::success($result);
    }

    public function omen(Request $request) {
        Validator::make($request->all(), [
            'id' => 'integer|min:1|max:159'
        ])->validate();

        $id = trim($request->get('id'));

        $omenId = !empty($id) ? (int)$id : rand(1, 159);
        $omenURL = "http://www.beytoote.com/images/Hafez/$omenId.gif";

        $result = [
            'id' => $omenId,
            'url' => $omenURL
        ];
        return Helper::success($result);
    }

    public function quote() {
        $quote = "http://api.sokhanak.com/v1/embed_widgets/?";
        $quote .= http_build_query([
            'lang' => 'fa_IR',
            'apiKey' => env('SOKHANAK_TOKEN'),
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $quote);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        $response = json_decode(curl_exec($ch));

        if (isset($response->error) || empty($response->quote_text))
            return Helper::failed('Sokhanak error', 502);

        $result = [
            'quote' => $response->quote_text,
            'author' => [
                'name' => $response->quote_author,
                'photo' => "https:" . $response->quote_photo[0]
            ]
        ];

        return Helper::success($result);
    }

    public function emamsadegh(Request $request) {
        Validator::make($request->all(), [
            'name' => 'required|min:4|max:255'
        ])->validate();

        $name = trim($request->get('name'));

        $source = "https://iandish.ir/web/list?qs=$name&src=1";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $source);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, env('FAKE_USERAGENT'));
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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

        if (!count($result)) return Helper::failed("Not found", 400);
        else {
            $result = [
                'users' => $result
            ];
            return Helper::success($result);
        }
    }

    public function weather(Request $request) {
        Validator::make($request->all(), [
            'lat' => ['required', 'regex:/^(\+|-)?(?:90(?:(?:\.0{1,6})?)|(?:[0-9]|[1-8][0-9])(?:(?:\.[0-9]{1,6})?))$/'],
            'long' => ['required', 'regex:/^(\+|-)?(?:180(?:(?:\.0{1,6})?)|(?:[0-9]|[1-9][0-9]|1[0-7][0-9])(?:(?:\.[0-9]{1,6})?))$/']
        ])->validate();

        $location = null;
        $weatherToken = env('WEATHER_TOKEN');
        $opencageToken = env('OPENCAGE_TOKEN');

        $latitude = trim($request->get('lat'));
        $longitude = trim($request->get('long'));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.weather.com/v1/geocode/$latitude/$longitude/aggregate.json?apiKey=$weatherToken&products=conditionsshort,fcstdaily10short,fcsthourly24short,nowlinks");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $weather = json_decode(curl_exec($ch));

        if (@$weather->metadata->status_code != 200 || @$weather->conditionsshort->metadata->status_code != 200)
            return Helper::failed("502", "Weather error");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.opencagedata.com/geocode/v1/json?q=$latitude+$longitude&key=$opencageToken");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $opencage = json_decode(curl_exec($ch));

        if (@$opencage->status->code == 200) {
            if (!empty($opencage->results[0]->components->city)) $location = $opencage->results[0]->components->city;
            else if (!empty($opencage->results[0]->components->state)) $location = $opencage->results[0]->components->state;
        }

        $phrase = str_replace(" ", "_", strtoupper($weather->conditionsshort->observation->wx_phrase));
        if (strstr($phrase, "/")) {
            $exp = explode("/", $phrase);
            $phrase = [];
            for ($i = 0; $i < count($exp); $i++) {
                $phrase[] = $exp[$i];
            }
        }

        $result = [
            'location' => $location,
            'temp' => [
                'now' => $weather->conditionsshort->observation->metric->temp,
                'min' => $weather->conditionsshort->observation->metric->min_temp,
                'max' => $weather->conditionsshort->observation->metric->max_temp
            ],
            'phrase' => $phrase,
            'wind' => $weather->conditionsshort->observation->metric->wspd,
            'uv' => $weather->conditionsshort->observation->uv_index == 0 ? null : [
                'index' => $weather->conditionsshort->observation->uv_index,
                'text' => $weather->conditionsshort->observation->uv_desc
            ],
            'rain' => $weather->conditionsshort->observation->metric->precip_total
        ];

        return Helper::success($result);
    }
}