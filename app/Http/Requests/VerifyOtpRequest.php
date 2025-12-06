<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOTPRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|regex:/^(05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/',
            'otp_code' => 'required|string|size:4'
        ];
    }
}
