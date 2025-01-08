<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class DriverRequest extends FormRequest
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
        $rules = [];

        if (request()->is('api*')) {
            $user_id = auth()->user()->id ?? request()->id;

            $rules = [
                'username' => 'required|unique:users,username,' . $user_id,
                'password' => 'required|min:8',
                'email' => 'required|email|unique:users,email,' . $user_id,
                'contact_number' => 'required|max:20|unique:users,contact_number,' . $user_id,
            ];

            if (request()->isMethod('post')) {
                $rules['password'] = 'required|min:8';
            } else {
                $rules['password'] = 'nullable|min:8';
            }

        } else {
            $method = strtolower($this->method());
            $user_id = $this->route()->driver;
            switch ($method) {
                case 'post':
                    $rules = [
                        'username' => 'required|unique:users,username',
                        'password' => 'required|min:8',
                        'email' => 'required|email|unique:users,email',
                        'contact_number' => 'required|max:20|unique:users,contact_number',
                        'userDetail.car_model' => 'required|string|max:255',
                        'userDetail.car_color' => 'required|string|max:255',
                        'userDetail.car_plate_number' => 'required|string|max:255|unique:user_details,car_plate_number',
                        'userDetail.car_production_year' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
                    ];
                    break;

                case 'patch':
                    $rules = [
                        'username' => 'required|unique:users,username,' . $user_id,
                        'email' => 'required|max:191|email|unique:users,email,' . $user_id,
                        'contact_number' => 'max:20|unique:users,contact_number,' . $user_id,
                    ];
                    break;
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'userDetail.car_model.*'  =>'Car Model is required.',
            'userDetail.car_color.*'  =>'Car Color is required.',
            'userDetail.car_plate_number.*'  =>'Car Plate number is required.',
            'userDetail.car_production_year.*'  =>'Car production year is required.',
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
