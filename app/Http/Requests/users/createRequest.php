<?php

namespace App\Http\Requests\users;

use Illuminate\Foundation\Http\FormRequest;

class createRequest extends FormRequest
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
        return [
            'username' => 'required|string|unique:users',
            'name'     => 'required|string',
            'password' => 'required|string',
            'role_id' => 'required|string|exists:roles,id',
            'payment_method' => 'nullable|string|in:weekly,daily,monthly',
            'working_hours_count' => 'nullable|numeric',
            'salary' => 'nullable|numeric',
            'vacation_days_count' => 'nullable|numeric',
            'hour_price' => 'nullable|numeric',
            'overtime_hour_price' => 'nullable|numeric',
            'presence_time' => 'nullable|date_format:H:i',
            'leave_time' => 'nullable|date_format:H:i',
        ];
    }
}
