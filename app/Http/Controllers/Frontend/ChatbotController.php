<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Exception;
use Modules\CourseSetting\Entities\Course;
use Modules\CourseSetting\Entities\CourseEnrolled;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    /**
     * Get the chatbot API base URL from config
     */
    private function getApiBaseUrl()
    {
        return config('chatbot.api_base_url', env('CHATBOT_API_BASE_URL'));
    }

    /**
     * Get HTTP client with authentication headers
     */
    private function getHttpClient()
    {
        $client = Http::timeout(config('chatbot.api_timeout', 30));
        $authMethod = config('chatbot.auth_method', 'api_key');
        
        $apiKey = config('chatbot.api_key', '');
        $headerName = config('chatbot.api_key_header', 'X-API-Key');
        
        if (!empty($apiKey)) {
            $client->withHeaders([$headerName => $apiKey]);
        }

        return $client;
    }

    /**
     * Get available chatbots for a student's enrolled courses
     */
    public function getAvailableChatbots(Request $request)
    {
        try {
            $baseUrl = rtrim($this->getApiBaseUrl(), '/');
            $url = $baseUrl . '/api/chatbot/user/available/?page=' . ($request->get('page', 1));
            
            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json([
                    'error' => 'Failed to fetch chatbots',
                    'message' => $response->body()
                ], $response->status());
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get chatbot for a specific course
     */
    public function getCourseChatbot($courseId)
    {
        try {
            $user = Auth::user();
            
            // Check if user is enrolled in the course
            $enrollment = CourseEnrolled::where('user_id', $user->id)
                ->where('course_id', $courseId)
                ->first();
            
            if (!$enrollment) {
                return response()->json([
                    'error' => 'You are not enrolled in this course'
                ], 403);
            }

            $course = Course::find($courseId);
            
            if (!$course || !$course->chatbot_id) {
                return response()->json([
                    'error' => 'No chatbot assigned to this course'
                ], 404);
            }

            // Get chatbot details from API
            $baseUrl = rtrim($this->getApiBaseUrl(), '/');
            $url = $baseUrl . '/api/chatbot/user/available/?page=1';
            
            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $chatbots = $data['results'] ?? [];
                
                // Find the assigned chatbot
                $chatbot = collect($chatbots)->firstWhere('id', $course->chatbot_id);
                
                if ($chatbot) {
                    return response()->json($chatbot);
                } else {
                    return response()->json([
                        'error' => 'Chatbot not found'
                    ], 404);
                }
            } else {
                return response()->json([
                    'error' => 'Failed to fetch chatbot',
                    'message' => $response->body()
                ], $response->status());
            }
        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send chat message to chatbot (streaming)
     */
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'query' => 'required|string',
                'chatbot_id' => 'required|string',
            ]);

            $baseUrl = rtrim($this->getApiBaseUrl(), '/');
            $url = $baseUrl . '/api/chatbot/user/chat/?stream=true';

            // Prepare request data
            $data = [
                'query' => $request->query,
                'chatbot_id' => $request->chatbot_id,
            ];

            // Prepare headers
            $headers = [
                config('chatbot.api_key_header', 'X-API-Key') => config('chatbot.api_key', ''),
                'Content-Type' => 'application/json',
                'Accept' => 'text/event-stream',
            ];

            // Stream the response using Laravel's HTTP client
            return response()->stream(function() use ($url, $data, $headers) {
                $client = new \GuzzleHttp\Client([
                    'timeout' => 300,
                    'headers' => $headers,
                ]);

                $response = $client->request('POST', $url, [
                    'json' => $data,
                    'stream' => true,
                ]);

                $body = $response->getBody();
                while (!$body->eof()) {
                    echo $body->read(1024);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

