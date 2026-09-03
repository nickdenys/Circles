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
     * The score sort is only available on the Reviewed list, the only list that carries ratings.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sortRule = Rule::enum(AlbumSort::class);

        if (! $this->route('albumList')->isReviewed()) {
            $sortRule->except(AlbumSort::Score);
        }

        return [
            'sort' => ['required', $sortRule],
            'direction' => ['required', 'in:asc,desc'],
        ];
    }
}
