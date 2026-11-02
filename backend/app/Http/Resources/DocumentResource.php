<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request): array {
        return ['id' => $this->id, 'original_name' => $this->original_name, 'summary' => $this->summary,
            'summary_status' => $this->summary_status, 'created_at' => $this->created_at];
    }
}
