<?php
namespace App\Http\Middleware;

use Closure;

class Headers {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $headers = [
            'Access-Control-Allow-Origin' => env('APP_ORIGIN'),
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ];

        if ($request->isMethod('OPTIONS')) return response()->json(['ok' => true], 200, $headers);

        $response = $next($request);
        $responseHeaders = [];
        foreach ($headers as $key => $value) {
            $responseHeaders[$key] = $value;
            $response->header($key, $value);
        }

        if (env('APP_ENV') == 'production' && env('APP_HTTPS') && !$request->secure())
            return redirect($request->getRequestUri(), 302, $responseHeaders, true);
        else return $response;
    }
}