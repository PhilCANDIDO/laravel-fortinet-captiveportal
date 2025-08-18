<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuestRegistrationRequest extends FormRequest
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
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:200',
            'visit_reason' => 'nullable|string|max:500',
            'portal_data' => 'nullable|string', // Base64 encoded JSON
        ];
    }

    /**
     * Get custom error messages
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'first_name.required' => __('validation.required', ['attribute' => __('fields.first_name')]),
            'last_name.required' => __('validation.required', ['attribute' => __('fields.last_name')]),
            'email.required' => __('validation.required', ['attribute' => __('fields.email')]),
            'email.email' => __('validation.email', ['attribute' => __('fields.email')]),
            'email.unique' => __('validation.unique', ['attribute' => __('fields.email')]),
        ];
    }

    /**
     * Prepare the data for validation
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // If portal_data is in query parameters, merge it into the request
        if ($this->query('portal_data')) {
            $this->merge([
                'portal_data' => $this->query('portal_data')
            ]);
        }
    }

    /**
     * Get the decoded portal data
     *
     * @return array|null
     */
    public function getPortalData(): ?array
    {
        $encodedData = $this->input('portal_data');
        
        if (empty($encodedData)) {
            return null;
        }
        
        try {
            $jsonData = base64_decode($encodedData, true);
            if ($jsonData === false) {
                return null;
            }
            
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            
            return $data;
        } catch (\Exception $e) {
            return null;
        }
    }
}