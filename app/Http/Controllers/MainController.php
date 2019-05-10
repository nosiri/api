<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper as Helper;
use App\Helpers\Jdate;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MainController extends Controller {
    public function init(Request $request) {
        $Jdate = Jdate::instance();
        $IP = Helper::realIP();
        $date = $Jdate->jdate("Y/n/j", null, null, null, 'en');
        $dollar = $this->currency()->original["result"]["dollar"];
        $weather = $this->weather($request)->original;

        $result = [
            'ip' => $IP,
            'date' => $date,
            'dollar' => $dollar,
            'weather' => $weather
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
        $result["weather"] = Helper::ping("wttr.in");
        $result["vajehyab"] = Helper::ping("vajehyab.com");
        $result["emamsadegh"] = Helper::ping("iandish.ir");
        $result["npm"] = Helper::ping("npms.io");
        $result["packagist"] = Helper::ping("packagist.org");

        return Helper::success($result);
    }

    public function currency() {
        $Jdate = Jdate::instance();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://bonbast.com/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
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
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
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

            if ($title == " " || substr($link, -5) == "_.mp4") return Helper::failed("Censored video", 403);

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
            'id' => 'string|min:4|max:20'
        ])->validate();

        $location = trim($request->get('location'));

        if (empty($location)) {
            $ip = Helper::instance()->IPInfo();
            if (empty($ip['country'])) return Helper::failed("Not found", 400);
            $location = @$ip["country"];
            if (!empty(@$ip['state'])) $location .= " " . $ip["state"];
        }

        $locationName = ucwords(str_replace("\n", "" , file_get_contents("https://wttr.in/$location?format=%l")));
        if ($locationName == "Not Found" || strstr($locationName, "Unknow location"))
            return Helper::failed("Not found", 400);

        $weather = str_replace("\n", "", file_get_contents("http://wttr.in/$location?format=%c+%t"));

        $result = [
            'location' => $locationName,
            'weather' => $weather
        ];
        return Helper::success($result);
    }

    public function nassaab(Request $request) {
        Validator::make($request->all(), [
            'item' => 'required|string|max:20'
        ])->validate();

        $item = trim($request->get('item'));

        return Helper::success("soon");
    }

    public function filimo(Request $request) {
        Validator::make($request->all(), [
            'username' => 'required|string'
        ])->validate();

        $GUID = env('FILIMO_GUID');
        $AFCN = env('FILIMO_AFCN');
        $tv = "";
        $Mobile = [];

        $username = trim($request->get('username'));

        #Get TV Code
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.filimo.com/etc/api/verifycodeget/devicetype/tvweb');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $response = curl_exec($ch);

        $tvCode = json_decode($response)->verifycodeget->code;
        if (curl_errno($ch) || empty($tvCode)) return Helper::failed("Fetch TV code error", 502);
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
        if (curl_errno($ch) || empty($csrf)) return Helper::failed("Fetch CSRF error", 502);
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
        if (curl_errno($ch) || empty($mobile[0])) return Helper::failed("Fetch first part error", 502);
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
        if (curl_errno($ch) || empty($tempID)) return Helper::failed("Fetch temp id error", 502);
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
        if (curl_errno($ch) || empty($mobile[1])) return Helper::failed("Fetch second part error", 502);
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

        return Helper::success($result);
    }
}