<?php

namespace App\Repositories;

use App\Models\MobileMoneyRequest;

class MobileMoneyRepository
{
    public function create(array $data)
    {
        return MobileMoneyRequest::create($data);
    }

    public function findByReference(string $referenceId)
    {
        return MobileMoneyRequest::where('reference_id', $referenceId)->first();
    }

    public function update(MobileMoneyRequest $request, array $data)
    {
        return $request->update($data);
    }
}
