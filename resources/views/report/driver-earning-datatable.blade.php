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
                                    <th scope='col'>{{ __('message.title_name',['title' => __('message.rider')]) }}</th>
                                    <th scope='col'>{{ __('message.title_name',['title' => __('message.driver')]) }}</th>
                                    <th scope='col'>{{ __('message.total_amount') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.driver_earning') }}</th>
                                    <th scope='col' class="text-center">{{ __('message.admin_commission') }}</th>
                                    <th scope='col'>{{ __('message.created_at') }}</th>
                                </tr>
                            </thead>
                            <tfoot>
                                <tr>
                                    <td colspan="2" class="font-weight-700">{{ __('message.total_amount') }}</td>
                                    <td id="total-payment-amount" class="font-weight-700">0.00</td>
                                    <td id="total-driver-commission" class="font-weight-700">0.00</td>
                                    <td id="total-admin-commission" class="font-weight-700">0.00</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('report.driver-earning-filter')

    @section('bottom_script')
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            $(document).ready(function() {
                $('.select2').select2({
                    dropdownParent: $('#filterModal'),
                });
                const table = $('#basic-table').DataTable({
                    processing: true,
                    serverSide: true,
                    searching: false,
                    ajax: {
                        url: '{{ route("driver.earning.report") }}',
                        data: function(d) {
                            d.from_date = $('#from_date_main').val();
                            d.to_date = $('#to_date_main').val();
                            d.rider_id = $('#rider_id').val();
                            d.driver_id = $('#driver_id').val();
                        },
                        dataSrc: function (json) {
                            // Update the totals in the footer
                            $('#total-payment-amount').html(json.totalAmount.toFixed(2));
                            $('#total-admin-commission').html(json.totalAdminCommission.toFixed(2));
                            $('#total-driver-commission').html(json.totalDriverCommission.toFixed(2));
                            return json.data; // Ensure this is returning an array of data
                        }
                    },
                    columns: [
                        { data: 'rider_display_name', defaultContent: '-' },
                        { data: 'driver_display_name', defaultContent: '-' },
                        { data: 'payment_total_amount', defaultContent: '-' },
                        { data: 'payment_driver_commission', defaultContent: '-' },
                        { data: 'payment_admin_commission', defaultContent: '-' },
                        { data: 'created_at', defaultContent: '-' }
                    ]
                });

                $('#admin_report_filter_form').on('submit', function(e) {
                    e.preventDefault();
                    $('#basic-table').DataTable().ajax.reload();
                });
        
                $('#reset-filter-btn').on('click', function() {
                    $('#from_date_main').val('').trigger('change');
                    $('#to_date_main').val('').trigger('change');
                    $('#rider_id').val('').trigger('change');
                    $('#driver_id').val('').trigger('change');
                    $('#basic-table').DataTable().ajax.reload();
                });
        
                $('#export-csv').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const riderId = $('#rider_id').val() || '';
                    const driverId = $('#driver_id').val() || '';
                    const exportUrl = `{{ route('download-admin-earning') }}?from_date=${fromDate}&to_date=${toDate}&rider_id=${riderId}&driver_id=${driverId}`;
                    window.location.href = exportUrl;
                });
        
                $('#export-pdf').on('click', function(e) {
                    e.preventDefault();
                    const fromDate = $('#from_date_main').val() || '';
                    const toDate = $('#to_date_main').val() || '';
                    const riderId = $('#rider_id').val() || '';
                    const driverId = $('#driver_id').val() || '';
                    const exportUrl = `{{ route('download-adminearningpdf') }}?from_date=${fromDate}&to_date=${toDate}&rider_id=${riderId}&driver_id=${driverId}`;
                    window.location.href = exportUrl;
                });
            });
            
            
        </script>
    @endsection
</x-master-layout>
