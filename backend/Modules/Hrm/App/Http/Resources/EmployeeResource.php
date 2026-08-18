<?php

namespace Modules\Hrm\App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'gender' => $this->gender,
            'employment_type' => $this->employment_type,
            'birth_date' => $this->birth_date,
            'joining_date' => $this->joining_date,
            'designation_id' => $this->designation_id,
            'salary' => $this->salary,
            'has_id_document' => (bool) $this->id_document_path,
            'has_id_document_back' => (bool) $this->id_document_back_path,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
