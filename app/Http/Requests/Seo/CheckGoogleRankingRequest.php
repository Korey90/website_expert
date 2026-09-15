<?php

namespace App\Http\Requests\Seo;

use App\Models\SearchRankingCheck;
use App\Support\Seo\DomainNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CheckGoogleRankingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'keyword' => trim(preg_replace('/\s+/', ' ', (string) $this->input('keyword')) ?? ''),
            'device' => $this->input('device', 'desktop'),
            'language' => $this->input('language', 'en'),
        ]);
    }

    public function rules(): array
    {
        return [
            'keyword' => ['required', 'string', 'min:2', 'max:150'],
            'website' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:150'],
            'device' => ['required', 'string', 'in:'.implode(',', SearchRankingCheck::DEVICES)],
            'language' => ['nullable', 'string', 'size:2'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $website = (string) $this->input('website', '');
            $normalized = app(DomainNormalizer::class)->normalize($website);

            if ($website !== '' && ! app(DomainNormalizer::class)->isValidDomain($normalized)) {
                $validator->errors()->add('website', __('rank_checker.errors.invalid_domain'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'keyword.required' => __('rank_checker.errors.keyword_required'),
            'website.required' => __('rank_checker.errors.website_required'),
            'location.required' => __('rank_checker.errors.location_required'),
        ];
    }
}
