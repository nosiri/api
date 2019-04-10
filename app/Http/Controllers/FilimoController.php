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

    public function finder() {
        
    }
}