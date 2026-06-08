<?php

namespace App\Http\Requests;

use App\Enums\AlbumListMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlbumListModeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $albumList = $this->route('albumList');

        return $albumList->user_id === $this->user()->id
            && ! $albumList->isReviewed();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::enum(AlbumListMode::class)],
        ];
    }
}
