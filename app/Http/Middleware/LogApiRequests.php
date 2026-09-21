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

        $filteredRequestData = $this->filterSensitiveData(
            $request->isJson()
                ? $request->json()->all()
                : $request->all()
        );
        
        $contentType = $response->headers->get('Content-Type');

        // Skip logging for file/binary responses
        if (
            str_contains($contentType, 'application/pdf') ||
            str_contains($contentType, 'application/octet-stream') ||
            str_contains($contentType, 'image/') ||
            str_contains($contentType, 'zip')
        ) {
            $decodedContent = 'Binary/File response not logged';
        } else {
            $content = method_exists($response, 'getContent')
                ? $response->getContent()
                : 'Streamed response';

            $decodedContent = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedContent = $content;
            }
        }

        $logLevel = $status >= 400 ? 'warning' : 'info';
        $message = $status >= 400 ? 'Failed API Call' : 'API Call';

        Log::channel('api')->{$logLevel}($message, [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'body' => $filteredRequestData,
            'status' => $status,
            'response' => is_array($decodedContent)
                ? $this->filterSensitiveData($decodedContent)
                : $decodedContent,
        ]);

        return $response;
    }

    private function filterSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

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
