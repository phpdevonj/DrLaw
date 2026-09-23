<x-master-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block card-stretch card-height border-radius-20">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="header-title">
                            <h4 class="card-title mb-0">{{ $pageTitle ?? ''}}</h4>
                        </div>
                        <div class="d-flex justify-content-end">                    
                            <div class="dropdown">
                                <button class="btn btn-outline-success border-radius-10 dropdown-toggle me-2" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-file-export"></i> {{ __('message.export') }}
                                </button>
                                <ul class="dropdown-menu border-radius-10" aria-labelledby="exportDropdown">
                                    <li><a class="dropdown-item text-decoration-none border-radius-10" href="#" id="export-csv"><i class="fas fa-file-csv"></i> {{__('message.excel')}}</a></li>
                                    <li><a class="dropdown-item text-decoration-none border-radius-10" href="#" id="export-pdf"><i class="fas fa-file-pdf"></i> {{__('message.pdf')}}</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-warning border-radius-10 ml-2" type="button" id="openFilterModal" data-bs-toggle="modal" data-bs-target="#filterModal">
                                <i class="fas fa-filter"></i> {{ __('message.filter') }}
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <table id="basic-table" class="table table-hover mb-1 text-center" role="grid">
                            <thead>
                                <tr>
                                    <th scope='col'>{{ __('message.id') }}</th>
                                    <th scope='col'>{{ __('message.referrer_email') }}</th>
                                    <th scope='col'>{{ __('message.referred_email') }}</th>
                                    <th scope='col'>{{ __('message.referred_device_id') }}</th>
                                    <th scope='col'>{{ __('message.referred_device_type') }}</th>
                                    <th scope='col'>{{ __('message.status') }}</th>
                                    <th scope='col'>{{ __('message.created_at') }}</th>
                                </tr>
                            </thead>                          
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('report.referrals-report-filter')

    @section('bottom_script')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script type="text/javascript">
            $(document).ready(function() {
                // Initialize Bootstrap dropdowns
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
                var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                    return new bootstrap.Dropdown(dropdownToggleEl)
                });

                // Initialize Bootstrap modals
                var filterModal = new bootstrap.Modal(document.getElementById('filterModal'));

                // Open filter modal when button is clicked
                $('#openFilterModal').on('click', function() {
                    filterModal.show();
                });

                if ($.fn.select2) {
                    $('.select2').select2({
                        dropdownParent: $('#filterModal')
                    });
                }

                var table = $('#basic-table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: '{{ route("referralsReport") }}',
                        type: 'GET',
                        data: function(d) {
                            return $.extend({}, d, {
                                referrer_id: $('#referrer_id').val(),
                                referred_id: $('#referred_id').val(), 
                                status: $('#status').val(),
                                from_date: $('#from_date_main').val(),
                                to_date: $('#to_date_main').val()
                            });
                        }
                    },
                    columns: [
                        { 
                            data: null,
                            render: function (data, type, row, meta) {
                                return meta.row + 1 + meta.settings._iDisplayStart;
                            },
                            orderable: false,
                            searchable: false
                        },
                        { data: 'referrer_email' },
                        { data: 'referred_email' },
                        { data: 'referred_device_id' },
                        { data: 'referred_device_type' },
                        { data: 'status' },
                        { data: 'created_at' }
                    ],
                    "drawCallback": function(settings) {
                        if (typeof bootstrap !== 'undefined') {
                            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                            tooltipTriggerList.map(function (tooltipTriggerEl) {
                                return new bootstrap.Tooltip(tooltipTriggerEl);
                            });
                        }
                    }
                });                
        
                $('#admin_report_filter_form').on('submit', function(e) {
                    e.preventDefault();
                    table.draw();
                    $('#filterModal').modal('hide');
                });
        
                $('#reset-filter-btn').on('click', function() {
                    $('#referrer_id, #referred_id, #status').val('').trigger('change');
                    $('#from_date_main, #to_date_main').val('');
                    table.draw();
                });

                $('#export-csv').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const referrerId = $('#referrer_id').val() || '';
                    const referredId = $('#referred_id').val() || '';
                    const status = $('#status').val() || '';
                    const exportUrl = `{{ route('download.referrals.report') }}?from_date=${fromDate}&to_date=${toDate}&referrer_id=${referrerId}&referred_id=${referredId}&status=${status}`;
                    window.location.href = exportUrl;
                });
        
                $('#export-pdf').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const referrerId = $('#referrer_id').val() || '';
                    const referredId = $('#referred_id').val() || '';
                    const status = $('#status').val() || '';
                    const exportUrl = `{{ route('download.referrals.report.pdf') }}?from_date=${fromDate}&to_date=${toDate}&referrer_id=${referrerId}&referred_id=${referredId}&status=${status}`;
                    window.location.href = exportUrl;
                });
            });
        </script>
    @endsection
</x-master-layout>