<?php

namespace App\Http\Requests;

use App\Enums\AlbumListMode;
use App\Rules\ValidAlbumListTitleSlug;
use Illuminate\Container\Container;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlbumListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', Container::getInstance()->make(ValidAlbumListTitleSlug::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'mode' => ['nullable', Rule::enum(AlbumListMode::class)],
            'force_slug' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'A title is required for your list.',
        ];
    }
}
