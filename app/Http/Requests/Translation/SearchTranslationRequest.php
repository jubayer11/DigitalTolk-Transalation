<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\Api\BaseApiRequest;

class SearchTranslationRequest extends BaseApiRequest
{
    /**
     * Validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10'],
            'tag' => ['nullable', 'string', 'max:50'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        $tags = $this->input('tags');

        if (is_array($tags)) {
            $normalizedTags = array_values(array_filter(array_map(function ($tag) {
                return is_string($tag) ? trim(strtolower($tag)) : $tag;
            }, $tags), fn ($tag) => $tag !== null && $tag !== ''));

            $this->merge([
                'tags' => $normalizedTags,
            ]);
        }

        $this->merge([
            'search' => is_string($this->search) ? trim($this->search) : $this->search,
            'locale' => is_string($this->locale) ? trim(strtolower($this->locale)) : $this->locale,
            'tag' => is_string($this->tag) ? trim(strtolower($this->tag)) : $this->tag,
            'perPage' => is_numeric($this->perPage) ? (int) $this->perPage : $this->perPage,
        ]);
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Search
            'search.string' => 'Search term must be a valid text value.',
            'search.max' => 'Search term may not be greater than 255 characters.',

            // Locale
            'locale.string' => 'Locale must be a valid text value.',
            'locale.max' => 'Locale may not be greater than 10 characters.',

            // Tag / Tags
            'tag.string' => 'Tag must be a valid text value.',
            'tag.max' => 'Tag may not be greater than 50 characters.',
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.string' => 'Each tag must be a valid text value.',
            'tags.*.max' => 'Each tag may not be greater than 50 characters.',

            // Pagination
            'perPage.integer' => 'Per page value must be a valid integer.',
            'perPage.min' => 'Per page value must be at least 1.',
            'perPage.max' => 'Per page value may not be greater than 100.',
        ];
    }

    /**
     * Custom attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'search' => 'search term',
            'locale' => 'locale',
            'tag' => 'tag',
            'tags' => 'tags',
            'tags.*' => 'tag',
            'perPage' => 'per page value',
        ];
    }
}
