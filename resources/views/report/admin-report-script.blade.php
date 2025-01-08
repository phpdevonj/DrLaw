<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        $('#basic-table').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            stateSave: true,
            ajax: {
                url: '{{ route("adminEarningReport") }}',
                data: function(d) {
                    d.from_date = $('#from_date_main').val();
                    d.to_date = $('#to_date_main').val();
                    d.rider_id = $('#rider_id').val();
                    d.driver_id = $('#driver_id').val();
                }
            },
            columns: [
                { data: 'id' },
                { data: 'rider_display_name' },
                { data: 'driver_display_name' },
                { data: 'pickup_date_time' },
                { data: 'drop_date_time' },
                { data: 'payment_total_amount' },
                { data: 'payment_admin_commission' },
                { data: 'payment_driver_commission' },
                { data: 'created_at' }
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
