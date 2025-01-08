<?php

namespace App\Exports;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use App\Helpers\Helper;
use App\Models\RideRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class AdminReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $request;
    protected $counter;
    protected $totalAmount;
    protected $driver_commission;
    protected $admin_commission;
    protected $startDate;
    protected $endDate;
    protected $riderId;
    protected $driverId;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->counter = 1;
        $this->totalAmount;
        $this->driver_commission;
        $this->admin_commission;
        $this->startDate;
        $this->endDate;
        $this->riderId;
        $this->driverId;
    }

    public function collection()
    {
        $driver_ids = User::where('user_type', 'driver')->pluck('id');
        $rider_ids = User::where('user_type', 'rider')->pluck('id');

        $ride_requests_rider = RideRequest::whereIn('rider_id', $rider_ids)->where('status', 'completed')->myRide()->orderBy('id', 'desc');
        $ride_requests_driver = RideRequest::whereIn('driver_id', $driver_ids)->where('status', 'completed')->myRide()->orderBy('id', 'desc');

        $fromDate = $this->request->input('from_date');
        $toDate = $this->request->input('to_date');
        $riderId = $this->request->input('rider_id');
        $driverId = $this->request->input('driver_id');

        if (!empty($fromDate)) {
            $ride_requests_rider->whereDate('created_at', '>=', $fromDate);
            $ride_requests_driver->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $ride_requests_rider->whereDate('created_at', '<=', $toDate);
            $ride_requests_driver->whereDate('created_at', '<=', $toDate);
        }

        if (!empty($riderId)) {
            $ride_requests_rider->where('rider_id', $riderId);
            $ride_requests_driver->where('rider_id', $riderId);
        }

        if (!empty($driverId)) {
            $ride_requests_rider->where('driver_id', $driverId);
            $ride_requests_driver->where('driver_id', $driverId);
        }

        $rider_request = $ride_requests_rider->get()->merge($ride_requests_driver->get());

        $this->totalAmount = $rider_request->sum('payment.total_amount');
        $this->admin_commission = $rider_request->sum('payment.admin_commission');
        $this->driver_commission = $rider_request->sum('payment.driver_commission');

        $data = $rider_request->map(function ($q) {
            $completed_ride_history = $q->rideRequestHistory()->where('history_type','completed')->first();
            $in_progress_ride_history = $q->rideRequestHistory()->where('history_type','in_progress')->first();
            return [

                'id' => $q->id,
                'rider_name' => optional($q->rider)->display_name,
                'driver_name' => optional($q->driver)->display_name,
                'pickup_date_time' => $in_progress_ride_history ? dateAgoFormate($in_progress_ride_history->datetime,true) : '-',
                'drop_date_time' => $completed_ride_history ? dateAgoFormate($completed_ride_history->datetime,true) : '-',
                'total_amount' => getPriceFormat(optional($q->payment)->total_amount),
                'admin_commission' => getPriceFormat(optional($q->payment)->admin_commission),
                'driver_commission' => getPriceFormat(optional($q->payment)->driver_commission),
                'created_at' => dateAgoFormate($q->created_at, true) ,
                // 'status' => $q->status,
            ];
        })->toArray();

        $data[] = [
            'id' => '',
            'rider_name' => 'Total',
            'driver_name' => '',
            'pickup_date_time' => '',
            'drop_date_time' => '',
            'total_amount' => getPriceFormat($this->totalAmount) ?? '-',
            'admin_commission' => getPriceFormat($this->admin_commission) ?? '-',
            'driver_commission' => getPriceFormat($this->driver_commission) ?? '-',
            'created_at' => '-',
        ];

        return collect($data);  // Return as a collection
    }


    public function map($order): array
    {
        if ($order['rider_name'] === 'Total') {
            return [
                'Total',
                '',
                '',
                '',
                '',
                '',
                $order['total_amount'],
                // '',
                $order['admin_commission'],
                $order['driver_commission'],
                '',
                // '',
            ];
        }
        return [
            $this->counter++,
            $order['id'] ?? '-',
            $order['rider_name'] ?? '-',
            $order['driver_name'] ?? '-',
            $order['pickup_date_time'] ?? '-',
            $order['drop_date_time'] ?? '-',
            $order['total_amount'] ?? '-',
            $order['admin_commission'] ?? '-',
            $order['driver_commission'] ?? '-',
            $order['created_at'] ?? '-',
            // $order['status'] ?? '-',
        ];
    }

    public function headings($exportType = 'excel'): array
    {
        if ($exportType === 'excel') {
            $fromDate = $this->request->input('from_date');
            $toDate = $this->request->input('to_date');
            $date = ($fromDate && $toDate) ? 'From Date: ' . ($fromDate ?: '-') . ' To Date ' . ($toDate ?: '-') : null;
    
            $headings = [
                [
                   'Admin Earning Report' . ($date ? ' : ' . $date : ''),
                ],
                [''],
                [
                    __('message.no'),
                    __('message.ride_request_id'),
                    __('message.title_name',['title' => __('message.rider')]),
                    __('message.title_name',['title' => __('message.driver')]),
                    __('message.pickup_date_time'),
                    __('message.drop_date_time'),
                    __('message.total_amount'),
                    __('message.admin_commission'),
                    __('message.driver_commission'),
                    __('message.created_at'),
                    // __('message.status'),
                ],
            ];
        } else {
            $headings = [
                __('message.no'),
                __('message.ride_request_id'),
                __('message.rider'),
                __('message.driver'),
                __('message.pickup_date_time'),
                __('message.drop_date_time'),
                __('message.total_amount'),
                __('message.admin_commission'),
                __('message.driver_commission'),
                __('message.created_at'),
                // __('message.status'),
            ];
        }
    
        return $headings;
    }
    
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->mergeCells('A1:' . $highestColumn . '1');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        for ($row = 1; $row <= $highestRow; $row++) {

            $cellValueI = $sheet->getCell('H' . $row)->getValue();
            if ($cellValueI) {
                $sheet->getCell('H' . $row)->setValue(ucfirst(strtolower($cellValueI)));
            }

            if ($row === 1 || $sheet->getCell('C' . $row)->getValue() === 'Total') {
                $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        }
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->getDelegate()->getStyle('A:J')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

    public function getTotalAmountSum()
    {
        return getPriceFormat($this->totalAmount);
    }

    public function getAdminCommission()
    {
        return getPriceFormat($this->admin_commission);
    }

    public function getDriverCommission()
    {
        return getPriceFormat($this->driver_commission);
    }
}