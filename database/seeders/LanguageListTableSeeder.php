<?php

namespace Database\Seeders;

use App\Models\DefaultKeyword;
use App\Models\LanguageDefaultList;
use App\Models\LanguageList;
use App\Models\LanguageWithKeyword;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageListTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        DB::table('language_lists')->delete();

        $default_language = LanguageDefaultList::where('countryCode', 'en-US')->first();

        $language = LanguageList::create([
            'language_id' => $default_language['id'],   
            'language_name' => $default_language['languageName'],
            'language_code' => $default_language['languageCode'],
            'country_code' => $default_language['countryCode'],
            'is_rtl' => 0,
            'status' => 1,
            'is_default' => 1,
        ]);

        $language_keyword = DefaultKeyword::all();
        if(count($language_keyword) > 0){
            foreach($language_keyword as $value){
                $languagedata = [
                    'id' => null,
                    'keyword_id' => $value->keyword_id,
                    'screen_id' => $value->screen_id,
                    'language_id' => $language->id,
                    'keyword_value' => $value->keyword_value,
                ];
                LanguageWithKeyword::create($languagedata);
            }
        }
        updateLanguageVersion();
    }
}