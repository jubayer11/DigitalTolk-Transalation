<?php

namespace App\Http\Requests\Translation;

use App\Http\Requests\Api\BaseApiRequest;


class StoreTranslationRequest extends BaseApiRequest
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
            'key' => ['required', 'string', 'max:255'],
            'translations' => ['required', 'array', 'min:1'],
            'translations.*' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
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

        $this->merge([
            'key' => is_string($this->key) ? trim($this->key) : $this->key,
        ]);
    }
    public function messages(): array
    {
        return [
            // Key
            'key.required' => 'Translation key is required.',
            'key.string' => 'Translation key must be a valid text value.',
            'key.max' => 'Translation key may not be greater than 255 characters.',

            // Translations
            'translations.required' => 'At least one translation is required.',
            'translations.array' => 'Translations must be provided as an object/array.',
            'translations.min' => 'At least one translation entry is required.',
            'translations.*.required' => 'Each translation value is required.',
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
            'key' => 'translation key',
            'translations' => 'translations',
            'translations.*' => 'translation value',
            'tags' => 'tags',
            'tags.*' => 'tag',
        ];
    }

}
