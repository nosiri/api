<?php
namespace App\Http\Middleware;

use Closure;

class HeadersMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $headers = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With'
        ];

        if ($request->isMethod('OPTIONS')) return response()->json(['status' => true], 200, $headers);

        $responseHeaders = [];
        foreach ($headers as $key => $value) {
            $responseHeaders[] = [$key => $value];
        }

        if (env('APP_HTTPS') && !$request->secure() && env('APP_ENV') == 'production') {
            return redirect($request->getRequestUri(), 302, $responseHeaders, true);
        }

        return $next($request);
    }
}