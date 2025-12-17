@php
    // Get enrolled courses with chatbots
    $enrolledCourses = \Modules\CourseSetting\Entities\CourseEnrolled::where('user_id', Auth::id())
        ->whereHas('course', function($q) {
            $q->whereNotNull('chatbot_id');
        })
        ->with('course')
        ->latest()
        ->limit(5)
        ->get();
@endphp

@if(count($enrolledCourses) > 0)
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

@push('styles')
    <style>
        .chatbot-widget .chatbot-item {
            transition: all 0.3s ease;
        }
        .chatbot-widget .chatbot-toggle:hover {
            background-color: #f8f9fa;
        }
        .chatbot-messages {
            scroll-behavior: smooth;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
        }
        .message.user {
            background-color: #007bff;
            color: white;
            margin-left: 20%;
            text-align: right;
        }
        .message.bot {
            background-color: #e9ecef;
            color: #333;
            margin-right: 20%;
        }
        .chatbot-query:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
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
    <script src="{{ asset('public/frontend/js/chatbot-widget.js') }}"></script>
@endpush

