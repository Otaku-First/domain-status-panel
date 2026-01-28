<?php

namespace App\Http\Resources;

use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Domain
 */
class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hostname' => $this->hostname,
            'method' => $this->method,
            'interval' => $this->interval,
            'timeout' => $this->timeout,
            'body' => $this->body,
            'is_active' => $this->is_active,
            'last_checked_at' => $this->last_checked_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relationships
            'latest_check' => new DomainCheckResource($this->whenLoaded('latestCheck')),
            'checks' => DomainCheckResource::collection($this->whenLoaded('checks')),

            // Computed
            'checks_count' => $this->when(
                $this->checks_count !== null,
                $this->checks_count
            ),

            // Stats (always included for list/detail views)
            'is_down' => $this->isCurrentlyDown(),
            'uptime_24h' => $this->getUptimePercentage(24),
            'uptime_30d' => $this->getUptimePercentage(24 * 30),
            'avg_response_24h' => $this->getAvgResponseTime(24),
        ];
    }
}