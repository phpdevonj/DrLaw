<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */

    protected $sensitiveKeys = ['password', 'password_confirmation', 'token', 'access_token', 'api_token'];

    public function handle(Request $request, Closure $next)
    {        
        $response = $next($request);

        $status = $response->getStatusCode();

        // Log all API requests and responses
        $filteredRequestData = $this->filterSensitiveData($request->all());

        $content = method_exists($response, 'getContent') ? $response->getContent() : 'Streamed response';

        $decodedContent = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decodedContent = $content;
        }

        $logLevel = $status >= 400 ? 'warning' : 'info';
        $message = $status >= 400 ? 'Failed API Call' : 'API Call';

        Log::channel('api')->{$logLevel}($message, [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'body' => $filteredRequestData,
            'status' => $status,
            'response' => $this->filterSensitiveData($decodedContent),
        ]);

        return $response;
    }

    private function filterSensitiveData(array $data)
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, $this->sensitiveKeys)) {
                $value = '*****'; // Mask value
            } elseif (is_array($value)) {
                $value = $this->filterSensitiveData($value); // Recursive for nested arrays
            }
        }
        return $data;
    }
}
