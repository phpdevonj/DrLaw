<?php

namespace App\Exports;

use App\Models\WithdrawRequest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


class WithdrawRequestExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $withdraw_request = WithdrawRequest::where('status',0)->get();

        return $withdraw_request;
        
    }
    
    public function map($withdraw_request): array
    {
        $bank_detail = optional($withdraw_request->user->userBankAccount);
        $wallet_balance = '-';
        if($withdraw_request->status == 0 ) {
            $wallet_balance = optional($withdraw_request->user) && optional($withdraw_request->user)->userWallet ? optional($withdraw_request->user)->userWallet->total_amount : null;
        }
        return [
            optional($withdraw_request->user)->display_name ?? '-',
            getPriceFormat($withdraw_request->amount) ?? '-',
            $wallet_balance,
            dateAgoFormate($withdraw_request->created_at,true) ?? '-',
            dateAgoFormate($withdraw_request->updated_at,true) ?? '-',
            $bank_detail->bank_name ?? '-',
            $bank_detail->bank_code ?? '-',
            $bank_detail->account_holder_name ?? '-',
            $bank_detail->account_number ?? '-',
        ];
    }

    public function headings(): array
    {
        return [
            __('message.name'),
            __('message.amount'),
            __('message.available_balnce'),
            __('message.request_at'),
            __('message.action_at'),
            __('message.bank_name'),
            __('message.bank_code'),
            __('message.account_holder_name'),
            __('message.account_number'),
        ];
    }
  
}