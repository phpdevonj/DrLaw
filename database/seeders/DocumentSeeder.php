<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Document;

class DocumentSeeder extends Seeder
{
    public function run()
    {
        $documents = [
            [
                'name' => 'Private Hire Insurance Certificate',
                'type' => 'vehicle',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'MOT Test Certificate',
                'type' => 'vehicle',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'PHV (Private Hire Vehicle License)',
                'type' => 'vehicle',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'DVLA Electronic Counterpart Check Code',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 1, // Codes usually expire
                'status' => 1,
            ],
            [
                'name' => 'PHD Badge (Private Hire Driver Badge)',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'PHL (Private Hire Driver License)',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'DVLA Driving License (Pink Card – front)',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Profile Photo',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 0,
                'status' => 1,
            ],
            [
                'name' => 'V5 Logbook (Full)',
                'type' => 'vehicle',
                'is_required' => 1,
                'has_expiry_date' => 0,
                'status' => 1,
            ],
            [
                'name' => 'National Insurance Number',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 0,
                'status' => 1,
            ],
            [
                'name' => 'Bank Statement',
                'type' => 'driver',
                'is_required' => 1,
                'has_expiry_date' => 0, // Statements are recent but don't "expire" in the same way, though usually < 3 months old is required. Setting 0 for now as it's not a fixed expiry date like a license.
                'status' => 1,
            ],
            [
                'name' => 'Exemption Certificate',
                'type' => 'driver',
                'is_required' => 0,
                'has_expiry_date' => 1,
                'status' => 1,
            ],
            [
                'name' => 'Insurance Supporting Doc',
                'type' => 'vehicle',
                'is_required' => 0,
                'has_expiry_date' => 0,
                'status' => 1,
            ],
            [
                'name' => 'Logbook Supporting Doc',
                'type' => 'vehicle',
                'is_required' => 0,
                'has_expiry_date' => 0,
                'status' => 1,
            ],
        ];

        foreach ($documents as $document) {
            Document::updateOrCreate(['name' => $document['name']], $document);
        }
    }
}
