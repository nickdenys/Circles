<?php

namespace App\Http\Requests;

use App\Enums\AlbumListMode;
use Illuminate\Foundation\Http\FormRequest;

class StoreAlbumReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Reviews may only be submitted from the user's own Reviewed list (an
     * edit in place) or from a list in the listening mode. Default-mode
     * lists, such as custom lists, cannot route an album into Reviewed.
     */
    public function authorize(): bool
    {
        $albumList = $this->route('albumList');

        if ($albumList->user_id !== $this->user()->id) {
            return false;
        }

        return $albumList->isReviewed() || $albumList->mode === AlbumListMode::Listening;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'numeric', 'min:0.5', 'max:5', 'multiple_of:0.5'],
            'review' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
