(function($) {
    "use strict";
    
    let _token = $('meta[name=_token]').attr('content');
    let chatbotTable;
    let deleteChatbotId = null;

    $(document).ready(function() {
        // Only initialize if the table exists
        if ($('#chatbot_table').length) {
            initializeDataTable();
            bindEvents();
        }
    });

    function initializeDataTable() {
        // Check if DataTable is already initialized and destroy it first
        if ($.fn.DataTable.isDataTable('#chatbot_table')) {
            $('#chatbot_table').DataTable().destroy();
        }
        
        chatbotTable = $('#chatbot_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: chatbotRoutes.index,
                type: 'GET',
                dataSrc: function(json) {
                    // Handle both DataTable format and API format
                    if (json.data && Array.isArray(json.data)) {
                        // DataTable format
                        return json.data;
                    } else if (json.results && Array.isArray(json.results)) {
                        // API format - transform to DataTable format
                        json.data = json.results;
                        json.recordsTotal = json.count || 0;
                        json.recordsFiltered = json.count || 0;
                        return json.data;
                    }
                    return [];
                },
                error: function(xhr, error, thrown) {
                    console.error('Error loading chatbots:', error);
                    toastr.error('Failed to load chatbots', 'Error');
                }
            },
            columns: [
                {
                    data: null,
                    render: function(data, type, row, meta) {
                        return meta.row + 1;
                    }
                },
                {
                    data: 'name',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                {
                    data: 'description',
                    render: function(data, type, row) {
                        if (data && data.length > 50) {
                            return data.substring(0, 50) + '...';
                        }
                        return data || '-';
                    }
                },
                {
                    data: 'language_style',
                    render: function(data, type, row) {
                        return data || '-';
                    }
                },
                {
                    data: 'specialization',
                    render: function(data, type, row) {
                        if (data && data.length > 30) {
                            return data.substring(0, 30) + '...';
                        }
                        return data || '-';
                    }
                },
                {
                    data: 'is_active',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="badge_1">Active</span>';
                        } else {
                            return '<span class="badge_2">Inactive</span>';
                        }
                    }
                },
                {
                    data: 'documents',
                    render: function(data, type, row) {
                        if (data && Array.isArray(data) && data.length > 0) {
                            return '<span class="badge_1">' + data.length + ' document(s)</span>';
                        }
                        return '<span class="badge_2">No documents</span>';
                    }
                },
                {
                    data: 'created_at',
                    render: function(data, type, row) {
                        if (data) {
                            return new Date(data).toLocaleDateString();
                        }
                        return '-';
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        let actions = '<div class="dropdown CRM_dropdown">';
                        actions += '<button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenu2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                        actions += 'Action';
                        actions += '</button>';
                        actions += '<div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenu2">';
                        actions += '<a class="dropdown-item edit-chatbot" href="javascript:void(0)" data-id="' + data + '">Edit</a>';
                        actions += '<a class="dropdown-item view-chatbot" href="javascript:void(0)" data-id="' + data + '">View</a>';
                        actions += '<a class="dropdown-item delete-chatbot text-danger" href="javascript:void(0)" data-id="' + data + '">Delete</a>';
                        actions += '</div>';
                        actions += '</div>';
                        return actions;
                    }
                }
            ],
            order: [[7, 'desc']],
            pageLength: 10,
            language: {
                emptyTable: "No chatbots found"
            }
        });
    }

    function bindEvents() {
        // Add new chatbot
        $(document).on('click', '#addChatbotBtn', function() {
            resetForm();
            $('#modalTitle').text('Add New Chatbot');
            $('#chatbotModal').modal('show');
        });

        // Edit chatbot
        $(document).on('click', '.edit-chatbot', function() {
            let id = $(this).data('id');
            loadChatbotForEdit(id);
        });

        // View chatbot
        $(document).on('click', '.view-chatbot', function() {
            let id = $(this).data('id');
            viewChatbot(id);
        });

        // Delete chatbot
        $(document).on('click', '.delete-chatbot', function() {
            deleteChatbotId = $(this).data('id');
            $('#deleteModal').modal('show');
        });

        // Confirm delete
        $(document).on('click', '#confirmDeleteBtn', function() {
            if (deleteChatbotId) {
                deleteChatbot(deleteChatbotId);
            }
        });

        // Form submit
        $(document).on('submit', '#chatbotForm', function(e) {
            e.preventDefault();
            saveChatbot();
        });
    }

    function loadChatbotForEdit(id) {
        $.ajax({
            url: chatbotRoutes.show.replace(':id', id),
            type: 'GET',
            beforeSend: function() {
                $('.preloader').fadeIn();
            },
            success: function(response) {
                if (response && response.id) {
                    populateForm(response);
                    $('#modalTitle').text('Edit Chatbot');
                    $('#chatbotModal').modal('show');
                } else {
                    toastr.error('Failed to load chatbot data', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to load chatbot';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $('.preloader').fadeOut();
            }
        });
    }

    function viewChatbot(id) {
        $.ajax({
            url: chatbotRoutes.show.replace(':id', id),
            type: 'GET',
            beforeSend: function() {
                $('.preloader').fadeIn();
            },
            success: function(response) {
                if (response && response.id) {
                    showChatbotDetails(response);
                } else {
                    toastr.error('Failed to load chatbot data', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to load chatbot';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $('.preloader').fadeOut();
            }
        });
    }

    function showChatbotDetails(data) {
        let html = '<div class="chatbot-details">';
        html += '<p><strong>Name:</strong> ' + (data.name || '-') + '</p>';
        html += '<p><strong>Description:</strong> ' + (data.description || '-') + '</p>';
        html += '<p><strong>System Prompt:</strong> ' + (data.system_prompt || '-') + '</p>';
        html += '<p><strong>Language Style:</strong> ' + (data.language_style || '-') + '</p>';
        html += '<p><strong>Specialization:</strong> ' + (data.specialization || '-') + '</p>';
        html += '<p><strong>Status:</strong> ' + (data.is_active ? 'Active' : 'Inactive') + '</p>';
        html += '<p><strong>Temperature:</strong> ' + (data.temperature || '0.7') + '</p>';
        html += '<p><strong>Max Tokens:</strong> ' + (data.max_tokens || '1000') + '</p>';
        html += '<p><strong>Document Usage %:</strong> ' + (data.document_usage_percentage || '0') + '%</p>';
        if (data.documents && data.documents.length > 0) {
            html += '<p><strong>Documents:</strong></p><ul>';
            data.documents.forEach(function(doc) {
                html += '<li>' + doc.file_name + ' (' + doc.file_type + ')</li>';
            });
            html += '</ul>';
        }
        html += '<p><strong>Created At:</strong> ' + (data.created_at ? new Date(data.created_at).toLocaleString() : '-') + '</p>';
        html += '</div>';

        swal({
            title: 'Chatbot Details',
            html: html,
            type: 'info',
            confirmButtonText: 'Close'
        });
    }

    function populateForm(data) {
        $('#chatbotId').val(data.id || '');
        $('#name').val(data.name || '');
        $('#description').val(data.description || '');
        $('#system_prompt').val(data.system_prompt || '');
        $('#language_style').val(data.language_style || '');
        $('#specialization').val(data.specialization || '');
        $('#temperature').val(data.temperature || '0.7');
        $('#max_tokens').val(data.max_tokens || '1000');
        $('#document_usage_percentage').val(data.document_usage_percentage || '50');
        $('#response_format').val(data.response_format || 'text');
        $('#is_active').prop('checked', data.is_active === true || data.is_active === '1' || data.is_active === 1);
    }

    function resetForm() {
        $('#chatbotForm')[0].reset();
        $('#chatbotId').val('');
        $('.error_msg').text('');
        $('#is_active').prop('checked', true);
    }

    function saveChatbot() {
        let formData = {
            name: $('#name').val(),
            description: $('#description').val(),
            system_prompt: $('#system_prompt').val(),
            language_style: $('#language_style').val(),
            specialization: $('#specialization').val(),
            temperature: $('#temperature').val() || '0.7',
            max_tokens: $('#max_tokens').val() || '1000',
            document_usage_percentage: $('#document_usage_percentage').val() || '50',
            response_format: $('#response_format').val() || 'text',
            is_active: $('#is_active').is(':checked')
        };

        let chatbotId = $('#chatbotId').val();
        let url = chatbotId ? chatbotRoutes.update.replace(':id', chatbotId) : chatbotRoutes.store;
        let method = chatbotId ? 'PUT' : 'POST';

        // Clear previous errors
        $('.error_msg').text('');

        $.ajax({
            url: url,
            type: method,
            data: formData,
            headers: {
                'X-CSRF-TOKEN': _token
            },
            beforeSend: function() {
                $('.preloader').fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Chatbot saved successfully', 'Success');
                    $('#chatbotModal').modal('hide');
                    chatbotTable.ajax.reload(null, false);
                    resetForm();
                } else {
                    toastr.error(response.error || 'Failed to save chatbot', 'Error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    // Display validation errors
                    $.each(xhr.responseJSON.errors, function(key, value) {
                        $('#error_' + key).text(value[0]);
                    });
                } else {
                    let errorMsg = 'Failed to save chatbot';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    toastr.error(errorMsg, 'Error');
                }
            },
            complete: function() {
                $('.preloader').fadeOut();
            }
        });
    }

    function deleteChatbot(id) {
        $.ajax({
            url: chatbotRoutes.destroy.replace(':id', id),
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': _token
            },
            beforeSend: function() {
                $('.preloader').fadeIn();
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Chatbot deleted successfully', 'Success');
                    $('#deleteModal').modal('hide');
                    chatbotTable.ajax.reload(null, false);
                    deleteChatbotId = null;
                } else {
                    toastr.error(response.error || 'Failed to delete chatbot', 'Error');
                }
            },
            error: function(xhr) {
                let errorMsg = 'Failed to delete chatbot';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMsg = xhr.responseJSON.error;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                toastr.error(errorMsg, 'Error');
            },
            complete: function() {
                $('.preloader').fadeOut();
            }
        });
    }
})(jQuery);

