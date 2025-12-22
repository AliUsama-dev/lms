<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chatbot API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is for the third-party chatbot API integration.
    | Set the base URL of the chatbot API service.
    |
    */

    'api_base_url' => env('CHATBOT_API_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | API Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for API requests.
    |
    */

    'api_timeout' => env('CHATBOT_API_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Authentication Method
    |--------------------------------------------------------------------------
    |
    | Choose the authentication method for the chatbot API:
    | - 'bearer': Bearer token authentication (Authorization: Bearer {token})
    | - 'api_key': API key authentication (X-API-Key or ApiKey header)
    | - 'basic': Basic authentication (username:password)
    | - 'custom': Custom header authentication
    | - 'none': No authentication
    |
    */

    'auth_method' => env('CHATBOT_AUTH_METHOD', 'api_key'),

    /*
    |--------------------------------------------------------------------------
    | Bearer Token
    |--------------------------------------------------------------------------
    |
    | The bearer token for API authentication.
    | Used when auth_method is 'bearer'
    |
    */

    'bearer_token' => env('CHATBOT_BEARER_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The API key for authentication.
    | Used when auth_method is 'api_key'
    |
    */

    'api_key' => env('CHATBOT_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Key Header Name
    |--------------------------------------------------------------------------
    |
    | The header name for API key authentication.
    | Common values: 'X-API-Key', 'ApiKey', 'X-Api-Key', 'Authorization'
    |
    */

    'api_key_header' => env('CHATBOT_API_KEY_HEADER', 'X-API-Key'),

    /*
    |--------------------------------------------------------------------------
    | Basic Authentication
    |--------------------------------------------------------------------------
    |
    | Username and password for basic authentication.
    | Used when auth_method is 'basic'
    |
    */

    'basic_username' => env('CHATBOT_BASIC_USERNAME', ''),
    'basic_password' => env('CHATBOT_BASIC_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Custom Headers
    |--------------------------------------------------------------------------
    |
    | Custom headers for authentication.
    | Format: ['Header-Name' => 'value']
    | Used when auth_method is 'custom'
    |
    */

    'custom_headers' => [
        // Example: 'X-Custom-Auth' => env('CHATBOT_CUSTOM_AUTH', ''),
    ],
];

