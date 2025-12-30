<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;
use Brian2694\Toastr\Facades\Toastr;

class ChatbotController extends Controller
{
    /**
     * Get the chatbot API base URL from config or env
     */
    private function getApiBaseUrl()
    {
        return config('chatbot.api_base_url', env('CHATBOT_API_BASE_URL'));
    }

    /**
     * Get the full API endpoint URL
     */
    private function getApiUrl($endpoint = '')
    {
        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $endpoint = ltrim($endpoint, '/');
        return $baseUrl . '/api/chatbot/admin/chatbots' . ($endpoint ? '/' . $endpoint : '');
    }

    /**
     * Get user-facing API endpoint URL (for available chatbots)
     */
    private function getUserApiUrl($endpoint = '')
    {
        $baseUrl = rtrim($this->getApiBaseUrl(), '/');
        $endpoint = ltrim($endpoint, '/');
        return $baseUrl . '/api/chatbot/user' . ($endpoint ? '/' . $endpoint : '');
    }

    /**
     * Get available chatbots for course assignment
     */
    public function getAvailableChatbots()
    {
        try {
            $url = $this->getUserApiUrl('available') . '/?page=1';
            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $data['results'] ?? [];
            }
            return [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get HTTP client with authentication headers
     */
    private function getHttpClient()
    {
        $client = Http::timeout(config('chatbot.api_timeout', 30));
        $authMethod = config('chatbot.auth_method', 'api_key');
        
        // Auto-detect: If API key is set and auth_method is 'none' or empty, use api_key
        $apiKey = config('chatbot.api_key', '');
        if (($authMethod === 'none' || empty($authMethod)) && !empty($apiKey)) {
            $authMethod = 'api_key';
        }

        switch ($authMethod) {
            case 'bearer':
                $token = config('chatbot.bearer_token', '');
                if (!empty($token)) {
                    $client->withToken($token);
                }
                break;

            case 'api_key':
                $apiKey = config('chatbot.api_key', '');
                $headerName = config('chatbot.api_key_header', 'X-API-Key');
                if (!empty($apiKey)) {
                    // For X-API-Key or other custom headers, use as-is
                    $client->withHeaders([$headerName => $apiKey]);
                }
                break;

            case 'basic':
                $username = config('chatbot.basic_username', '');
                $password = config('chatbot.basic_password', '');
                if (!empty($username) && !empty($password)) {
                    $client->withBasicAuth($username, $password);
                }
                break;

            case 'custom':
                $customHeaders = config('chatbot.custom_headers', []);
                if (!empty($customHeaders)) {
                    $client->withHeaders($customHeaders);
                }
                break;

            case 'none':
            default:
                // No authentication
                break;
        }

        return $client;
    }

    /**
     * Display a listing of chatbots
     */
    public function index()
    {
        try {
            return view('backend.chatbot.index');
        } catch (Exception $e) {
            Toastr::error('Failed to load chatbot page', 'Error');
            return redirect()->back();
        }
    }

    /**
     * Get chatbots data via AJAX (for DataTable)
     */
    public function getChatbots(Request $request)
    {
        try {
            // Handle DataTable pagination parameters
            $start = $request->get('start', 0);
            $length = $request->get('length', 10);
            $page = $request->get('page', floor($start / $length) + 1);
            
            // If page is explicitly provided, use it; otherwise calculate from start/length
            if (!$request->has('page')) {
                $page = floor($start / $length) + 1;
            }
            
            $url = $this->getApiUrl() . '/?page=' . $page;

            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                $data = $response->json();
                
                // Transform API response to DataTable format
                return response()->json([
                    'draw' => $request->get('draw', 1),
                    'recordsTotal' => $data['count'] ?? 0,
                    'recordsFiltered' => $data['count'] ?? 0,
                    'data' => $data['results'] ?? [],
                    'count' => $data['count'] ?? 0,
                    'next' => $data['next'] ?? null,
                    'previous' => $data['previous'] ?? null,
                    'results' => $data['results'] ?? []
                ]);
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
     * Show the form for creating a new chatbot
     */
    public function create()
    {
        return view('backend.chatbot.create');
    }

    /**
     * Store a newly created chatbot
     */
    public function store(Request $request)
    {
        try {
            $url = $this->getApiUrl();
            
            // Prepare multipart form data
            $multipart = [];
            
            // Add text fields
            $multipart[] = [
                'name' => 'name',
                'contents' => $request->input('name')
            ];
            
            if ($request->has('description')) {
                $multipart[] = [
                    'name' => 'description',
                    'contents' => $request->input('description')
                ];
            }
            
            if ($request->has('system_prompt')) {
                $multipart[] = [
                    'name' => 'system_prompt',
                    'contents' => $request->input('system_prompt')
                ];
            }
            
            if ($request->has('language_style')) {
                $multipart[] = [
                    'name' => 'language_style',
                    'contents' => $request->input('language_style')
                ];
            }
            
            if ($request->has('specialization')) {
                $multipart[] = [
                    'name' => 'specialization',
                    'contents' => $request->input('specialization')
                ];
            }
            
            // Convert is_active to string 'true' or 'false'
            $isActive = $request->input('is_active', 'false');
            if (is_string($isActive)) {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            } else {
                $isActive = $isActive ? 'true' : 'false';
            }
            
            $multipart[] = [
                'name' => 'is_active',
                'contents' => $isActive
            ];
            
            // Add document files
            // Check both hasFile and allFiles to ensure we catch files
            $hasFiles = $request->hasFile('document_files');
            $allFiles = $request->allFiles();
            
            if ($hasFiles || isset($allFiles['document_files'])) {
                $files = $request->file('document_files');
                
                // If document_files is not an array, make it one
                if (!is_array($files)) {
                    $files = $files ? [$files] : [];
                }
                
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $filePath = $file->getRealPath();
                        $fileHandle = fopen($filePath, 'r');
                        
                        if ($fileHandle === false) {
                            Log::error('Failed to open file for upload', [
                                'file_path' => $filePath,
                                'file_name' => $file->getClientOriginalName()
                            ]);
                            continue;
                        }
                        
                        $multipart[] = [
                            'name' => 'document_files',
                            'contents' => $fileHandle,
                            'filename' => $file->getClientOriginalName(),
                            'headers' => [
                                'Content-Type' => $file->getMimeType()
                            ]
                        ];
                    }
                }
            }

            // Log multipart data for debugging
            Log::debug('Chatbot store request', [
                'url' => $url,
                'multipart_count' => count($multipart),
                'has_files' => $hasFiles,
                'all_files_keys' => array_keys($allFiles),
                'file_count' => $hasFiles ? (is_array($request->file('document_files')) ? count($request->file('document_files')) : 1) : 0
            ]);

            $response = $this->getHttpClient()->asMultipart()->post($url, $multipart);

            if ($response->successful()) {
                Toastr::success('Chatbot created successfully', 'Success');
                return response()->json([
                    'success' => true,
                    'message' => 'Chatbot created successfully',
                    'data' => $response->json()
                ]);
            } else {
                $errorData = $response->json();
                return response()->json([
                    'success' => false,
                    'error' => $errorData['message'] ?? 'Failed to create chatbot',
                    'errors' => $errorData['errors'] ?? []
                ], $response->status());
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified chatbot
     */
    public function show($id)
    {
        try {
            $url = $this->getApiUrl($id);
            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                return response()->json($response->json());
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
     * Show the form for editing the specified chatbot
     */
    public function edit($id)
    {
        return view('backend.chatbot.edit', compact('id'));
    }

    /**
     * Update the specified chatbot
     */
    public function update(Request $request, $id)
    {
        try {
            $url = $this->getApiUrl($id);
            
            // Prepare multipart form data
            $multipart = [];
            
            // Add text fields
            $multipart[] = [
                'name' => 'name',
                'contents' => $request->input('name')
            ];
            
            if ($request->has('description')) {
                $multipart[] = [
                    'name' => 'description',
                    'contents' => $request->input('description')
                ];
            }
            
            if ($request->has('system_prompt')) {
                $multipart[] = [
                    'name' => 'system_prompt',
                    'contents' => $request->input('system_prompt')
                ];
            }
            
            if ($request->has('language_style')) {
                $multipart[] = [
                    'name' => 'language_style',
                    'contents' => $request->input('language_style')
                ];
            }
            
            if ($request->has('specialization')) {
                $multipart[] = [
                    'name' => 'specialization',
                    'contents' => $request->input('specialization')
                ];
            }
            
            // Convert is_active to string 'true' or 'false'
            $isActive = $request->input('is_active', 'false');
            if (is_string($isActive)) {
                $isActive = filter_var($isActive, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
            } else {
                $isActive = $isActive ? 'true' : 'false';
            }
            
            $multipart[] = [
                'name' => 'is_active',
                'contents' => $isActive
            ];
            
            // Add document files
            // Check both hasFile and allFiles to ensure we catch files
            $hasFiles = $request->hasFile('document_files');
            $allFiles = $request->allFiles();
            
            if ($hasFiles || isset($allFiles['document_files'])) {
                $files = $request->file('document_files');
                
                // If document_files is not an array, make it one
                if (!is_array($files)) {
                    $files = $files ? [$files] : [];
                }
                
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $filePath = $file->getRealPath();
                        $fileHandle = fopen($filePath, 'r');
                        
                        if ($fileHandle === false) {
                            Log::error('Failed to open file for upload', [
                                'file_path' => $filePath,
                                'file_name' => $file->getClientOriginalName()
                            ]);
                            continue;
                        }
                        
                        $multipart[] = [
                            'name' => 'document_files',
                            'contents' => $fileHandle,
                            'filename' => $file->getClientOriginalName(),
                            'headers' => [
                                'Content-Type' => $file->getMimeType()
                            ]
                        ];
                    }
                }
            }

            $response = $this->getHttpClient()->asMultipart()->put($url, $multipart);

            if ($response->successful()) {
                Toastr::success('Chatbot updated successfully', 'Success');
                return response()->json([
                    'success' => true,
                    'message' => 'Chatbot updated successfully',
                    'data' => $response->json()
                ]);
            } else {
                $errorData = $response->json();
                return response()->json([
                    'success' => false,
                    'error' => $errorData['message'] ?? 'Failed to update chatbot',
                    'errors' => $errorData['errors'] ?? []
                ], $response->status());
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified chatbot
     */
    public function destroy($id)
    {
        try {
            $url = $this->getApiUrl($id);
            $response = $this->getHttpClient()->delete($url);

            if ($response->successful()) {
                Toastr::success('Chatbot deleted successfully', 'Success');
                return response()->json([
                    'success' => true,
                    'message' => 'Chatbot deleted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to delete chatbot',
                    'message' => $response->body()
                ], $response->status());
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'An error occurred',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}

