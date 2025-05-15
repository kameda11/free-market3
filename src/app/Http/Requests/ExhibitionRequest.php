<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'detail' => 'required|string|max:1000',
            'category' => 'required|array|min:1',
            'product_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'condition' => 'required|string|in:new,used_like_new,used_good,used_fair,used_poor',
            'price' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '商品名は必須です。',
            'detail.required' => '商品の詳細は必須です。',
            'category.required' => 'カテゴリーは必須です。',
            'product_image.required' => '商品画像は必須です。',
            'product_image.image' => '画像ファイルを選択してください。',
            'condition.required' => '商品の状態を選択してください。',
            'price.required' => '価格を入力してください。',
        ];
    }
}
