<?php

namespace App\Http\Middleware;

use App\Models\UserActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class UserActivityLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Auth::check()) {
            $user = Auth::user();
            $action_type = $request->method();
            $description = $this->generateDescription($request, $response);
            $table_name = $this->getTableNameFromRequest($request);
            $record_id = $this->getRecordIdFromRequest($request, $response);

            UserActivityLog::create([
                'user_id' => $user->id,
                'action_type' => $action_type,
                'description' => $description,
                'table_name' => $table_name,
                'record_id' => $record_id,
                'metadata' => ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()],
            ]);
        }

        return $response;
    }

    private function generateDescription(Request $request, $response)
    {
        $method = $request->method();
        $path = $request->path();
        $status = $response->getStatusCode();

        $description = "User performed a {$method} request to {$path}. Response status: {$status}.";

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $requestData = $request->all();
            if (!empty($requestData)) {
                $description .= " Request data: " . json_encode($requestData);
            }
        }

        return $description;
    }

    private function getTableNameFromRequest(Request $request)
    {
        // Attempt to infer table name from the request path
        $segments = $request->segments();
        // Example: /api/cars -> cars, /api/users -> users
        if (count($segments) > 1 && $segments[0] === 'api') {
            // Pluralize the resource name, e.g., 'car' becomes 'cars'
            // This is a simple heuristic and might need adjustment based on your route structure
            return Str::plural($segments[1]);
        }
        return null;
    }

    private function getRecordIdFromRequest(Request $request, $response)
    {
        // For POST requests, the ID is often in the response body after creation
        if ($request->isMethod('POST') && $response->isSuccessful()) {
            $responseData = json_decode($response->getContent(), true);
            if (isset($responseData['data']['id'])) {
                return $responseData['data']['id'];
            }
            if (isset($responseData['id'])) {
                return $responseData['id'];
            }
        }

        // For PUT, PATCH, DELETE, the ID is often in the URL
        $segments = $request->segments();
        // Example: /api/cars/{id}
        if (count($segments) > 2 && $segments[0] === 'api') {
            // Check if the last segment looks like a UUID or an ID
            $lastSegment = end($segments);
            if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $lastSegment) || is_numeric($lastSegment)) {
                return $lastSegment;
            }
        }

        return null;
    }
}
