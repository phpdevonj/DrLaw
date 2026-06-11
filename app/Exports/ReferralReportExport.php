<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Illuminate\Support\Facades\DB;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;

class ReferralReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithEvents
{
    protected $request;
    protected $counter;

    public function __construct($request)
    {
        $this->request = $request;
        $this->counter = 1;
    }

    public function collection()
    {
        $referrals = Referral::with(['referrer', 'referred']); 

        $fromDate = $this->request->input('from_date');
        $toDate = $this->request->input('to_date');
        $referrer_id = $this->request->input('referrer_id');
        $referred_id = $this->request->input('referred_id');
        $status = $this->request->input('status');

        if (!empty($fromDate)) {
            $referrals->whereDate('created_at', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $referrals->whereDate('created_at', '<=', $toDate);
        }

        if (!empty($referrer_id)) {
            $referrals->where('referrer_id', $referrer_id);
        }

        if (!empty($referred_id)) {
            $referrals->where('referred_id', $referred_id);
        }

        if (!empty($status)) {
            $referrals->where('status', $status);
        }

        $data = $referrals->get()->map(function($row) {
            return [
                'id' => $row->id,
                'referrer_id' => $row->referrer->email,
                'referred_id' => $row->referred->email,
                'referred_device_id' => $row->referred->device_id,
                'referred_device_type' => ucfirst($row->referred->device_type),
                'status' => ucfirst($row->status),
                'created_at' => dateAgoFormate($row->created_at, true)
            ];
        })->toArray();
        return collect($data); // Return as a collection
    }

    public function map($order): array
    {
        return [
            $this->counter++,
            $order['referrer_id'] ?? '-',
            $order['referred_id'] ?? '-',
            $order['referred_device_id'] ?? '-',
            $order['referred_device_type'] ?? '-',
            $order['status'] ?? '-',
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
                    'Referral Report' . ($date ? ' : ' . $date : ''),
                ],
                [''],
                [
                    'ID',
                    'Referrer Email',
                    'Referred Email',
                    'Referred Device ID',
                    'Referred Device Type',
                    'Status',
                    'Created At',
                ],
            ];
        } else {
            $headings = [
                'ID',
                'Referrer Email',
                'Referred Email',
                'Referred Device ID',
                'Referred Device Type',
                'Status',
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