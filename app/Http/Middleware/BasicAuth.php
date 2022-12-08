<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponses;
use Closure;

class BasicAuth
{
    use ApiResponses;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $reqAuth = base64_decode(str_replace('Basic ', '', $request->header('authorization')));
        $auth = env('AUTH_BASIC_USER') . ':' . env('AUTH_BASIC_PASS');

        if ($reqAuth !== $auth) {
            return $this->failure('Unauthorized', 401);
        }

        return $next($request);
    }
}
