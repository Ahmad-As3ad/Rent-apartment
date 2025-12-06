<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendOTPRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'phone_number' => 'required|string|digits:10|starts_with:09',
            'user_type' => 'required|in:owner,tenant'
        ];
    }
}
