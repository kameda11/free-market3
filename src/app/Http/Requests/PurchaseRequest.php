<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
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
            'exhibition_id' => 'required|exists:exhibitions,id',
            'quantity' => 'required|integer|min:1',
            'address_id' => 'nullable|exists:addresses,id',
            'payment_method' => 'required|in:1,2',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'exhibition_id.required' => '商品IDは必須です。',
            'exhibition_id.exists' => '存在しない商品です。',
            'quantity.required' => '数量は必須です。',
            'quantity.integer' => '数量は整数で指定してください。',
            'quantity.min' => '数量は1以上で指定してください。',
            'address_id.exists' => '存在しない配送先です。',
            'payment_method.required' => '支払い方法は必須です。',
            'payment_method.in' => '無効な支払い方法です。',
        ];
    }
}
