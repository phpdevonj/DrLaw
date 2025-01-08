<?php

namespace App\Exports;

use App\Models\RideRequest;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class DriverReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $request;
    protected $counter;
    protected $driver_commission;
    protected $total_amount;
    protected $admin_commission;
    protected $endDate;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->counter = 1;
        $this->total_amount;
        $this->driver_commission;
        $this->endDate;
        $this->admin_commission;
    }

    public function collection()
    {

        $driver_ids = isset($_GET['driver']) ? $_GET['driver'] : null;
        $model = RideRequest::where('driver_id', $driver_ids)->where('status', 'completed')->myRide();;

        $fromDate = $this->request->input('from_date');
        $toDate = $this->request->input('to_date');

        if (!empty($fromDate)) {
            $model->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $model->whereDate('created_at', '<=', $toDate);
        }


        $rider_request = $model->get();

        $this->total_amount = $rider_request->sum('payment.total_amount');
        $this->driver_commission = $rider_request->sum('payment.driver_commission');
        $this->admin_commission = $rider_request->sum('payment.admin_commission');

        $data = $rider_request->map(function ($q) {
            return [
                'id' => $q->rider_id,
                'driver_name' => optional($q->driver)->display_name,
                'total_amount' => getPriceFormat(optional($q->payment)->total_amount),
                'driver_commission' => getPriceFormat(optional($q->payment)->driver_commission),
                'admin_commission' => getPriceFormat(optional($q->payment)->admin_commission),
                'created_at' => dateAgoFormate($q->created_at,true),
            ];
        })->toArray();

        // dd($data);
        $data[] = [
            'id' => '',
            'driver_name' => 'Total',
            'total_amount' => getPriceFormat($this->total_amount) ?? '-',
            'driver_commission' => getPriceFormat($this->driver_commission) ?? '-',
            'admin_commission' => getPriceFormat($this->admin_commission) ?? '-',
            'created_at' => ''
        ];
        return collect($data);
    }

    public function map($driver): array
    {
        if ($driver['driver_name'] === 'Total') {
            return [
                'Total',
                '',
                '',
                $driver['total_amount'],
                $driver['driver_commission'],
                $driver['admin_commission'],
                '',
            ];
        }
        return [
            $this->counter++,
            $driver['id'] ?? '-',
            $driver['driver_name'] ?? '-',
            $driver['total_amount'] ?? '-',
            $driver['driver_commission'] ?? '-',
            $driver['admin_commission'] ?? '-',
            $driver['created_at'] ?? '-',
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
                   'Driver Report' . ($date ? ' : ' . $date : ''),
                ],
                [''],
                [
                    __('message.no'),
                    __('message.rider_id'),
                    __('message.title_name',['title' => __('message.driver')]),
                    __('message.total_amount'),
                    __('message.driver_earning'),
                    __('message.admin_commission'),
                    __('message.created_at'),
                ],
            ];
        } else {
            $headings = [
                __('message.no'),
                __('message.rider_id'),
                __('message.title_name',['title' => __('message.driver')]),
                __('message.total_amount'),
                __('message.driver_earning'),
                __('message.admin_commission'),
                __('message.created_at'),
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

    public function getDriverSumData($type = null)
    {
        return getPriceFormat($this->$type);
    }
}