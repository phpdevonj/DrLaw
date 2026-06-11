<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DefaultKeywordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $method = strtolower($this->method());

        $rules = [];
        switch ($method) {
            case 'post':
                $rules = [
                    'keyword_name' => 'required',
                    'keyword_value' => 'required',
                    'screen_id' => 'required',
                ];
                break;
            case 'patch':
                $rules = [
                    'keyword_name' => 'required',
                    'keyword_value' => 'required',
                    'screen_id' => 'required',
                ];
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'keyword_name.required'  =>'The keyword title field is required.',
            'screen_id.required'  =>'The screen name field is required.',
        ];
    }
    /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator)
    {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json([ 'status' => false, 'event' => 'validation', 'message' => $validator->errors()->first() ]));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
