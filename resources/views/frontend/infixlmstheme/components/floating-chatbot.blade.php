@php
    // Get enrolled courses with chatbots (default behaviour for dashboard and generic pages)
    $enrolledCourses = [];
    if (Auth::check() && Auth::user()->role_id == 3) {
        $enrolledCourses = \Modules\CourseSetting\Entities\CourseEnrolled::where('user_id', Auth::id())
            ->whereHas('course', function($q) {
                $q->whereNotNull('chatbot_id');
            })
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();
    }

    // On course viewing pages, only expose the chatbot for the current course (if assigned)
    if (isset($course)) {
        if (
            isset($isEnrolled) &&
            $isEnrolled &&
            !empty($course->chatbot_id) &&
            Auth::check() &&
            Auth::user()->role_id == 3
        ) {
            $enrolledCourses = \Modules\CourseSetting\Entities\CourseEnrolled::where('user_id', Auth::id())
                ->where('course_id', $course->id)
                ->whereHas('course', function($q) {
                    $q->whereNotNull('chatbot_id');
                })
                ->with('course')
                ->get();
        } else {
            // If we are on a course page but the user is not enrolled or no chatbot is assigned,
            // do not show any chatbot entries from other courses.
            $enrolledCourses = collect();
        }
    }
@endphp

@if(Auth::check() && Auth::user()->role_id == 3)
    <!-- Floating Chatbot Icon -->
    <div class="floating-chatbot-wrapper">
        <div class="floating-chatbot-icon" id="floatingChatbotIcon">
            <i class="fas fa-robot"></i>
            @if(count($enrolledCourses) > 1)
                <span class="chatbot-badge">{{count($enrolledCourses)}}</span>
            @endif
        </div>

        <!-- Chatbot Window -->
        <div class="floating-chatbot-window" id="floatingChatbotWindow" style="display: none;">
            <div class="chatbot-window-header">
                <div class="d-flex align-items-center">
                    <i class="fas fa-robot me-2"></i>
                    <h5 class="mb-0">Course Chatbot Assistant</h5>
                </div>
                <button class="chatbot-close-btn" id="chatbotCloseBtn">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Course Selection (if multiple courses) -->
            @if(count($enrolledCourses) > 1)
                <div class="chatbot-course-selector">
                    <select class="form-select" id="chatbotCourseSelect">
                        <option value="">Select a course...</option>
                        @foreach($enrolledCourses as $enrollment)
                            @php
                                $course = $enrollment->course;
                            @endphp
                            <option value="{{$course->id}}" data-chatbot-id="{{$course->chatbot_id}}">
                                {{$course->title}}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif(count($enrolledCourses) == 1)
                @php
                    $course = $enrolledCourses->first()->course;
                @endphp
                <input type="hidden" id="chatbotCourseSelect" value="{{$course->id}}" data-chatbot-id="{{$course->chatbot_id}}" data-single-course="true">
            @else
                <div class="chatbot-no-courses">
                    <div class="text-center p-4">
                        <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No courses with chatbot assistants available.</p>
                        <small class="text-muted">Enroll in a course with a chatbot to start chatting.</small>
                    </div>
                </div>
            @endif

            <!-- Chat Messages Area -->
            @if(count($enrolledCourses) > 0)
                <div class="chatbot-messages-area" id="chatbotMessagesArea">
                    <div class="message-list" id="floatingMessageList"></div>
                </div>

                <!-- Chat Input Area -->
                <div class="chatbot-input-area">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control chatbot-query-input" 
                               id="floatingChatbotInput" 
                               placeholder="Type your message..." 
                               disabled>
                        <button class="btn btn-primary chatbot-send-btn" 
                                id="floatingChatbotSend" 
                                type="button" 
                                disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        /* Floating Chatbot Styles */
        .floating-chatbot-wrapper {
            position: fixed !important;
            bottom: 20px !important;
            right: 20px !important;
            z-index: 99999 !important;
        }

        .floating-chatbot-icon {
            width: 60px !important;
            height: 60px !important;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4) !important;
            transition: all 0.3s ease !important;
            position: relative !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .floating-chatbot-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0, 123, 255, 0.5);
        }

        .floating-chatbot-icon i {
            font-size: 28px;
            color: #ffffff;
        }

        .chatbot-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .floating-chatbot-window {
            position: absolute;
            bottom: 80px;
            right: 0;
            width: 380px;
            height: 600px;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .chatbot-window-header {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chatbot-window-header h5 {
            color: white;
            font-size: 16px;
            font-weight: 600;
        }

        .chatbot-close-btn {
            background: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }

        .chatbot-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .chatbot-course-selector {
            padding: 12px 16px;
            border-bottom: 1px solid #e0e0e0;
        }

        .chatbot-course-selector .form-select {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 14px;
        }

        .chatbot-messages-area {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .chatbot-messages-area .message-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .chatbot-messages-area .message {
            display: flex;
            margin-bottom: 0;
            padding: 0;
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

        .chatbot-messages-area .message.user {
            align-self: flex-start;
            margin-right: auto;
            margin-left: 0;
        }

        .chatbot-messages-area .message.bot {
            align-self: flex-end;
            margin-left: auto;
            margin-right: 0;
        }

        .chatbot-messages-area .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            word-wrap: break-word;
            line-height: 1.5;
            font-size: 14px;
        }

        .chatbot-messages-area .message.user .message-content {
            background: #007bff;
            color: #ffffff;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .chatbot-messages-area .message.bot .message-content {
            background: #e0e0e0;
            color: #2c3e50;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chatbot-messages-area .message.bot.error .message-content {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
        }

        .chatbot-messages-area .message.loading .message-content,
        .chatbot-messages-area .message.streaming .message-content {
            background: #e0e0e0;
            color: #2c3e50;
            border-bottom-right-radius: 4px;
        }

        .typing-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 8px 12px;
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

        .chatbot-input-area {
            padding: 16px;
            border-top: 1px solid #e0e0e0;
            background: white;
        }

        .chatbot-input-area .input-group {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 25px;
            overflow: hidden;
        }

        .chatbot-input-area .form-control {
            border: 1px solid #e0e0e0;
            border-right: none;
            padding: 12px 20px;
            font-size: 14px;
            border-radius: 25px 0 0 25px;
        }

        .chatbot-input-area .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .chatbot-input-area .form-control:disabled {
            background-color: #f8f9fa;
            cursor: not-allowed;
        }

        .chatbot-input-area .btn {
            border-radius: 0 25px 25px 0;
            padding: 12px 24px;
            border: 1px solid #007bff;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            transition: all 0.3s ease;
        }

        .chatbot-input-area .btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
            transform: scale(1.05);
        }

        .chatbot-input-area .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .chatbot-messages-area::-webkit-scrollbar {
            width: 6px;
        }

        .chatbot-messages-area::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .chatbot-messages-area::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .chatbot-messages-area::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .floating-chatbot-window {
                width: calc(100vw - 40px);
                height: calc(100vh - 120px);
                bottom: 80px;
                right: 20px;
            }
        }
    </style>

    @push('js')
    <script>
        window.chatbotRoutes = {
            chat: '{{ route("student.chatbot.chat") }}',
            available: '{{ route("student.chatbot.available") }}'
        };
    </script>
    <script src="{{ asset('public/frontend/js/floating-chatbot.js') }}?v={{ time() }}"></script>
    @endpush
@endif

