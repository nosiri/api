<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NamavaController extends Controller {
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.namava.ir/api2/movie/search");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, env('NAMAVA_USERAGENT'));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded','auth_token: ' . env('NAMAVA_TOKEN')]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "Text=$query&count=20&page=1");
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        if (!count($response)) return AppHelper::instance()->failed("Not found", 400);
        else {
            $result = [];
            for ($i = 0; $i < count($response); $i++) {
                if (!in_array($response[$i]->PostTypeSlug, ['episode', 'movie'])) continue;
                $movie = $response[$i];
                $result[] = [
                    'title' => $movie->Name,
                    'id' => $movie->PostId,
                ];
            }

            return AppHelper::instance()->success($result);
        }
    }

    public function get(Request $request) {
        $id = trim($request->get('id'));

        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|min:1'
        ]);
        if ($validator->fails()) {
            $error = $validator->errors()->first();
            return AppHelper::instance()->failed($error, 400);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://www.namava.ir/api2/movie/$id");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = @json_decode(curl_exec($ch));
        curl_close($ch);

        if ($id != @$response->PostId || !in_array($response->PostTypeSlug, ["movie", "episode"])) return AppHelper::instance()->failed("Incorrect ID", 400);
        else {
            $title = $response->Name;
            $image = $response->ImageAbsoluteUrl;

            $description = trim(html_entity_decode(strip_tags(str_replace(["<br>", "<br/>", "<br />"], "\r\n", $response->FullDescription))));
            preg_match_all('/^داستان (?:فیلم|قسمت):\r\n.+/m', $description, $description);
            $description = $description[0][0];

            $year = null;
            $duration = null;
            $rate = null;

            for ($i = 0; $i < count($response->PostTypeAttrValueModels); $i++) {
                if ($response->PostTypeAttrValueModels[$i]->Key == "movie-year") $year = (int)$response->PostTypeAttrValueModels[$i]->Value;
                else if ($response->PostTypeAttrValueModels[$i]->Key == "movie-duration") $duration = (int)$response->PostTypeAttrValueModels[$i]->Value;
                else if ($response->PostTypeAttrValueModels[$i]->Key == "movie-imdb-rate") $rate = (float)$response->PostTypeAttrValueModels[$i]->Value;
            }

            $genres = [];
            for ($i=0; $i < count($response->PostCategories); $i++)
                $genres[] = $response->PostCategories[$i]->Name;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://shahbaghi.com/F/data/namavaa/stream.php");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "id=$id");
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            $link = @json_decode(curl_exec($ch))->link;
            curl_close($ch);

            $result = [
                'title' => $title,
                'image' => $image,
                'description' => $description,
                'year' => $year,
                'duration' => $duration,
                'genres' => $genres,
                'rate' => $rate,
                'link' => $link
            ];

            if (!empty($link)) return AppHelper::instance()->success($result);
            else return AppHelper::instance()->failed("Internal Error", 502);
        }
    }
}