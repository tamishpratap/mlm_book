<?php

namespace App\Http\Requests\Admin;

use App\Models\Member;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UpdateMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin authorization is guarded at the route level via auth:admin middleware
        return true;
    }

    /**
     * Prepare the data for validation.
     * Trims inputs, normalizes empty strings to null for optional fields,
     * and strips leading '@' on usernames.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('name')) {
            $name = trim(preg_replace('/\s+/', ' ', (string) $this->name));
            $merge['name'] = $name !== '' ? $name : null;
        }

        if ($this->has('user_id')) {
            $userId = ltrim(trim((string) $this->user_id), '@');
            $merge['user_id'] = $userId !== '' ? $userId : null;
        }

        if ($this->has('email')) {
            $email = trim((string) $this->email);
            $merge['email'] = $email !== '' ? $email : null;
        }

        if ($this->has('phone')) {
            $phone = trim((string) $this->phone);
            $merge['phone'] = $phone !== '' ? $phone : null;
        }

        if ($this->has('gender')) {
            $gender = trim((string) $this->gender);
            $merge['gender'] = $gender !== '' ? $gender : null;
        }

        if ($this->has('date_of_birth')) {
            $dob = trim((string) $this->date_of_birth);
            $merge['date_of_birth'] = $dob !== '' ? $dob : null;
        }

        if ($this->has('city')) {
            $city = trim((string) $this->city);
            $merge['city'] = $city !== '' ? $city : null;
        }

        if ($this->has('country')) {
            $country = trim((string) $this->country);
            $merge['country'] = $country !== '' ? $country : null;
        }

        if ($this->has('website')) {
            $website = trim((string) $this->website);
            $merge['website'] = $website !== '' ? $website : null;
        }

        if ($this->has('bio')) {
            $bio = trim((string) $this->bio);
            $merge['bio'] = $bio !== '' ? $bio : null;
        }

        if (!empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->route('member');
        $memberId = $member instanceof Member ? $member->id : $member;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/[a-zA-Z]/',            // Must contain letters (rejects purely numeric inputs like "123456")
                'not_regex:/^\d+$/',           // Explicitly reject strings consisting only of digits
                'not_regex:/[<>]/',            // Reject dangerous HTML/script tags
            ],
            'user_id' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('members', 'user_id')->ignore($memberId),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('members', 'email')->ignore($memberId),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:25',
                'regex:/^[+]?[0-9\s\-().]{7,25}$/', // Rejects alphabetic garbage ("abc123", "random text") while supporting international formats
            ],
            'gender' => [
                'nullable',
                'string',
                Rule::in(['male', 'female', 'other', 'prefer_not_to_say']),
            ],
            'date_of_birth' => [
                'nullable',
                'date',
                'before:today',                // Reject today and any future birth dates
                'after:1900-01-01',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
                'not_regex:/[<>]/',
            ],
            'country' => [
                'nullable',
                'string',
                'max:100',
                'not_regex:/[<>]/',
            ],
            'website' => [
                'nullable',
                'url',
                'max:255',
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
                'not_regex:/<script\b[^>]*>(.*?)<\/script>/i',
            ],
            'is_verified' => [
                'nullable',
                'boolean',
            ],
            'profile_photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:5120',
            ],
            'cover_photo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],
            'remove_profile_photo' => [
                'nullable',
                'boolean',
            ],
            'remove_cover_photo' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Custom validation error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The full name is required.',
            'name.string' => 'The full name must be a valid text string.',
            'name.min' => 'The full name must be at least :min characters.',
            'name.max' => 'The full name cannot exceed :max characters.',
            'name.regex' => 'The full name cannot consist only of numbers and must contain valid letters.',
            'name.not_regex' => 'The full name contains invalid characters or HTML elements.',

            'user_id.required' => 'The username is required.',
            'user_id.min' => 'The username must be at least :min characters.',
            'user_id.max' => 'The username cannot exceed :max characters.',
            'user_id.regex' => 'The username may only contain letters, numbers, dots, dashes, and underscores.',
            'user_id.unique' => 'This username is already in use.',

            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already in use.',

            'phone.regex' => 'The phone number format is invalid. Please provide a valid phone number.',
            'phone.max' => 'The phone number cannot exceed :max characters.',

            'gender.in' => 'The selected gender is invalid.',

            'date_of_birth.date' => 'The date of birth must be a valid date.',
            'date_of_birth.before' => 'The date of birth cannot be in the future.',
            'date_of_birth.after' => 'The date of birth is too far in the past.',

            'city.max' => 'The city cannot exceed :max characters.',
            'city.not_regex' => 'The city contains invalid characters.',

            'country.max' => 'The country cannot exceed :max characters.',
            'country.not_regex' => 'The country contains invalid characters.',

            'website.url' => 'Please enter a valid website URL (e.g. https://example.com).',
            'website.max' => 'The website URL cannot exceed :max characters.',

            'bio.max' => 'The biography cannot exceed :max characters.',
            'bio.not_regex' => 'The biography cannot contain script tags.',

            'profile_photo.image' => 'The profile photo must be a valid image file.',
            'profile_photo.mimes' => 'The profile photo must be a file of type: jpeg, png, jpg, webp.',
            'profile_photo.max' => 'The profile photo size cannot exceed 5MB.',

            'cover_photo.image' => 'The cover photo must be a valid image file.',
            'cover_photo.mimes' => 'The cover photo must be a file of type: jpeg, png, jpg, webp.',
            'cover_photo.max' => 'The cover photo size cannot exceed 10MB.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Ensures structured 422 JSON response when JSON / API request,
     * or standard redirect back for Blade web submissions.
     */
    protected function failedValidation(Validator $validator)
    {
        if ($this->expectsJson() || $this->is('api/*') || $this->ajax()) {
            $response = response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);

            throw new ValidationException($validator, $response);
        }

        parent::failedValidation($validator);
    }
}
