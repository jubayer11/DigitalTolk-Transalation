<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\Api\BaseApiRequest;

class ExportTranslationRequest extends BaseApiRequest
{
    /**
     * Validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'max:10'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        // ✅ Read query/body input, not the Request's internal locale property
        $locale = $this->input('locale');

        $this->merge([
            'locale' => is_string($locale) ? trim(strtolower($locale)) : $locale,
        ]);

        $tags = $this->input('tags');

        // Optional: support "tags=web,homepage" too
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }

        if (is_array($tags)) {
            $normalizedTags = array_values(array_filter(array_map(function ($tag) {
                return is_string($tag) ? trim(strtolower($tag)) : $tag;
            }, $tags), fn ($tag) => $tag !== null && $tag !== ''));

            $this->merge([
                'tags' => $normalizedTags,
            ]);
        }
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Locale
            'locale.required' => 'Locale is required.',
            'locale.string' => 'Locale must be a valid text value.',
            'locale.max' => 'Locale may not be greater than 10 characters.',

            // Tags
            'tags.array' => 'Tags must be provided as an array.',
            'tags.*.string' => 'Each tag must be a valid text value.',
            'tags.*.max' => 'Each tag may not be greater than 50 characters.',
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
            'locale' => 'locale',
            'tags' => 'tags',
            'tags.*' => 'tag',
        ];
    }
}
