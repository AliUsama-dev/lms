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
        
        // Add loading indicator
        let $loading = $('<div class="message bot loading">Thinking...</div>');
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

        xhr.open('POST', chatbotRoutes.chat, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.onprogress = function() {
            let response = xhr.responseText;
            let lines = response.split('\n');
            
            for (let i = 0; i < lines.length; i++) {
                let line = lines[i].trim();
                if (line.startsWith('data: ')) {
                    try {
                        let data = JSON.parse(line.substring(6));
                        
                        if (data.type === 'start') {
                            if (!chatStarted) {
                                $loading.remove();
                                botMessage = '';
                                chatStarted = true;
                            }
                        } else if (data.type === 'chunk') {
                            botMessage += data.content;
                            if (chatStarted) {
                                if ($loading.length) {
                                    $loading.text(botMessage);
                                } else {
                                    $loading = $('<div class="message bot loading"></div>').text(botMessage);
                                    $messageList.append($loading);
                                }
                                scrollToBottom($messageList);
                            }
                        } else if (data.type === 'done') {
                            if ($loading.length) {
                                $loading.removeClass('loading');
                                $loading.text(botMessage);
                            } else {
                                addBotMessage(chatbotId, botMessage);
                            }
                            chatHistory[chatbotId].push({
                                type: 'bot',
                                message: botMessage
                            });
                            chatStarted = false;
                            botMessage = '';
                        }
                    } catch (e) {
                        // Ignore parse errors
                    }
                }
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                // Success - handled in onprogress
            } else {
                $loading.remove();
                let errorMsg = 'Sorry, I encountered an error. Please try again.';
                try {
                    let error = JSON.parse(xhr.responseText);
                    if (error.error) {
                        errorMsg = error.error;
                    }
                } catch (e) {
                    // Use default error message
                }
                addBotMessage(chatbotId, errorMsg);
            }
            $input.prop('disabled', false);
            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
            $input.focus();
        };

        xhr.onerror = function() {
            $loading.remove();
            addBotMessage(chatbotId, 'Network error. Please check your connection and try again.');
            $input.prop('disabled', false);
            $(`.chatbot-send[data-chatbot-id="${chatbotId}"]`).prop('disabled', false);
            $input.focus();
        };

        xhr.send(formData);
    }

    function addUserMessage(chatbotId, message) {
        let $messageList = $(`.chatbot-item[data-chatbot-id="${chatbotId}"] .message-list`);
        let $message = $('<div class="message user"></div>').text(message);
        $messageList.append($message);
        chatHistory[chatbotId].push({
            type: 'user',
            message: message
        });
        scrollToBottom($messageList);
    }

    function addBotMessage(chatbotId, message) {
        let $messageList = $(`.chatbot-item[data-chatbot-id="${chatbotId}"] .message-list`);
        let $message = $('<div class="message bot"></div>').text(message);
        $messageList.append($message);
        scrollToBottom($messageList);
    }

    function scrollToBottom($container) {
        $container.scrollTop($container[0].scrollHeight);
    }
})(jQuery);

