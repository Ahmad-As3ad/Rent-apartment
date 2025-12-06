<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'date_of_birth' => 'required|date|before:-18 years',
            'id_card_picture' => 'required|image|mimes:jpeg,png,jpg|max:5120'
        ];
    }
}
