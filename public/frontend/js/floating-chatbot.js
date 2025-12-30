(function($) {
    "use strict";
    
    let _token = $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || $('input[name="csrf_token"]').val() || $('input[name="_token"]').val();
    let activeChatbotId = null;
    let activeCourseId = null;
    let chatHistory = {};
    let isWindowOpen = false;

    $(document).ready(function() {
        initializeFloatingChatbot();
    });

    function initializeFloatingChatbot() {
        // Toggle chatbot window
        $(document).on('click', '#floatingChatbotIcon', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleChatbotWindow();
        });

        // Close chatbot window
        $(document).on('click', '#chatbotCloseBtn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closeChatbotWindow();
        });

        // Close on click outside
        $(document).on('click', function(e) {
            if (isWindowOpen && !$(e.target).closest('.floating-chatbot-wrapper').length) {
                closeChatbotWindow();
            }
        });

        // Course selection change
        $(document).on('change', '#chatbotCourseSelect', function() {
            let $option = $(this).find('option:selected');
            let courseId = $(this).val();
            let chatbotId = $option.data('chatbot-id');
            
            if (courseId && chatbotId) {
                activeCourseId = courseId;
                activeChatbotId = chatbotId;
                enableChatInput();
                loadChatHistory(chatbotId);
            } else {
                activeCourseId = null;
                activeChatbotId = null;
                disableChatInput();
                clearMessages();
            }
        });

        // Auto-select first course if only one
        if ($('#chatbotCourseSelect').is('input[type="hidden"]') && $('#chatbotCourseSelect').data('single-course') === true) {
            let courseId = $('#chatbotCourseSelect').val();
            let chatbotId = $('#chatbotCourseSelect').data('chatbot-id');
            if (courseId && chatbotId) {
                activeCourseId = courseId;
                activeChatbotId = chatbotId;
                enableChatInput();
                loadChatHistory(chatbotId);
            }
        } else if ($('#chatbotCourseSelect').is('select')) {
            // If multiple courses, select first one by default
            let $firstOption = $('#chatbotCourseSelect option:not(:first)').first();
            if ($firstOption.length) {
                $('#chatbotCourseSelect').val($firstOption.val()).trigger('change');
            }
        }
        
        // Check if there are no courses available
        if ($('.chatbot-no-courses').length > 0) {
            // No courses available, input area is already hidden
            return;
        }

        // Send message
        $(document).on('click', '#floatingChatbotSend', function() {
            if (activeChatbotId) {
                sendMessage(activeChatbotId);
            }
        });

        // Send on Enter key
        $(document).on('keypress', '#floatingChatbotInput', function(e) {
            if (e.which === 13 && activeChatbotId) {
                sendMessage(activeChatbotId);
            }
        });
    }

    function toggleChatbotWindow() {
        let $window = $('#floatingChatbotWindow');
        if (isWindowOpen) {
            closeChatbotWindow();
        } else {
            openChatbotWindow();
        }
    }

    function openChatbotWindow() {
        $('#floatingChatbotWindow').slideDown(300);
        isWindowOpen = true;
        
        // Focus input if chatbot is ready
        if (activeChatbotId) {
            setTimeout(function() {
                $('#floatingChatbotInput').focus();
            }, 300);
        }
    }

    function closeChatbotWindow() {
        $('#floatingChatbotWindow').slideUp(300);
        isWindowOpen = false;
    }

    function enableChatInput() {
        $('#floatingChatbotInput').prop('disabled', false);
        $('#floatingChatbotSend').prop('disabled', false);
    }

    function disableChatInput() {
        $('#floatingChatbotInput').prop('disabled', true);
        $('#floatingChatbotSend').prop('disabled', true);
    }

    function clearMessages() {
        $('#floatingMessageList').empty();
    }

    function loadChatHistory(chatbotId) {
        if (!chatHistory[chatbotId]) {
            chatHistory[chatbotId] = [];
            // Add initial greeting
            addBotMessage(chatbotId, "Hello! I'm here to help you with this course. How can I assist you today?");
        } else {
            // Reload previous messages
            clearMessages();
            chatHistory[chatbotId].forEach(function(msg) {
                if (msg.type === 'user') {
                    addUserMessageToUI(chatbotId, msg.message);
                } else {
                    addBotMessageToUI(chatbotId, msg.message);
                }
            });
        }
    }

    function sendMessage(chatbotId) {
        let $input = $('#floatingChatbotInput');
        let query = $input.val().trim();
        
        if (!query || !chatbotId) {
            return;
        }

        // Add user message
        addUserMessage(chatbotId, query);
        $input.val('');
        
        // Disable input while processing
        $input.prop('disabled', true);
        $('#floatingChatbotSend').prop('disabled', true);

        // Get the message container
        let $messageList = $('#floatingMessageList');
        
        // Add loading indicator
        let $loading = $('<div class="message bot loading"><div class="message-content"><div class="typing-indicator"><span></span><span></span><span></span></div><span class="loading-text">Thinking...</span></div></div>');
        $loading.css({
            'align-self': 'flex-end',
            'margin-left': 'auto',
            'margin-right': '0'
        });
        $loading.find('.message-content').css({
            'background': '#e0e0e0',
            'background-color': '#e0e0e0',
            'color': '#2c3e50',
            'border': '2px solid #e0e0e0',
            'border-bottom-right-radius': '4px'
        });
        $messageList.append($loading);
        scrollToBottom($messageList);

        // Send to API with streaming support
        let formData = new FormData();
        formData.append('query', query);
        formData.append('chatbot_id', chatbotId);
        formData.append('_token', _token);

        let xhr = new XMLHttpRequest();
        let botMessage = '';
        let chatStarted = false;
        let lastProcessedIndex = 0;
        let processedLines = new Set();

        xhr.open('POST', chatbotRoutes.chat, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onprogress = function() {
            let response = xhr.responseText;
            let lines = response.split('\n');
            
            for (let i = lastProcessedIndex; i < lines.length; i++) {
                let line = lines[i].trim();
                if (line.startsWith('data: ')) {
                    let lineHash = line.substring(0, 100);
                    if (processedLines.has(lineHash)) {
                        continue;
                    }
                    processedLines.add(lineHash);
                    
                    try {
                        let jsonStr = line.substring(6);
                        if (!jsonStr) continue;
                        
                        let data = JSON.parse(jsonStr);
                        
                        if (data.error === true) {
                            $loading.remove();
                            let errorMsg = data.message || 'Sorry, I encountered an error. Please try again.';
                            addBotMessage(chatbotId, errorMsg, true);
                            enableChatInput();
                            $input.focus();
                            return;
                        } else if (data.type === 'start') {
                            if (!chatStarted) {
                                $loading.remove();
                                botMessage = '';
                                chatStarted = true;
                                $loading = $('<div class="message bot streaming"><div class="message-content"></div></div>');
                                $loading.css({
                                    'align-self': 'flex-end',
                                    'margin-left': 'auto',
                                    'margin-right': '0'
                                });
                                $loading.find('.message-content').css({
                                    'background': '#e0e0e0',
                                    'background-color': '#e0e0e0',
                                    'color': '#2c3e50',
                                    'border': '2px solid #e0e0e0',
                                    'border-bottom-right-radius': '4px'
                                });
                                $messageList.append($loading);
                                scrollToBottom($messageList);
                            }
                        } else if (data.type === 'chunk' && data.content) {
                            botMessage += data.content;
                            if (chatStarted && $loading.length) {
                                $loading.find('.message-content').text(botMessage);
                                $loading.find('.message-content').css({
                                    'background': '#e0e0e0',
                                    'background-color': '#e0e0e0',
                                    'color': '#2c3e50',
                                    'border': '2px solid #e0e0e0',
                                    'border-bottom-right-radius': '4px'
                                });
                                scrollToBottom($messageList);
                            }
                        } else if (data.type === 'done') {
                            if ($loading.length) {
                                $loading.removeClass('streaming');
                                if (botMessage.trim()) {
                                    $loading.find('.message-content').text(botMessage);
                                    $loading.find('.message-content').css({
                                        'background': '#e0e0e0',
                                        'background-color': '#e0e0e0',
                                        'color': '#2c3e50',
                                        'border': '2px solid #e0e0e0',
                                        'border-bottom-right-radius': '4px'
                                    });
                                } else {
                                    $loading.remove();
                                }
                            }
                            if (botMessage.trim()) {
                                chatHistory[chatbotId].push({
                                    type: 'bot',
                                    message: botMessage
                                });
                            }
                            chatStarted = false;
                            botMessage = '';
                            lastProcessedIndex = 0;
                            processedLines.clear();
                            enableChatInput();
                            $input.focus();
                        }
                    } catch (e) {
                        console.error('Error parsing SSE data:', e, line);
                    }
                }
            }
            
            lastProcessedIndex = lines.length;
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                if (chatStarted && botMessage.trim() && $loading.length) {
                    $loading.removeClass('streaming');
                    $loading.find('.message-content').text(botMessage);
                    chatHistory[chatbotId].push({
                        type: 'bot',
                        message: botMessage
                    });
                    chatStarted = false;
                }
            } else {
                $loading.remove();
                let errorMsg = 'Sorry, I encountered an error. Please try again.';
                try {
                    let error = JSON.parse(xhr.responseText);
                    if (error.error || error.message) {
                        errorMsg = error.error || error.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                addBotMessage(chatbotId, errorMsg, true);
            }
            lastProcessedIndex = 0;
            processedLines.clear();
            enableChatInput();
            $input.focus();
        };

        xhr.onerror = function() {
            $loading.remove();
            addBotMessage(chatbotId, 'Network error. Please check your connection and try again.', true);
            lastProcessedIndex = 0;
            processedLines.clear();
            enableChatInput();
            $input.focus();
        };

        xhr.send(formData);
    }

    function addUserMessage(chatbotId, message) {
        addUserMessageToUI(chatbotId, message);
        chatHistory[chatbotId].push({
            type: 'user',
            message: message
        });
    }

    function addUserMessageToUI(chatbotId, message) {
        let $messageList = $('#floatingMessageList');
        let $message = $('<div class="message user"><div class="message-content"></div></div>');
        $message.find('.message-content').text(message);
        $message.css({
            'align-self': 'flex-start',
            'margin-right': 'auto',
            'margin-left': '0'
        });
        $message.find('.message-content').css({
            'background': '#007bff',
            'background-color': '#007bff',
            'color': '#ffffff',
            'border': '2px solid #007bff',
            'border-bottom-left-radius': '4px'
        });
        $messageList.append($message);
        scrollToBottom($messageList);
    }

    function addBotMessage(chatbotId, message, isError = false) {
        addBotMessageToUI(chatbotId, message, isError);
        if (!isError) {
            if (!chatHistory[chatbotId]) {
                chatHistory[chatbotId] = [];
            }
            chatHistory[chatbotId].push({
                type: 'bot',
                message: message
            });
        }
    }

    function addBotMessageToUI(chatbotId, message, isError = false) {
        let $messageList = $('#floatingMessageList');
        let $message = $('<div class="message bot' + (isError ? ' error' : '') + '"><div class="message-content"></div></div>');
        $message.find('.message-content').text(message);
        $message.css({
            'align-self': 'flex-end',
            'margin-left': 'auto',
            'margin-right': '0'
        });
        if (isError) {
            $message.find('.message-content').css({
                'background': '#fff3cd',
                'background-color': '#fff3cd',
                'color': '#856404',
                'border': '2px solid #ffc107'
            });
        } else {
            $message.find('.message-content').css({
                'background': '#e0e0e0',
                'background-color': '#e0e0e0',
                'color': '#2c3e50',
                'border': '2px solid #e0e0e0',
                'border-bottom-right-radius': '4px'
            });
        }
        $messageList.append($message);
        scrollToBottom($messageList);
    }

    function scrollToBottom($container) {
        $container.scrollTop($container[0].scrollHeight);
    }
})(jQuery);

