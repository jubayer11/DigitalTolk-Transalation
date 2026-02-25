<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\Api\BaseApiRequest;

class UpdateTranslationRequest extends BaseApiRequest
{
    /**
     * Validation rules.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'translations' => ['sometimes', 'array', 'min:1'],
            'translations.*' => ['required_with:translations', 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Normalize input before validation.
     */
    protected function prepareForValidation(): void
    {
        $translations = $this->input('translations');
        $tags = $this->input('tags');

        if (is_array($translations)) {
            $normalizedTranslations = [];

            foreach ($translations as $locale => $content) {
                $normalizedLocale = is_string($locale) ? trim(strtolower($locale)) : $locale;
                $normalizedTranslations[$normalizedLocale] = is_string($content) ? trim($content) : $content;
            }

            $this->merge([
                'translations' => $normalizedTranslations,
            ]);
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
            // Translations
            'translations.array' => 'Translations must be provided as an object/array.',
            'translations.min' => 'At least one translation entry is required when translations are provided.',
            'translations.*.required_with' => 'Each translation value is required when translations are provided.',
            'translations.*.string' => 'Each translation value must be a valid text value.',

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
            'translations' => 'translations',
            'translations.*' => 'translation value',
            'tags' => 'tags',
            'tags.*' => 'tag',
        ];
    }
}
