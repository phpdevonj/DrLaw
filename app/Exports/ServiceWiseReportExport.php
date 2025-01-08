<?php

namespace App\Exports;

use App\Models\RideRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ServiceWiseReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $request;
    protected $counter;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->counter = 1;
    }

    public function collection()
    {
        // Fetching the ride requests
        $ride_requests_rider = RideRequest::where('status', 'completed')->orderBy('id', 'desc');

        $fromDate = $this->request->input('from_date');
        $toDate = $this->request->input('to_date');
        $riderId = $this->request->input('rider_id');
        $driverId = $this->request->input('driver_id');
        $serviceId = $this->request->input('service_id');

        if (!empty($fromDate)) {
            $ride_requests_rider->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $ride_requests_rider->whereDate('created_at', '<=', $toDate);
        }

        if (!empty($riderId)) {
            $ride_requests_rider->where('rider_id', $riderId);
        }

        if (!empty($driverId)) {
            $ride_requests_rider->where('driver_id', $driverId);
        }

        if (!empty($serviceId)) {
            $ride_requests_rider->where('service_id', $serviceId);
        }

        $rider_request = $ride_requests_rider->get();

        // Mapping data to include only the required fields
        $data = $rider_request->map(function ($q) {
            $completed_ride_history = $q->rideRequestHistory()->where('history_type', 'completed')->first();
            $in_progress_ride_history = $q->rideRequestHistory()->where('history_type', 'in_progress')->first();

            return [
                'id' => $q->id,
                'service' => $q->service->name, // Assuming 'service_type' represents the service name
                'rider_name' => optional($q->rider)->display_name,
                'driver_name' => optional($q->driver)->display_name,
                'pickup_date_time' => $in_progress_ride_history ? dateAgoFormate($in_progress_ride_history->datetime, true) : '-',
                'drop_date_time' => $completed_ride_history ? dateAgoFormate($completed_ride_history->datetime, true) : '-',
                'created_at' => dateAgoFormate($q->created_at, true),
            ];
        })->toArray();

        return collect($data); // Return as a collection
    }

    public function map($order): array
    {
        return [
            $this->counter++,
            $order['service'] ?? '-',
            $order['rider_name'] ?? '-',
            $order['driver_name'] ?? '-',
            $order['pickup_date_time'] ?? '-',
            $order['drop_date_time'] ?? '-',
            $order['created_at'] ?? '-',
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
                    'Service Wise Report' . ($date ? ' : ' . $date : ''),
                ],
                [''],
                [
                    'ID',
                    'Service',
                    'Rider Name',
                    'Driver Name',
                    'Pickup Date Time',
                    'Drop Date Time',
                    'Created At',
                ],
            ];
        } else {
            $headings = [
                'ID',
                'Service',
                'Rider Name',
                'Driver Name',
                'Pickup Date Time',
                'Drop Date Time',
                'Created At',
            ];
        }

        return $headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Merge cells for the title
                $sheet->mergeCells('A1:G1');
                
                // Set title style: bold and centered
                $sheet->getStyle('A1:G1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Set headings style: bold
                $sheet->getStyle('A3:G3')->applyFromArray([
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Set column alignment for data
                $sheet->getStyle('A:G')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            },
        ];
    }

}
