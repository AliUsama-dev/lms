@extends('backend.master')

@push('styles')
    <style>
        .chatbot-table .QA_table .table tbody td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('mainContent')
    {!! generateBreadcrumb() !!}

    <section class="admin-visitor-area up_st_admin_visitor">
        <div class="container-fluid p-0">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="white-box">
                        <div class="row">
                            <div class="col-12">
                                <div class="box_header common_table_header">
                                    <div class="main-title d-flex justify-content-between">
                                        <h3 class="mb-15 mr-30 mb_xs_15px mb_sm_20px">Chatbot Management</h3>
                                        <button class="primary-btn small fix-gr-bg text-nowrap" type="button" id="addChatbotBtn">
                                            <i class="ti-plus"></i> Add New Chatbot
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-12">
                                <div class="QA_section QA_section_heading_custom chatbot-table">
                                    <div class="QA_table">
                                        <div class="">
                                            <table id="chatbot_table" class="table Crm_table_active3">
                                                <thead>
                                                <tr>
                                                    <th scope="col">SL</th>
                                                    <th scope="col">Name</th>
                                                    <th scope="col">Description</th>
                                                    <th scope="col">Language Style</th>
                                                    <th scope="col">Specialization</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Documents</th>
                                                    <th scope="col">Created At</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Add/Edit Chatbot Modal -->
    <div class="modal fade admin-query" id="chatbotModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="modalTitle">Add New Chatbot</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"><i class="ti-close"></i></button>
                </div>
                <div class="modal-body">
                    <form id="chatbotForm">
                        <input type="hidden" id="chatbotId" name="id">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="name">Name <strong class="text-danger">*</strong></label>
                                    <input class="primary_input_field" type="text" id="name" name="name" placeholder="Enter chatbot name" required>
                                    <span class="text-danger error_msg" id="error_name"></span>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="description">Description</label>
                                    <textarea class="primary_input_field" id="description" name="description" rows="3" placeholder="Enter chatbot description"></textarea>
                                    <span class="text-danger error_msg" id="error_description"></span>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="system_prompt">System Prompt <strong class="text-danger">*</strong></label>
                                    <textarea class="primary_input_field" id="system_prompt" name="system_prompt" rows="5" placeholder="Enter system prompt" required></textarea>
                                    <span class="text-danger error_msg" id="error_system_prompt"></span>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="language_style">Language Style</label>
                                    <input class="primary_input_field" type="text" id="language_style" name="language_style" placeholder="e.g., Professional but Friendly">
                                    <span class="text-danger error_msg" id="error_language_style"></span>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="specialization">Specialization</label>
                                    <input class="primary_input_field" type="text" id="specialization" name="specialization" placeholder="e.g., Python, Django, JavaScript">
                                    <span class="text-danger error_msg" id="error_specialization"></span>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="temperature">Temperature</label>
                                    <input class="primary_input_field" type="number" id="temperature" name="temperature" step="0.1" min="0" max="2" value="0.7" placeholder="0.7">
                                    <span class="text-danger error_msg" id="error_temperature"></span>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="max_tokens">Max Tokens</label>
                                    <input class="primary_input_field" type="number" id="max_tokens" name="max_tokens" min="1" value="1000" placeholder="1000">
                                    <span class="text-danger error_msg" id="error_max_tokens"></span>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="document_usage_percentage">Document Usage %</label>
                                    <input class="primary_input_field" type="number" id="document_usage_percentage" name="document_usage_percentage" min="0" max="100" value="50" placeholder="50">
                                    <span class="text-danger error_msg" id="error_document_usage_percentage"></span>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label" for="response_format">Response Format</label>
                                    <select class="primary_select" id="response_format" name="response_format">
                                        <option value="text">Text</option>
                                        <option value="json">JSON</option>
                                    </select>
                                    <span class="text-danger error_msg" id="error_response_format"></span>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="primary_input mb-25">
                                    <label class="primary_input_label">Status</label>
                                    <div class="primary_checkbox d-flex">
                                        <input type="checkbox" id="is_active" name="is_active" value="1" checked>
                                        <label for="is_active">Active</label>
                                    </div>
                                    <span class="text-danger error_msg" id="error_is_active"></span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-40 d-flex justify-content-between">
                            <button type="button" class="primary-btn tr-bg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="primary-btn fix-gr-bg">
                                <i class="ti-check"></i> Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade admin-query" id="deleteModal">
        <div class="modal-dialog modal-dialog-centered modal_400px">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Delete Confirmation</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"><i class="ti-close"></i></button>
                </div>
                <div class="modal-body">
                    <p class="text-center">Are you sure you want to delete this chatbot?</p>
                    <div class="mt-40 d-flex justify-content-between">
                        <button type="button" class="primary-btn tr-bg" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="primary-btn fix-gr-bg" id="confirmDeleteBtn">
                            <i class="ti-check"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Define chatbot routes for JavaScript
        window.chatbotRoutes = {
            index: '{{ route("admin.chatbot.data") }}',
            store: '{{ route("admin.chatbot.store") }}',
            update: '{{ route("admin.chatbot.update", ":id") }}',
            show: '{{ route("admin.chatbot.show", ":id") }}',
            destroy: '{{ route("admin.chatbot.destroy", ":id") }}'
        };
    </script>
    <script src="{{ asset('public/backend/js/chatbot.js') }}"></script>
@endpush

