<?php

namespace App\Exports;

use App\Models\LanguageWithKeyword;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;


class LanguageWithKeywordExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $language_with_keyword = LanguageWithKeyword::orderBy('id','asc')->get();

        $language = isset($_GET['language']) ? $_GET['language'] : null;
        if( $language != null ) {
            $language_with_keyword = $language_with_keyword->where('language_id',$language);
        }
        $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : null;
        if( $keyword != null ) {
            $language_with_keyword = $language_with_keyword->where('keyword_id',$keyword);
        }
        $screen = isset($_GET['screen']) ? $_GET['screen'] : null;
        if( $screen != null ) {
            $language_with_keyword = $language_with_keyword->where('screen_id',$screen);
        }   

        return $language_with_keyword;
        
    }
    
    public function map($language_with_keyword): array
    {
        return [
            $language_with_keyword->id,
            optional($language_with_keyword->languagelist)->language_name,
            optional($language_with_keyword->screen)->screenName,
            optional($language_with_keyword->defaultkeyword)->keyword_name,
            $language_with_keyword->keyword_value, 

        ];
    }

    public function headings(): array
    {
        return [
            'Id',
            __('message.language_name'),
            __('message.screen_name'),
            __('message.keyword_name'),
            __('message.keyword_value'),
        ];
    }
  
}