<?php

namespace App\Http\Controllers;

use App\Models\{RideRequest,User,Payment};
use Illuminate\Http\Request;
use App\Exports\{AdminReportExport,DriverReportExport,DriverEarningExport,ServiceWiseReportExport};
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function adminEarning(Request $request)
    {
        $pageTitle = __('message.earning_report', ['name' => __('message.admin')]);
        $auth_user = authSession();
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'rider_id' => $request->input('rider_id'),
            'driver_id' => $request->input('driver_id'),
        ];

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment'])
            ->where('status', 'completed')
            ->orderBy('id', 'desc');

        if ($params['from_date'] != null) {
            $ride_requests->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($params['to_date'] != null) {
            $ride_requests->whereDate('created_at', '<=', $params['to_date']);
        }
        if ($params['rider_id'] != null) {
            $ride_requests->where('rider_id', $params['rider_id']);
        }
        if ($params['driver_id'] != null) {
            $ride_requests->where('driver_id', $params['driver_id']);
        }

        $totals = Payment::whereHas('rideRequest', function ($query) use ($params) {
            $query->where('status', 'completed')
                ->when($params['from_date'], fn($q) => $q->whereDate('created_at', '>=', $params['from_date']))
                ->when($params['to_date'], fn($q) => $q->whereDate('created_at', '<=', $params['to_date']))
                ->when($params['rider_id'], fn($q) => $q->where('rider_id', $params['rider_id']))
                ->when($params['driver_id'], fn($q) => $q->where('driver_id', $params['driver_id']));
        })
        ->selectRaw('SUM(total_amount) as totalAmount, SUM(admin_commission) as totalAdminCommission, SUM(driver_commission) as totalDriverCommission')
        ->first();
        
        $totalAmount = $totals->totalAmount ?? 0;
        $totalAdminCommission = $totals->totalAdminCommission ?? 0;
        $totalDriverCommission = $totals->totalDriverCommission ?? 0;
        
        if ($request->ajax()) {
            return datatables()->of($ride_requests)
                ->addColumn('rider_display_name', function ($row) {
                    return optional($row->rider)->display_name ?? '-';
                })
                ->addColumn('driver_display_name', function ($row) {
                    return optional($row->driver)->display_name ?? '-';
                })
                ->addColumn('pickup_date_time', function ($row) {
                    return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
                })
                ->addColumn('drop_date_time', function ($row) {
                    $completed_ride = $row->rideRequestHistory()->where('history_type', 'completed')->first();
                    return $completed_ride ? dateAgoFormate($completed_ride->datetime, true) : '-';
                })
                ->addColumn('payment_total_amount', function ($row) {
                    return getPriceFormat(optional($row->payment)->total_amount) ?? '-';
            })
            ->addColumn('payment_admin_commission', function ($row) {
                return getPriceFormat(optional($row->payment)->admin_commission) ?? '-';
            })
            ->addColumn('payment_driver_commission', function ($row) {
                return getPriceFormat(optional($row->payment)->driver_commission) ?? '-';
            })
            ->addColumn('created_at', function ($row) {
                return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
            })
            ->with('totalAmount', $totalAmount)
            ->with('totalAdminCommission', $totalAdminCommission)
            ->with('totalDriverCommission', $totalDriverCommission)
            ->make(true);
        }

        // For non-AJAX requests, render the page with the initial data
        $data = $ride_requests->get();
        return view('report.adminreport', compact('pageTitle', 'auth_user', 'data', 'params'));
    }

    public function downloadAdminEarning(Request $request)
    {
        $startDate = $request->input('from_date',null);
        $endDate = $request->input('to_date',null);
        $riderId = $request->input('rider_id',null);
        $driverId = $request->input('driver_id',null);

        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null;
        $end = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : null;

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment'])
            ->where('status', 'completed');

        if ($start) {
            $ride_requests->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $ride_requests->whereDate('created_at', '<=', $end);
        }
        if ($riderId) {
            $ride_requests->where('rider_id', $riderId);
        }
        if ($driverId) {
            $ride_requests->where('driver_id', $driverId);
        }

        $data = $ride_requests->get();

        $export = new AdminReportExport($request,$startDate,$endDate,$riderId,$driverId);
        $filename = ($start && $end) ? "admin-earning-report_{$start}_to_{$end}.xlsx" : "admin-earning-report_{$start}.xlsx";

        return Excel::download($export, $filename);
    }

    public function downloadAdminEarningPdf(Request $request)
    {
        $export = new AdminReportExport($request);
        $collection = $export->collection();
        $mappedData = $collection->map([$export, 'map']);
        $headings = $export->headings('pdf');

        $totalAmountSum = getPriceFormat($export->getTotalAmountSum());
        $admin_commission = getPriceFormat($export->getAdminCommission());
        $driver_commission = getPriceFormat($export->getDriverCommission());

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted . ' To Date: ' . $toDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted . '_to_' . $toDateFormatted;
        } elseif ($fromDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted;
        } elseif ($toDate) {
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'To Date: ' . $toDateFormatted;
            $filenameDatePart = '_to_' . $toDateFormatted;
        }

        $htmlContent = '<h1>Admin Earning Report</h1>';
        if ($dateFilterText) {
            $htmlContent .= '<p><strong>' . $dateFilterText . '</strong></p>';
        }

        $htmlContent .= '<style>
            body {
                font-family: "DejaVu Sans", sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #bfbfbf;
            }
            .bold-text {
                 font-weight: bold;
            }
            .text-capitalize {
                text-transform: capitalize;
            }
            h1{
                text-align:center;
            }
            p{
                font-size:18px;
            }
            .note {
                margin-top: 20px;
                font-size: 15px;
                text-align: center;
                color: green;
            }  
        </style>';
        $htmlContent .= '<table>';
        $htmlContent .= '<thead><tr>';

        foreach ($headings as $heading) {
            $htmlContent .= '<th>' . $heading . '</th>';
        }
        $htmlContent .= '</tr></thead>';
        $htmlContent .= '<tbody>';

        foreach ($mappedData as $row) {
            $htmlContent .= '<tr>';
            foreach ($row as $cell) {
                if ($cell === 'Total' || $cell === $totalAmountSum || $cell === $admin_commission || $cell === $driver_commission) {
                    $htmlContent .= '<td class="bold-text">' . $cell . '</td>';
                } else {
                    $htmlContent .= '<td class="text-capitalize">' . $cell . '</td>';
                }
            }
            $htmlContent .= '</tr>';
        }

        $htmlContent .= '</tbody>';

        $htmlContent .= '</table>';
        $htmlContent .= '<div class="note">
                <p class="note">'.__('message.note_pdf_report').'</p>
                </div>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'landscape');

        // return $pdf->download('admin-earning-report.pdf');
        $filename = 'admin-earning-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }

    public function driverEarning(Request $request)
    {
        $pageTitle = __('message.earning_report',['name' => __('message.driver')] );
        $auth_user = authSession();
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'rider_id' => $request->input('rider_id'),
            'driver_id' => $request->input('driver_id'),
        ];

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment', 'rideRequestHistory'])
            ->where('status', 'completed')
            ->whereHas('payment', function ($query) {
                $query->where('payment_status', 'paid');
            })
            // ->myRide()
            ->orderBy('id', 'desc');

        // Apply date filters if provided
        if (!empty($params['from_date'])) {
            $ride_requests->whereDate('created_at', '>=', $params['from_date']);
        }

        if (!empty($params['to_date'])) {
            $ride_requests->whereDate('created_at', '<=', $params['to_date']);
        }

        if (!empty($params['rider_id'])) {
            $ride_requests->where('rider_id', $params['rider_id']);
        }

        if (!empty($params['driver_id'])) {
            $ride_requests->where('driver_id', $params['driver_id']);
        }

        // Calculate totals for the payment data
        $totals = Payment::whereHas('rideRequest', function ($query) use ($params) {
            $query->where('status', 'completed')
                ->when($params['from_date'], fn($q) => $q->whereDate('created_at', '>=', $params['from_date']))
                ->when($params['to_date'], fn($q) => $q->whereDate('created_at', '<=', $params['to_date']))
                ->when($params['rider_id'], fn($q) => $q->where('rider_id', $params['rider_id']))
                ->when($params['driver_id'], fn($q) => $q->where('driver_id', $params['driver_id']));
        })
        ->selectRaw('SUM(total_amount) as totalAmount, SUM(admin_commission) as totalAdminCommission, SUM(driver_commission) as totalDriverCommission')
        ->first();

        // Total amounts
        $totalAmount = $totals->totalAmount ?? 0;
        $totalAdminCommission = $totals->totalAdminCommission ?? 0;
        $totalDriverCommission = $totals->totalDriverCommission ?? 0;

        // Check if the request is AJAX
        if ($request->ajax()) {
            return datatables()->of($ride_requests)
                ->addColumn('rider_display_name', function ($row) {
                    return optional($row->rider)->display_name ?? '-';
                })
                ->addColumn('driver_display_name', function ($row) {
                    return optional($row->driver)->display_name ?? '-';
                })
                ->addColumn('pickup_date_time', function ($row) {
                    return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
                })
                ->addColumn('drop_date_time', function ($row) {
                    $completed_ride = $row->rideRequestHistory()->where('history_type', 'completed')->first();
                    return $completed_ride ? dateAgoFormate($completed_ride->datetime, true) : '-';
                })
                ->addColumn('payment_total_amount', function ($row) {
                    return getPriceFormat(optional($row->payment)->total_amount) ?? '-';
                })
                ->addColumn('payment_admin_commission', function ($row) {
                    return getPriceFormat(optional($row->payment)->admin_commission) ?? '-';
                })
                ->addColumn('payment_driver_commission', function ($row) {
                    return getPriceFormat(optional($row->payment)->driver_commission) ?? '-';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
                })
                ->with([
                    'totalAmount' => $totalAmount,
                    'totalAdminCommission' => $totalAdminCommission,
                    'totalDriverCommission' => $totalDriverCommission,
                ])
                ->make(true);
        }

        // If it's not an AJAX request, return the page view
        $pageTitle = __('message.earning_report', ['name' => __('message.driver')]);
        $auth_user = authSession();
        return view('report.driver-earning-datatable', compact('pageTitle', 'auth_user', 'params'));
    }

    public function downloadDriverEarning(Request $request)
    {
        $startDate = $request->input('from_date',null);
        $endDate = $request->input('to_date',null);
        $riderId = $request->input('rider_id',null);
        $driverId = $request->input('driver_id',null);

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment'])
            ->where('status', 'completed');

        if ($start) {
            $ride_requests->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $ride_requests->whereDate('created_at', '<=', $end);
        }
        if ($riderId) {
            $ride_requests->where('rider_id', $riderId);
        }
        if ($driverId) {
            $ride_requests->where('driver_id', $driverId);
        }

        $data = $ride_requests->get();

        $export = new DriverEarningExport($request,$startDate,$endDate,$riderId,$driverId);

        $filename = ($start && $endDate) ? "driver-earning-report_{$start}_to_{$end}.xlsx" : "driver-earning-report_{$start}.xlsx";

        return Excel::download($export, $filename);
    }

    public function downloadDriverEarningPdf(Request $request)
    {
        $export = new DriverEarningExport($request);
        $collection = $export->collection();
        $mappedData = $collection->map([$export, 'map']);
        $headings = $export->headings('pdf');

        $total_amount   = $export->getDriverSumData('total_amount');
        $driver_commission  = $export->getDriverSumData('driver_commission');
        $admin_commission    = $export->getDriverSumData('admin_commission');

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted . ' To Date: ' . $toDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted . '_to_' . $toDateFormatted;
        } elseif ($fromDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted;
        } elseif ($toDate) {
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'To Date: ' . $toDateFormatted;
            $filenameDatePart = '_to_' . $toDateFormatted;
        }

        $htmlContent = '<h1>Driver Earning Report</h1>';
        if ($dateFilterText) {
            $htmlContent .= '<p><strong>' . $dateFilterText . '</strong></p>';
        }

        $htmlContent .= '<style>
            body {
                font-family: "DejaVu Sans", sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #bfbfbf;
            }
            .bold-text {
                 font-weight: bold;
            }
            .text-capitalize {
                text-transform: capitalize;
            }
            h1{
                text-align:center;
            }
            p{
                font-size:18px;
            }
            .note {
                margin-top: 20px;
                font-size: 15px;
                text-align: center;
                color: green;
            }  
        </style>';
        $htmlContent .= '<table>';
        $htmlContent .= '<thead><tr>';

        foreach ($headings as $heading) {
            $htmlContent .= '<th>' . $heading . '</th>';
        }
        $htmlContent .= '</tr></thead>';
        $htmlContent .= '<tbody>';

        foreach ($mappedData as $row) {
            $htmlContent .= '<tr>';
            foreach ($row as $cell) {
                
                if (in_array($cell,['Total',$total_amount,$driver_commission,$admin_commission])) {
                    $htmlContent .= '<td class="bold-text">' . $cell . '</td>';
                } else {
                    $htmlContent .= '<td class="text-capitalize">' . $cell . '</td>';
                }
            }
            $htmlContent .= '</tr>';
        }

        $htmlContent .= '</tbody>';

        $htmlContent .= '</table>';
        $htmlContent .= '<div class="note">
                <p class="note">'.__('message.note_pdf_report').'</p>
                </div>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'landscape');
        $filename = 'driver-earning-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }

    public function driverReport(Request $request)
    {
        $pageTitle = __('message.report',['name' => __('message.driver')] );
        $auth_user = authSession();
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        $driver_ids = isset($_GET['driver']) ? $_GET['driver'] : null;
        $ride_requests_driver = RideRequest::where('driver_id', $driver_ids)->where('status', 'completed')->myRide();
        $user_data = User::find($driver_ids);

        if (!empty($params['from_date'])) {
            $ride_requests_driver->whereDate('created_at', '>=', $params['from_date']);
        }

        if (!empty($params['to_date'])) {
            $ride_requests_driver->whereDate('created_at', '<=', $params['to_date']);
        }

        $params['datatable_botton_style'] = true;
        $data = $ride_requests_driver->get();
        return view('report.driver-report', compact('pageTitle', 'auth_user','data','params','user_data'));
    }

    public function downloadDriverReport(Request $request)
    {
        $startDate = $request->input('from_date');
        $endDate = $request->input('to_date');

        $start = Carbon::parse($startDate)->format('Y-m-d');
        $end = Carbon::parse($endDate)->format('Y-m-d');
        $driver_ids = isset($_GET['driver']) ? $_GET['driver'] : null;
        $export = new DriverReportExport($request, $startDate, $endDate,$driver_ids);
        $filename = ($start && $endDate) ? "driver-earning-report_{$start}_to_{$end}.xlsx" : "driver-earning-report_{$start}.xlsx";

        return Excel::download($export, $filename);
    }

    public function downloadDriverReportPdf(Request $request)
    {
        $export = new DriverReportExport($request);
        $collection = $export->collection();
        $mappedData = $collection->map([$export, 'map']);
        $headings = $export->headings('pdf');

        $total_amount = $export->getDriverSumData('total_amount');
        $admin_commission   = $export->getDriverSumData('admin_commission');
        $driver_commission  = $export->getDriverSumData('driver_commission');

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $dateFilterText = '';
        $filenameDatePart = '';
        if ($fromDate && $toDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted . ' To Date: ' . $toDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted . '_to_' . $toDateFormatted;
        } elseif ($fromDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted;
        } elseif ($toDate) {
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'To Date: ' . $toDateFormatted;
            $filenameDatePart = '_to_' . $toDateFormatted;
        }

        $htmlContent = '<h1>Driver Earning Report</h1>';
        if ($dateFilterText) {
            $htmlContent .= '<p><strong>' . $dateFilterText . '</strong></p>';
        }

        $htmlContent .= '<style>
            body {
                font-family: "DejaVu Sans", sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #bfbfbf;
            }
            .bold-text {
                 font-weight: bold;
            }
            .text-capitalize {
                text-transform: capitalize;
            }
            h1{
                text-align:center;
            }
            p{
                font-size:18px;
            }
            .note {
                margin-top: 20px;
                font-size: 15px;
                text-align: center;
                color: green;
            }  
        </style>';
        $htmlContent .= '<table>';
        $htmlContent .= '<thead><tr>';

        foreach ($headings as $heading) {
            $htmlContent .= '<th>' . $heading . '</th>';
        }
        $htmlContent .= '</tr></thead>';
        $htmlContent .= '<tbody>';

        foreach ($mappedData as $row) {
            $htmlContent .= '<tr>';
            foreach ($row as $cell) {
                
                if (in_array($cell,['Total',$total_amount,$admin_commission,$driver_commission])) {
                    $htmlContent .= '<td class="bold-text">' . $cell . '</td>';
                } else {
                    $htmlContent .= '<td class="text-capitalize">' . $cell . '</td>';
                }
            }
            $htmlContent .= '</tr>';
        }

        $htmlContent .= '</tbody>';

        $htmlContent .= '</table>';
        $htmlContent .= '<div class="note">
                <p class="note">'.__('message.note_pdf_report').'</p>
                </div>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'landscape');
        $filename = 'driver-earning-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }

    public function serviceWiseReport(Request $request)
    {
        $pageTitle = __('message.report',['name' => __('message.service_wise')] );
        $auth_user = authSession();
        $params = [
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'rider_id' => $request->input('rider_id'),
            'driver_id' => $request->input('driver_id'),
            'service_id' => $request->input('service_id'),
        ];

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment'])
            ->where('status', 'completed')
            ->orderBy('id', 'desc');

        if ($params['from_date'] != null) {
            $ride_requests->whereDate('created_at', '>=', $params['from_date']);
        }
        if ($params['to_date'] != null) {
            $ride_requests->whereDate('created_at', '<=', $params['to_date']);
        }
        if ($params['rider_id'] != null) {
            $ride_requests->where('rider_id', $params['rider_id']);
        }
        if ($params['driver_id'] != null) {
            $ride_requests->where('driver_id', $params['driver_id']);
        }
        if ($params['service_id'] != null) {
            $ride_requests->where('service_id', $params['service_id']);
        }

        if ($request->ajax()) {
            return datatables()->of($ride_requests)
                ->addColumn('rider_display_name', function ($row) {
                    return optional($row->rider)->display_name ?? '-';
                })
                ->addColumn('driver_display_name', function ($row) {
                    return optional($row->driver)->display_name ?? '-';
                })
                ->addColumn('service_id', function ($row) {
                    return $row->service->name ?? '-';
                })
                ->addColumn('pickup_date_time', function ($row) {
                    return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
                })
                ->addColumn('drop_date_time', function ($row) {
                    $completed_ride = $row->rideRequestHistory()->where('history_type', 'completed')->first();
                    return $completed_ride ? dateAgoFormate($completed_ride->datetime, true) : '-';
                })
               
                ->addColumn('created_at', function ($row) {
                    return $row->created_at ? dateAgoFormate($row->created_at, true) : '-';
                })
                ->make(true);
        }

        // For non-AJAX requests, render the page with the initial data
        $data = $ride_requests->get();
        return view('report.servicewise-report', compact('pageTitle', 'auth_user','data','params'));
    }

    public function serviceWiseReportExport(Request $request)
    {
        $startDate = $request->input('from_date',null);
        $endDate = $request->input('to_date',null);
        $riderId = $request->input('rider_id',null);
        $driverId = $request->input('driver_id',null);
        $serviceId = $request->input('service_id',null);

        $start = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null;
        $end = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : null;

        $ride_requests = RideRequest::with(['rider', 'driver', 'payment'])
            ->where('status', 'completed');

        if ($start) {
            $ride_requests->whereDate('created_at', '>=', $start);
        }
        if ($end) {
            $ride_requests->whereDate('created_at', '<=', $end);
        }
        if ($riderId) {
            $ride_requests->where('rider_id', $riderId);
        }
        if ($driverId) {
            $ride_requests->where('driver_id', $driverId);
        }
        if ($serviceId) {
            $ride_requests->where('service_id', $serviceId);
        }

        $data = $ride_requests->get();

        $export = new ServiceWiseReportExport($request,$startDate,$endDate,$riderId,$driverId,$serviceId);
        $filename = ($start && $end) ? "servicewise-report_{$start}_to_{$end}.xlsx" : "servicewise-report_{$start}.xlsx";

        return Excel::download($export, $filename);
    }

    public function serviceWiseReportPdfExport(Request $request)
    {
        $export = new ServiceWiseReportExport($request);
        $collection = $export->collection();
        $mappedData = $collection->map([$export, 'map']);
        $headings = $export->headings('pdf');
        $dateFilterText = '';

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $filenameDatePart = '';

        if ($fromDate && $toDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted . ' To Date: ' . $toDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted . '_to_' . $toDateFormatted;
        } elseif ($fromDate) {
            $fromDateFormatted = \Carbon\Carbon::parse($fromDate)->format('Y-m-d');
            $dateFilterText = 'From Date: ' . $fromDateFormatted;
            $filenameDatePart = '_from_' . $fromDateFormatted;
        } elseif ($toDate) {
            $toDateFormatted = \Carbon\Carbon::parse($toDate)->format('Y-m-d');
            $dateFilterText = 'To Date: ' . $toDateFormatted;
            $filenameDatePart = '_to_' . $toDateFormatted;
        }

        $htmlContent = '<h1>Service Wise Report</h1>';
        if ($dateFilterText) {
            $htmlContent .= '<p><strong>' . $dateFilterText . '</strong></p>';
        }

        $htmlContent .= '<style>
            body {
                font-family: "DejaVu Sans", sans-serif;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 1px solid black;
            }
            th, td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #bfbfbf;
            }
            .bold-text {
                font-weight: bold;
            }
            .text-capitalize {
                text-transform: capitalize;
            }
            h1 {
                text-align: center;
            }
            p {
                font-size: 18px;
            }
            .note {
                margin-top: 20px;
                font-size: 15px;
                text-align: center;
                color: green;
            }  
        </style>';

        $htmlContent .= '<table>';
        $htmlContent .= '<thead><tr>';

        if (is_array($headings) && isset($headings) && is_array($headings)) {
            $columns = $headings;
        } else {
            $columns = ['Invalid Headings'];
        }

        foreach ($columns as $heading) {
            $htmlContent .= '<th>' . $heading . '</th>';
        }

        $htmlContent .= '</tr></thead>';
        $htmlContent .= '<tbody>';

        foreach ($mappedData as $row) {
            $htmlContent .= '<tr>';
            foreach ($row as $cell) {
                $htmlContent .= '<td class="text-capitalize">' . $cell . '</td>';
            }
            $htmlContent .= '</tr>';
        }

        $htmlContent .= '</tbody>';
        $htmlContent .= '</table>';

        $htmlContent .= '<div class="note">
                <p class="note">' . __('message.note_pdf_report') . '</p>
                </div>';

        $pdf = Pdf::loadHTML($htmlContent)->setPaper('a4', 'landscape');

        $filename = 'servicewise-report' . $filenameDatePart . '.pdf';

        return $pdf->download($filename);
    }

}
