<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Rules\PhoneNumber;


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
        $user_type = 'driver';

        if (request()->is('api*')) {
            $user_id = auth()->user()->id ?? request()->id;

            $rules = [
                'username' => [
                    'required',
                    Rule::unique('users', 'username')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                ],
                //'password' => 'required|min:8',
                'first_name' => 'required',
                'last_name' => 'required',
                'date_of_birth' => 'required',
                'license_expiration_date' => 'required',
                'social_security_number' => 'required',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users', 'email')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                ],
                'contact_number' => [
                    'required',
                    'max:20',
                    Rule::unique('users', 'contact_number')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                ],
            ];

            if (request()->isMethod('post')) {
                //$rules['password'] = 'required|min:8';
            } else {
                $rules['password'] = 'nullable|min:8';
            }

        } else {
            $method = strtolower($this->method());
            $user_id = $this->route()->driver;
            switch ($method) {
                case 'post':
                    $rules = [
                        'username' => [
                            'required',
                            Rule::unique('users', 'username')->where(fn ($q) => $q->where('user_type', $user_type)),
                        ],
                        'password' => 'required|min:8',
                        'email' => [
                            'required',
                            'email',
                            Rule::unique('users', 'email')->where(fn ($q) => $q->where('user_type', $user_type)),
                        ],
                        'contact_number' => [
                            'required',
                            'max:20',
                            new PhoneNumber,
                            Rule::unique('users', 'contact_number')->where(fn ($q) => $q->where('user_type', $user_type)),
                        ],
                        'userDetail.car_model' => 'required|string|max:255',
                        'userDetail.car_color' => 'required|string|max:255',
                        'userDetail.car_plate_number' => 'required|string|max:255|unique:user_details,car_plate_number',
                        'userDetail.car_production_year' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
                    ];
                    break;

                case 'patch':
                    $rules = [
                        'username' => [
                            'required',
                            Rule::unique('users', 'username')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                        ],
                        'email' => [
                            'required',
                            'max:191',
                            'email',
                            Rule::unique('users', 'email')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                        ],
                        'contact_number' => [
                            'max:20',
                            new PhoneNumber,
                            Rule::unique('users', 'contact_number')->where(fn ($q) => $q->where('user_type', $user_type))->ignore($user_id),
                        ],
                    ];
                    break;
            }
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'userDetail.car_model.*'  => __('message.car_model_required'),
            'userDetail.car_color.*'  => __('message.car_color_required'),
            'userDetail.car_plate_number.*'  => __('message.car_plate_number_required'),
            'userDetail.car_production_year.*'  => __('message.car_production_year_required'),
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
