<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class contactResource extends JsonResource
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
            'id'        => $this->id,
            'name'      => $this->name,
            'phone'     => $this->phone,
            'image'     => $this->getImage(),
        ];
    }
}
