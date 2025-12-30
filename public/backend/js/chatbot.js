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
            searching: false,
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
        
        // File upload handling - using label, clicks work automatically via HTML for attribute
        // No need for JavaScript click handler since label handles it natively
        
        $(document).on('change', '#document_files', function(e) {
            let files = Array.from(e.target.files);
            
            // Check total file count
            if (selectedFiles.length + files.length > 5) {
                toastr.error('Maximum 5 files allowed. You already have ' + selectedFiles.length + ' file(s) selected.', 'Error');
                return;
            }
            
            // Add each file
            files.forEach(function(file) {
                addFileToList(file);
            });
            
            // Reset input to allow selecting the same file again if needed
            $(this).val('');
        });
        
        // Drag and drop handling
        $(document).on('dragover', '#documentUploadArea', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });
        
        $(document).on('dragleave', '#documentUploadArea', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });
        
        $(document).on('drop', '#documentUploadArea', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
            
            let files = Array.from(e.originalEvent.dataTransfer.files);
            
            // Check total file count
            if (selectedFiles.length + files.length > 5) {
                toastr.error('Maximum 5 files allowed. You already have ' + selectedFiles.length + ' file(s) selected.', 'Error');
                return;
            }
            
            // Add each file
            files.forEach(function(file) {
                addFileToList(file);
            });
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

    function populateForm(data) {
        $('#chatbotId').val(data.id || '');
        $('#name').val(data.name || '');
        $('#description').val(data.description || '');
        $('#system_prompt').val(data.system_prompt || '');
        $('#language_style').val(data.language_style || '');
        $('#specialization').val(data.specialization || '');
        $('#is_active').prop('checked', data.is_active === true || data.is_active === '1' || data.is_active === 1);
        
        // Clear document files when editing (files can't be pre-populated)
        clearDocumentFiles();
    }

    function resetForm() {
        $('#chatbotForm')[0].reset();
        $('#chatbotId').val('');
        $('.error_msg').text('');
        $('#is_active').prop('checked', true);
        clearDocumentFiles();
    }
    
    function clearDocumentFiles() {
        $('#document_files').val('');
        $('#documentFileList').empty();
        selectedFiles = [];
    }
    
    // File upload handling
    let selectedFiles = [];
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    function validateFile(file) {
        const maxSize = 10 * 1024 * 1024; // 10MB
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        const allowedExtensions = ['.pdf', '.doc', '.docx', '.txt'];
        
        // Check file size
        if (file.size > maxSize) {
            toastr.error('File "' + file.name + '" exceeds 10MB limit', 'Error');
            return false;
        }
        
        // Check file type
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(fileExtension) && !allowedTypes.includes(file.type)) {
            toastr.error('File "' + file.name + '" has an unsupported format. Supported formats: PDF, DOC, DOCX, TXT', 'Error');
            return false;
        }
        
        return true;
    }
    
    function addFileToList(file) {
        if (!validateFile(file)) {
            return false;
        }
        
        selectedFiles.push(file);
        updateFileList();
        return true;
    }
    
    function removeFileFromList(index) {
        selectedFiles.splice(index, 1);
        updateFileList();
    }
    
    function updateFileList() {
        const fileList = $('#documentFileList');
        fileList.empty();
        
        selectedFiles.forEach(function(file, index) {
            const fileItem = $('<div>').addClass('document-file-item');
            const fileInfo = $('<div>').addClass('file-info');
            fileInfo.append($('<i>').addClass('ti-file'));
            fileInfo.append($('<span>').addClass('file-name').text(file.name));
            fileInfo.append($('<span>').addClass('file-size').text('(' + formatFileSize(file.size) + ')'));
            
            const removeBtn = $('<span>').addClass('remove-file').html('<i class="ti-close"></i>');
            removeBtn.on('click', function() {
                removeFileFromList(index);
            });
            
            fileItem.append(fileInfo);
            fileItem.append(removeBtn);
            fileList.append(fileItem);
        });
    }

    function saveChatbot() {
        // Validate file count
        if (selectedFiles.length > 5) {
            toastr.error('Maximum 5 files allowed', 'Error');
            return;
        }
        
        let chatbotId = $('#chatbotId').val();
        let url = chatbotId ? chatbotRoutes.update.replace(':id', chatbotId) : chatbotRoutes.store;
        let method = chatbotId ? 'PUT' : 'POST';

        // Clear previous errors
        $('.error_msg').text('');

        // Create FormData for multipart/form-data
        let formData = new FormData();
        formData.append('name', $('#name').val());
        formData.append('description', $('#description').val());
        formData.append('system_prompt', $('#system_prompt').val());
        formData.append('language_style', $('#language_style').val());
        formData.append('specialization', $('#specialization').val());
        formData.append('is_active', $('#is_active').is(':checked') ? 'true' : 'false');
        
        // Add document files
        selectedFiles.forEach(function(file) {
            formData.append('document_files', file);
        });

        $.ajax({
            url: url,
            type: method,
            data: formData,
            processData: false,
            contentType: false,
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

