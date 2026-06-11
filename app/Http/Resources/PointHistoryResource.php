<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PointHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->user_id,
            'user_display_name' => optional($this->user)->display_name,
            'type'              => $this->type,
            'transaction_type'  => $this->transaction_type,
            'amount'            => $this->amount,
            'balance'           => $this->balance,
            'point_balance'    => optional($this->point_user)->total_points,
            'datetime'          => $this->datetime,
            'ride_request_id'   => $this->ride_request_id,
            'description'       => $this->description,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
