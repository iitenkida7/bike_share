<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Closure;

class CheckLine
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $signature =  base64_encode(hash_hmac('sha256', $request->getContent(), Config::get('bike_share.line.channelSecret'), true));
        if ($signature !== $request->header('X-Line-Signature')) {
            Log::debug($request->getContent());
            abort(403, 'Unauthorized action.');
        }
        if (empty(json_decode($request->getContent(), true)['events'][0]['message']['type'])) {
            Log::debug($request->getContent());
            abort(403, 'Unauthorized action.');
        }
        return $next($request);
    }
}
