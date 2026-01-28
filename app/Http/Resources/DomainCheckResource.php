<?php

namespace App\Http\Resources;

use App\Models\DomainCheck;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DomainCheck
 */
class DomainCheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'result' => $this->result->value,
            'result_label' => $this->result->label(),
            'result_color' => $this->result->color(),
            'is_successful' => $this->result->isSuccessful(),
            'response_code' => $this->response_code,
            'response_time_ms' => $this->response_time_ms,
            'error_message' => $this->error_message,
            'checked_at' => $this->checked_at->toISOString(),
            'checked_at_human' => $this->checked_at->diffForHumans(),
        ];
    }
}