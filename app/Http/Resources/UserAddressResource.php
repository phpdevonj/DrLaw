<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAddressResource extends JsonResource
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
            'label'             => $this->label,
            'custom_label'      => $this->custom_label,
            'address_line1'     => $this->address_line1,
            'address_line2'     => $this->address_line2,
            'city'              => $this->city,
            'state'             => $this->state,
            'zip_code'          => $this->zip_code,
            'country'           => $this->country,
            'latitude'          => $this->latitude,
            'longitude'         => $this->longitude,
            'is_default'        => $this->is_default,
            'created_at'        => $this->created_at,
            'updated_at'        => $this->updated_at,
        ];
    }
}
