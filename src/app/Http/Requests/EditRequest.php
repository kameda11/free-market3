<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditRequest extends FormRequest
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
            'post_code' => 'required|regex:/^\d{3}-\d{4}$/',
            'address' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png|max:2048'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'お名前は必須です。',
            'name.string' => 'お名前は文字列で入力してください。',
            'name.max' => 'お名前は255文字以内で入力してください。',
            'post_code.required' => '郵便番号は必須です。',
            'post_code.regex' => '郵便番号は「123-4567」の形式で入力してください。',
            'address.required' => '住所は必須です。',
            'address.string' => '住所は文字列で入力してください。',
            'address.max' => '住所は255文字以内で入力してください。',
            'building.string' => '建物名は文字列で入力してください。',
            'building.max' => '建物名は255文字以内で入力してください。',
            'profile_image.image' => '画像ファイルを選択してください。',
            'profile_image.mimes' => '画像はjpeg、png形式のみアップロード可能です。',
            'profile_image.max' => '画像サイズは2MB以下にしてください。'
        ];
    }
}
