<?php

namespace App\Http\Requests;

use App\Enums\AlbumSort;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlbumListSortRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('albumList')->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => ['required', Rule::enum(AlbumSort::class)],
            'direction' => ['required', 'in:asc,desc'],
        ];
    }
}
