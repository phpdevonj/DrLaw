@component('mail::message')

<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center" style="padding-bottom: 20px;">
            <img src="{{ $logoUrl }}" alt="{{ env('APP_NAME') }}" style="max-height: 80px; display: block;">
        </td>
    </tr>
</table>

# Hello {{ $notifiable->display_name }},

We hope you are doing well.

This is an important update regarding your driver account with **{{ env('APP_NAME') }}**.

⚠️ **{{ $message }}**

**Document Name:** {{ $doc->document->name }}  
**Expired On:** {{ \Carbon\Carbon::parse($doc->expire_date)->format('d M Y') }}

To continue receiving ride requests without interruption, please update your document as soon as possible.

Failure to update may temporarily restrict your ability to accept rides.

Thanks,  
**{{ env('APP_NAME') }}**

@endcomponent