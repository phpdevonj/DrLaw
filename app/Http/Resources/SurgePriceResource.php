<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SurgePriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $daysMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];


        return [
            'id'            => $this->id,
            'day'           => $this->day,
            'day_name'      => collect($this->day)->map(fn($d) => $daysMap[(int)$d] ?? null)->filter()->values(),
            'type'          => $this->type,
            'value'         => $this->value,
            'weightage'     => $this->getWeightage($this->value),
            'from_time'     => $this->from_time,
            'to_time'       => $this->to_time,
            'region_id'     => $this->region_id,
            'region_name'   => optional($this->region)->name,
            'address'       => $this->address,
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
            'radius'        => (int) $this->radius,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }

    private function getWeightage($value)
    {
        if ($value >= 1 && $value < 5) {
            return 2;
        } elseif ($value >= 5 && $value < 10) {
            return 4;
        } elseif ($value >= 10 && $value < 15) {
            return 6;
        } elseif ($value >= 15 && $value < 20) {
            return 8;
        } elseif ($value >= 20) {
            return 10;
        }

        return 1; // if value is < 1
    }
}
