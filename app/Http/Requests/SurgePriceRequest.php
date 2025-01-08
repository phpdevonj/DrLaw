<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SurgePriceRequest extends FormRequest
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
                    'day'  => 'required|unique:surge_prices,day',
                    'value'     => 'required|numeric',
                    'from_time.*' => 'required|date_format:H:i',
                    'to_time.*' => 'required|date_format:H:i|after:from_time.*',
                ];
                break;
            case 'patch':
                $rules = [
                    'from_time.*' => 'required|date_format:H:i',
                    'to_time.*' => 'required|date_format:H:i|after:from_time.*',
                ];
                break;
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'day.required' => 'Day is required.',
            'day.unique' => 'Day must be unique.',
            'value.required' => 'Value is required.',
            'value.numeric' => 'Value must be numeric.',
            'from_time.*.required' => 'From time is required for all records.',
            'from_time.*.date_format' => 'From time must be in the format HH:MM.',
            'to_time.*.required' => 'To time is required for all records.',
            'to_time.*.date_format' => 'To time must be in the format HH:MM.',
            'to_time.*.after' => 'To time must be after the corresponding from time.',
        ];
    }

     /**
     * @param Validator $validator
     */
    protected function failedValidation(Validator $validator) {
        $data = [
            'status' => true,
            'message' => $validator->errors()->first(),
            'all_message' =>  $validator->errors()
        ];

        if ( request()->is('api*')){
           throw new HttpResponseException( response()->json($data,422) );
        }

        if ($this->ajax()) {
            throw new HttpResponseException(response()->json($data,422));
        } else {
            throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
        }
    }
}
