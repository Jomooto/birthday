<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Contact extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'data' => [
                'contact_id' => $this->id,
                'email' => $this->email,
                'name' => $this->name,
                'company' => $this->company,
                'birthday' => $this->birthday->format('m/d/Y'),
                'last_update' => $this->updated_at->diffForHumans(),
            ],
            'links' => [
                'self' => $this->path()
            ]
        ];
    }
}
