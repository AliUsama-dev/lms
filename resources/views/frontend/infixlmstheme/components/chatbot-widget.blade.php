@php
    // OLD CHATBOT WIDGET - COMPLETELY DISABLED
    // This widget has been replaced by the floating chatbot icon
    // See: floating-chatbot.blade.php
@endphp

{{-- OLD WIDGET DISABLED - DO NOT RENDER --}}
@if(false)
    <div class="dashboard_card chatbot-widget">
        <div class="head d-flex align-items-center justify-content-between mb-4">
            <h4>Course Chatbot Assistant</h4>
        </div>
        <div class="chatbot-list">
            @foreach($enrolledCourses as $enrollment)
                @php
                    $course = $enrollment->course;
                @endphp
                <div class="chatbot-item mb-3" data-course-id="{{$course->id}}" data-chatbot-id="{{$course->chatbot_id}}">
                    <div class="d-flex align-items-center justify-content-between p-3 border rounded cursor-pointer chatbot-toggle" style="cursor: pointer;">
                        <div>
                            <h6 class="mb-1">{{$course->title}}</h6>
                            <small class="text-muted">Click to chat with course assistant</small>
                        </div>
                        <i class="fas fa-robot text-primary"></i>
                    </div>
                    <div class="chatbot-container mt-3" style="display: none;">
                        <div class="chatbot-messages border rounded p-3" style="height: 400px; overflow-y: auto; background: #f8f9fa;">
                            <div class="message-list"></div>
                        </div>
                        <div class="chatbot-input mt-2">
                            <div class="input-group">
                                <input type="text" class="form-control chatbot-query" placeholder="Type your message..." data-chatbot-id="{{$course->chatbot_id}}">
                                <button class="btn btn-primary chatbot-send" type="button" data-chatbot-id="{{$course->chatbot_id}}">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- OLD WIDGET COMPLETELY DISABLED - STYLES AND JS ALSO DISABLED --}}
@if(false)
@push('styles')
    <style>
        /* Chatbot Widget Styles - Updated */
        .chatbot-widget {
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .chatbot-widget .chatbot-item {
            transition: all 0.3s ease;
        }
        
        .chatbot-widget .chatbot-toggle {
            transition: all 0.3s ease;
            border: 1px solid #e0e0e0 !important;
            background: #fff;
        }
        
        .chatbot-widget .chatbot-toggle:hover {
            background-color: #f8f9fa !important;
            border-color: #007bff !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.1);
        }
        
        .chatbot-widget .chatbot-toggle.active {
            background-color: #f0f7ff !important;
            border-color: #007bff !important;
        }
        
        .chatbot-widget .chatbot-toggle h6 {
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .chatbot-widget .chatbot-toggle .fa-robot {
            font-size: 24px;
            color: #007bff;
        }
        
        .chatbot-container {
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .chatbot-messages {
            scroll-behavior: smooth;
            background: #f8f9fa;
            border: 1px solid #e0e0e0 !important;
            border-radius: 8px;
            padding: 20px !important;
            min-height: 400px;
            max-height: 500px;
        }
        
        .message-list {
            display: flex !important;
            flex-direction: column !important;
            gap: 12px;
        }
        
        .message {
            display: flex !important;
            margin-bottom: 0;
            padding: 0;
            border-radius: 0;
            max-width: 75%;
            animation: messageSlideIn 0.3s ease-out;
        }
        
        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* User messages - LEFT SIDE */
        .message.user,
        .chatbot-widget .message.user,
        .chatbot-widget .message-list .message.user,
        .message-list .message.user {
            align-self: flex-start !important;
            margin-right: auto !important;
            margin-left: 0 !important;
            justify-self: flex-start !important;
        }
        
        /* Bot messages - RIGHT SIDE */
        .message.bot,
        .chatbot-widget .message.bot,
        .chatbot-widget .message-list .message.bot,
        .message-list .message.bot {
            align-self: flex-end !important;
            margin-left: auto !important;
            margin-right: 0 !important;
            justify-self: flex-end !important;
        }
        
        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            line-height: 1.5;
            font-size: 14px;
            display: block;
        }
        
        /* User messages - Left side with blue filled background */
        .chatbot-widget .message.user .message-content,
        .chatbot-widget .message-list .message.user .message-content,
        .message.user .message-content {
            background: #007bff !important;
            background-color: #007bff !important;
            color: #ffffff !important;
            border: 2px solid #007bff !important;
            border-bottom-left-radius: 4px !important;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2) !important;
        }
        
        /* Bot messages - Right side with light grey filled background */
        .chatbot-widget .message.bot .message-content,
        .chatbot-widget .message-list .message.bot .message-content,
        .message.bot .message-content {
            background: #e0e0e0 !important;
            background-color: #e0e0e0 !important;
            color: #2c3e50 !important;
            border: 2px solid #e0e0e0 !important;
            border-bottom-right-radius: 4px !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1) !important;
        }
        
        /* Override any background colors that might be applied globally */
        .chatbot-widget .message.bot {
            background: transparent !important;
            background-color: transparent !important;
        }
        
        .chatbot-widget .message.user {
            background: transparent !important;
            background-color: transparent !important;
        }
        
        .message.bot.error .message-content {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }
        
        .message.loading .message-content {
            background: #e0e0e0 !important;
            background-color: #e0e0e0 !important;
            border: 2px solid #e0e0e0 !important;
            border-bottom-right-radius: 4px !important;
        }
        
        .message.streaming .message-content {
            background: #e0e0e0 !important;
            background-color: #e0e0e0 !important;
            border: 2px solid #e0e0e0 !important;
            border-bottom-right-radius: 4px !important;
        }
        
        /* Typing Indicator */
        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
            margin-left: 8px;
        }
        
        .typing-indicator span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #999;
            animation: typing 1.4s infinite;
        }
        
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        
        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
        
        .loading-text {
            color: #6c757d;
            font-size: 13px;
            font-style: italic;
            margin-left: 8px;
        }
        
        /* Input Styling */
        .chatbot-input {
            margin-top: 15px;
        }
        
        .chatbot-input .input-group {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 25px;
            overflow: hidden;
        }
        
        .chatbot-input .form-control {
            border: 1px solid #e0e0e0;
            border-right: none;
            padding: 12px 20px;
            font-size: 14px;
            border-radius: 25px 0 0 25px;
        }
        
        .chatbot-input .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }
        
        .chatbot-input .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }
        
        .chatbot-input .btn {
            border-radius: 0 25px 25px 0;
            padding: 12px 24px;
            border: 1px solid #007bff;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            transition: all 0.3s ease;
        }
        
        .chatbot-input .btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: scale(1.05);
        }
        
        .chatbot-input .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .chatbot-input .btn i {
            font-size: 16px;
        }
        
        /* Scrollbar Styling */
        .chatbot-messages::-webkit-scrollbar {
            width: 6px;
        }
        
        .chatbot-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .chatbot-messages::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }
        
        .chatbot-messages::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        /* Empty State */
        .message-list:empty::before {
            content: "Start a conversation with your course assistant...";
            display: block;
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px 20px;
        }
    </style>
@endpush

@push('js')
    <script>
        window.chatbotRoutes = {
            chat: '{{ route("student.chatbot.chat") }}',
            available: '{{ route("student.chatbot.available") }}'
        };
    </script>
    <script src="{{ asset('public/frontend/js/chatbot-widget.js') }}?v={{ time() }}"></script>
@endpush
@endif

