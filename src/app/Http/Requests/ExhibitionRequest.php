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
            'name' => 'required|string',
            'detail' => 'required|string|max:255',
            'category' => 'required|array|min:1',
            'product_image' => 'required|image|mimes:jpeg,png|max:2048',
            'condition' => 'required|string|in:brand_new,used_like_new,used_good,used_fair,used_poor',
            'price' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:255',
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
            'name.required' => '商品名は必須です。',
            'detail.required' => '商品の詳細は必須です。',
            'detail.max' => '商品の詳細は255文字以内で入力してください。',
            'category.required' => 'カテゴリーは必須です。',
            'product_image.required' => '商品画像は必須です。',
            'product_image.image' => '画像ファイルを選択してください。',
            'product_image.mimes' => '画像はjpeg、png形式のみアップロード可能です。',
            'product_image.max' => '画像サイズは2MB以下にしてください。',
            'condition.required' => '商品の状態を選択してください。',
            'condition.in' => '無効な商品状態が選択されています。',
            'price.required' => '価格を入力してください。',
            'price.integer' => '価格は整数で入力してください。',
            'price.min' => '価格は0円以上で入力してください。',
            'brand.max' => 'ブランド名は255文字以内で入力してください。',
        ];
    }
}
