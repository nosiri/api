<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper as Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CinemaController extends Controller {
    private function searchInX($item, $value, $source) {
        for ($i = 0; $i < count($source); $i++) {
            if ($source[$i][$item] == $value) return ['ok' => true, 'id' => $i];
        }
        return ['ok' => false];
    }

    private function findId($name, $source) {
        $result = [
            'filimo' => "",
            'namava' => ""
        ];

        for ($i = 0; $i < count($source); $i++) {
            if ($source[$i]["title"] == $name) $result[$source[$i]["service"]] = $source[$i]["id"];
        }
        if (!empty($result['filimo']) && !empty($result['namava'])) return ['ok' => true, 'result' => $result];
        return ['ok' => false];
    }

    private function namava($query, $action, $count = 20) {
        $result = [];

        if ($action == "search") {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://www.namava.ir/api2/movie/search");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, env('NAMAVA_USERAGENT'));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded','auth_token: ' . env('NAMAVA_TOKEN')]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "Text=$query&count=$count&page=1");
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $response = @json_decode(curl_exec($ch));
            curl_close($ch);

            if (empty($response)) return false;

            for ($i = 0; $i < count($response); $i++) {
                if (!in_array($response[$i]->PostTypeSlug, ['episode', 'movie'])) continue;
                $result[] = $response[$i];
            }
        }
        else if ($action == "movie") {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://www.namava.ir/api2/movie/$query");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, env('NAMAVA_USERAGENT'));
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            $result = @json_decode(curl_exec($ch));
            curl_close($ch);

            if ($query != @$result->PostId || !in_array(@$result->PostTypeSlug, ["movie", "episode"]))
                return false;
        }
        else return false;

        return $result;
    }

    private function filimo($text, $action, $query = null, $count = null) {
        $user = env('FILIMO_USER');
        $token = env('FILIMO_TOKEN');
        if (empty($query)) $query = $action == "search" ? "text" : "uid";
        if (empty($count)) $count = env('FILIMO_SEARCH_COUNT');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.filimo.com/etc/api/$action/$query/$text/luser/$user/ltoken/$token/devicetype/ios");
        curl_setopt($ch, CURLOPT_USERAGENT, '{\"sz\":\"130.0x274.0\",\"dt\":\"iPhone*8\",\"an\":\"Aparat Filimo\",\"sdk\":\"11.4\",\"os\":\"iOS\",\"ds\":\" 2.0\",\"vn\":\"4.0.4\",\"pkg\":\"com.aparat.iFilimo\",\"id\":\"VQ5F86Y2-C9N5-3T4U-O539-B4S5454A3580\",\"afcn\":\"845189796364845\",\"vc\":\"64\",\"camp\":\"seeb\",\"oui\":\"\"}');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = @json_decode(curl_exec($ch))->$action;
        curl_close($ch);

        if (empty($response)) return false;
        if ($action == "movie" && empty($response->uid)) return false;

        if ($action != "movie" && !empty($count) && count($response) > $count) $response = array_slice($response, 0, $count);

        return $response;
    }

    public function home() {
        $movies = [];
        $result = [];

        $filimo = $this->filimo("9911133", "movielistbycat", "catid", 5);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.namava.ir/api2/movie/related");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ["a" => "b"]);
        curl_setopt($ch, CURLOPT_USERAGENT, env('NAMAVA_USERAGENT'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded','auth_token: ' . env('NAMAVA_TOKEN')]);
        $namava = json_decode(curl_exec($ch));

        if (empty($filimo) && empty($namava)) Helper::failed("Empty list", 502);

        if (!empty($filimo)) {
            foreach ($filimo as $movie) {
                $movies[] = [
                    'service' => 'filimo',
                    'title' => $movie->movie_title,
                    'id' => $movie->uid,
                    'image' => $movie->movie_img_s,
                    'description' => $movie->descr
                ];
            }
        }
        if (!empty($namava)) {
            foreach ($namava as $movie) {
                $movies[] = [
                    'service' => 'namava',
                    'title' => $movie->Name,
                    'id' => $movie->PostId,
                    'image' => $movie->ImageAbsoluteUrl,
                    'description' => $movie->ShortDescription
                ];
            }
        }

        //Find and merge duplicates
        foreach ($movies as $movie) {
            $search = $this->searchInX("title", $movie["title"], $result);

            if ($search["ok"]) {
                $IDs = $this->findId($movie["title"], $movies);
                if (!$IDs["ok"]) continue;
                else $IDs = $IDs["result"];

                $result[$search["id"]]["service"] = "both";
                $result[$search["id"]]["id"] = [
                    'filimo' => $IDs["filimo"],
                    'namava' => $IDs["namava"]
                ];
            }
            else {
                $result[] = [
                    'service' => $movie["service"],
                    'title' => $movie["title"],
                    'id' => $movie["id"],
                    'image' => $movie["image"],
                    'description' => $movie["description"],
                ];
            }
        }

        shuffle($result);

        return Helper::success($result);
    }

    public function search(Request $request) {
        Validator::make($request->all(), [
            'query' => 'required'
        ])->validate();

        $query = urlencode($request->get('query'));

        $namava = $this->namava($query, "search");
        $filimo = $this->filimo($query, "search");

        if (!$filimo && !$namava) return Helper::failed("Not found", 400);

        $movies = [];
        $result = [];

        if (!empty($filimo)) {
            foreach ($filimo as $movie) {
                $movies[] = [
                    'service' => 'filimo',
                    'title' => $movie->movie_title,
                    'id' => $movie->uid,
                    'image' => $movie->movie_img_s,
                ];
            }
        }
        if (!empty($namava)) {
            foreach ($namava as $movie) {
                $movies[] = [
                    'service' => 'namava',
                    'title' => $movie->Name,
                    'id' => $movie->PostId,
                    'image' => str_replace("http://", "https://", $movie->ImageAbsoluteUrl),
                ];
            }
        }

        //Find and merge duplicates
        foreach ($movies as $movie) {
            $search = $this->searchInX("title", $movie["title"], $result);

            if ($search["ok"]) {
                $IDs = $this->findId($movie["title"], $movies);
                if (!$IDs["ok"]) continue;
                else $IDs = $IDs["result"];

                $result[$search["id"]]["service"] = "both";
                $result[$search["id"]]["id"] = [
                    'filimo' => $IDs["filimo"],
                    'namava' => $IDs["namava"]
                ];
            }
            else {
                $result[] = [
                    'service' => $movie["service"],
                    'title' => $movie["title"],
                    'id' => $movie["id"],
                    'image' => $movie["image"],
                ];
            }
        }

        if (empty($result)) return Helper::failed("Not found", 400);
        else return Helper::success($result);
    }

    public function get($id) {
        if (empty($id)) return Helper::failed("Bad data", 400);
        $id = trim($id);
        $result = [];

        //Filimo
        if (!is_numeric($id) && strlen($id) == 5) {
            $movie = $this->filimo($id, "movie");

            if (@empty($movie->uid) || @$movie->uid != $id)
                return Helper::failed("Filimo incorrect id", 400);

            $title = $movie->movie_title;
            $image = $movie->movie_img_b;
            $cover = $movie->cover == "yes" ? $movie->cover_adr : null;
            $description = $movie->description;
            $year = (int)$movie->produced_year;
            $duration = (int)$movie->duration;
            $imdb = $movie->imdb_rate && $movie->imdb_rate != 0 ? (float)$movie->imdb_rate : null;
            $rate = $movie->rate_avrage ? (float)$movie->rate_avrage : null;

            $user = env('FILIMO_USER');
            $token = env('FILIMO_TOKEN');
            $link = "https://www.filimo.com/etc/api/movie/uid/$id/luser/$user/ltoken/$token/devicetype/ios";

            $genres = [];
            if (!empty($movie->category_1)) $genres[] = $movie->category_1;
            if (!empty($movie->category_2)) $genres[] = $movie->category_2;

            $result = [
                'title' => $title,
                'image' => $image,
                'cover' => $cover,
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
        }
        //Namava
        else if (is_numeric($id)) {
            $movie = $this->namava($id, "movie");

            if ($id != @$movie->PostId || !in_array($movie->PostTypeSlug, ["movie", "episode"]))
                return Helper::failed("Namava incorrect id", 400);

            $title = $movie->Name;
            $image = $movie->ImageAbsoluteUrl;
            $image = str_replace("http://", "https://", $image);

            $description = trim(html_entity_decode(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\r\n", $movie->FullDescription))));
            preg_match_all('/^داستان (?:فیلم|قسمت):\r\n.+/m', $description, $newDescription);
            $description = !empty(@$newDescription[0][0]) ? $newDescription[0][0] : $description;

            $year = null;
            $duration = null;
            $rate = null;
            $cover = null;

            for ($i = 0; $i < count($movie->PostTypeAttrValueModels); $i++) {
                if ($movie->PostTypeAttrValueModels[$i]->Key == "movie-year") $year = (int)$movie->PostTypeAttrValueModels[$i]->Value;
                else if ($movie->PostTypeAttrValueModels[$i]->Key == "movie-duration") $duration = (int)$movie->PostTypeAttrValueModels[$i]->Value;
                else if ($movie->PostTypeAttrValueModels[$i]->Key == "movie-imdb-rate") $rate = (float)$movie->PostTypeAttrValueModels[$i]->Value;
                else if ($movie->PostTypeAttrValueModels[$i]->Key == "movie-hero-image") $cover = $movie->PostTypeAttrValueModels[$i]->Value;
            }

            $genres = [];
            for ($i=0; $i < count($movie->PostCategories); $i++)
                $genres[] = $movie->PostCategories[$i]->Name;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://shahbaghi.com/F/data/namavaa/stream.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "id=$id");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            $m3u8 = @json_decode(curl_exec($ch))->link;
            curl_close($ch);

            if (empty($m3u8)) $result = false;
            else {
                $result = [
                    'title' => $title,
                    'image' => $image,
                    'cover' => !empty($cover) ? "https://static.namava.ir" . $cover : null,
                    'description' => $description,
                    'year' => $year,
                    'duration' => $duration,
                    'genres' => $genres,
                    'rate' => $rate,
                    'link' => $m3u8
                ];
            }
        }
        //Mix
        else if (strlen($id) > 6 && strstr($id, "-")) {
            $exp = explode("-", $id);

            $filimoId = !is_numeric($exp[0]) && strlen($exp[0]) == 5 ? $exp[0] : null;
            $namavaId = is_numeric($exp[1]) ? $exp[1] : null;
            if (empty($filimoId) || empty($namavaId)) return Helper::failed("Bad format", 400);

            $filimo = $this->filimo($filimoId, "movie");
            if (@empty($filimo->uid) || @$filimo->uid != $filimoId)
                return Helper::failed("Filimo incorrect id", 400);

            $namava = $this->namava($namavaId, "movie");
            if (@$namava->PostId != $namavaId || !in_array($namava->PostTypeSlug, ["movie", "episode"]))
                return Helper::failed("Namava incorrect id", 400);

            if ($filimo->movie_title != $namava->Name)
                return Helper::failed("Not match ids", 400);

            $filimoDescription = $filimo->description;
            $namavaDescription = trim(html_entity_decode(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\r\n", $namava->FullDescription))));
            preg_match_all('/^داستان (?:فیلم|قسمت):\r\n.+/m', $namavaDescription, $namavaDescription);
            $namavaDescription = $namavaDescription[0][0];

            $filimoGenres = [];
            if (!empty($filimo->category_1)) $filimoGenres[] = $filimo->category_1;
            if (!empty($filimo->category_2)) $filimoGenres[] = $filimo->category_2;
            $namavaGenres = [];
            for ($i=0; $i < count($namava->PostCategories); $i++)
                $namavaGenres[] = $namava->PostCategories[$i]->Name;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://shahbaghi.com/F/data/namavaa/stream.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "id=$namavaId");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            $namavaLink = @json_decode(curl_exec($ch))->link;
            curl_close($ch);

            $result = [
                'title' => $filimo->movie_title,
                'image' => $filimo->movie_img_b,
                'description' => mb_strlen($filimoDescription) > $namavaDescription ? $filimoDescription : $namavaDescription,
                'cover' => $filimo->cover == "yes" ? $filimo->cover_adr : false,
                'year' => (int)$filimo->produced_year,
                'duration' => (int)$filimo->duration,
                'genres' => count($filimoGenres) > count($namavaGenres) ? $filimoGenres : $namavaGenres,
                'rate' => $filimo->imdb_rate != 0 ? (float)$filimo->imdb_rate : (float)$filimo->rate_avrage,
                'link' => [
                    'filimo' => $filimo->movie_src,
                    'namava' => $namavaLink
                ]
            ];
        }

        else return Helper::failed("Bad data", 400);

        if ($result) return Helper::success($result);
        else return Helper::failed("Internal error", 500);
    }
}