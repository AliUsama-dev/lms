(function($) {
    "use strict";
    
    let _token = $('meta[name="csrf-token"]').attr('content') || $('meta[name="_token"]').attr('content') || $('input[name="csrf_token"]').val() || $('input[name="_token"]').val();
    let activeChatbotId = null;
    let chatHistory = {};

    $(document).ready(function() {
        initializeChatbot();
    });

    function initializeChatbot() {
        // Toggle chatbot container
        $(document).on('click', '.chatbot-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            let $container = $(this).siblings('.chatbot-container');
            let $item = $(this).closest('.chatbot-item');
            let chatbotId = $item.data('chatbot-id');
            
            // Close other chatbots
            $('.chatbot-container').not($container).slideUp();
            $('.chatbot-item').not($item).removeClass('active');
            
            // Toggle current
            $container.slideToggle();
            $item.toggleClass('active');
            
            if ($container.is(':visible')) {
                activeChatbotId = chatbotId;
                // Initialize chat if not already done
                if (!chatHistory[chatbotId]) {
                    chatHistory[chatbotId] = [];
                    // Add initial greeting - bot message should be on right side
                    addBotMessage(chatbotId, "Hello! I'm here to help you with this course. How can I assist you today?");
                }
            }
        });

        // Send message
        $(document).on('click', '.chatbot-send', function() {
            let chatbotId = $(this).data('chatbot-id');
            sendMessage(chatbotId);
        });

        // Send on Enter key
        $(document).on('keypress', '.chatbot-query', function(e) {
            if (e.which === 13) {
                let chatbotId = $(this).data('chatbot-id');
                sendMessage(chatbotId);
            }
        });
    }

    function sendMessage(chatbotId) {
        let $input = $(`.chatbot-query[data-chatbot-id="${chatbotId}"]`);
        let query = $input.val().trim();
        
        if (!query) {
            return;
        }

        // Add user message
        addUserMessage(chatbotId, query);
        $input.val('');
        
        // Disable input while processing
        $input.prop('disabled', true);
        $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', true);

        // Get the message container
        let $messageList = $(`.chatbot-item[data-chatbot-id="${chatbotId}"] .message-list`);
        
        // Add loading indicator with typing animation
        let $loading = $('<div class="message bot loading"><div class="message-content"><div class="typing-indicator"><span></span><span></span><span></span></div><span class="loading-text">Thinking...</span></div></div>');
        // Force alignment to right side
        $loading.css({
            'align-self': 'flex-end',
            'margin-left': 'auto',
            'margin-right': '0'
        });
        // Apply styles to loading message - light grey filled, right side
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
        let lastProcessedIndex = 0; // Track which lines we've already processed
        let processedLines = new Set(); // Track processed line hashes to avoid duplicates

        xhr.open('POST', chatbotRoutes.chat, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onprogress = function() {
            let response = xhr.responseText;
            let lines = response.split('\n');
            
            // Only process new lines
            for (let i = lastProcessedIndex; i < lines.length; i++) {
                let line = lines[i].trim();
                if (line.startsWith('data: ')) {
                    // Create a hash to avoid processing the same line twice
                    let lineHash = line.substring(0, 100); // Use first 100 chars as hash
                    if (processedLines.has(lineHash)) {
                        continue;
                    }
                    processedLines.add(lineHash);
                    
                    try {
                        let jsonStr = line.substring(6);
                        if (!jsonStr) continue;
                        
                        let data = JSON.parse(jsonStr);
                        
                        if (data.error === true) {
                            // Handle error response from server
                            $loading.remove();
                            let errorMsg = data.message || 'Sorry, I encountered an error. Please try again.';
                            addBotMessage(chatbotId, errorMsg, true);
                            $input.prop('disabled', false);
                            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
                            $input.focus();
                            return;
                        } else if (data.type === 'start') {
                            if (!chatStarted) {
                                $loading.remove();
                                botMessage = '';
                                chatStarted = true;
                                // Create new message element for streaming
                                $loading = $('<div class="message bot streaming"><div class="message-content"></div></div>');
                                // Force alignment to right side
                                $loading.css({
                                    'align-self': 'flex-end',
                                    'margin-left': 'auto',
                                    'margin-right': '0'
                                });
                                // Apply styles immediately - light grey filled, right side
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
                                // Ensure styles are maintained during streaming
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
                                    // Ensure final styles are applied
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
                            lastProcessedIndex = 0; // Reset for next message
                            processedLines.clear(); // Clear processed lines for next message
                            $input.prop('disabled', false);
                            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
                            $input.focus();
                        }
                    } catch (e) {
                        console.error('Error parsing SSE data:', e, line);
                    }
                }
            }
            
            // Update last processed index
            lastProcessedIndex = lines.length;
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                // Success - handled in onprogress
                // If we got here but no 'done' event, finalize the message
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
            $input.prop('disabled', false);
            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
            $input.focus();
        };

        xhr.onerror = function() {
            $loading.remove();
            addBotMessage(chatbotId, 'Network error. Please check your connection and try again.', true);
            lastProcessedIndex = 0;
            processedLines.clear();
            $input.prop('disabled', false);
            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
            $input.focus();
        };

        xhr.send(formData);
    }

    function addUserMessage(chatbotId, message) {
        let $messageList = $(`.chatbot-item[data-chatbot-id="${chatbotId}"] .message-list`);
        let $message = $('<div class="message user"><div class="message-content"></div></div>');
        $message.find('.message-content').text(message);
        // Force alignment to left side
        $message.css({
            'align-self': 'flex-start',
            'margin-right': 'auto',
            'margin-left': '0'
        });
        // Ensure styles are applied - blue filled background, left side
        $message.find('.message-content').css({
            'background': '#007bff',
            'background-color': '#007bff',
            'color': '#ffffff',
            'border': '2px solid #007bff',
            'border-bottom-left-radius': '4px'
        });
        $messageList.append($message);
        chatHistory[chatbotId].push({
            type: 'user',
            message: message
        });
        scrollToBottom($messageList);
    }

    function addBotMessage(chatbotId, message, isError = false) {
        let $messageList = $(`.chatbot-item[data-chatbot-id="${chatbotId}"] .message-list`);
        let $message = $('<div class="message bot' + (isError ? ' error' : '') + '"><div class="message-content"></div></div>');
        $message.find('.message-content').text(message);
        // Force alignment to right side
        $message.css({
            'align-self': 'flex-end',
            'margin-left': 'auto',
            'margin-right': '0'
        });
        // Ensure styles are applied - light grey filled background, right side
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

