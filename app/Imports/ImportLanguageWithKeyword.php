<?php

namespace App\Imports;

use App\Models\LanguageWithKeyword;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;


class ImportLanguageWithKeyword implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
       
        $id = $row['id'];

        $languagewithkeyword = LanguageWithKeyword::find($id);

        if($languagewithkeyword){
            $languagewithkeyword->update([
                'keyword_value' => $row['keyword_value'],
            ]);
        }
    }

    public function headingRow(): int
    {
        return 1;
    }
}
