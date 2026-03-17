<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO (Data Transfer Object) for user registration.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: Validation in FormRequest class (RegisterRequest extends FormRequest)
 *   with rules() method returning ['email' => 'required|email|unique:users', ...]
 * - Symfony: Validation via attributes on DTO properties
 *   using #[Assert\NotBlank], #[Assert\Email], etc.
 *
 * WHY DTOs?
 * - Never expose entities directly to API consumers
 * - Decouple request/response format from database schema
 * - Validate input before it touches the domain layer
 * - Laravel's FormRequest does validation + authorization in one class
 * - Symfony separates: DTO (data shape) + Validator (rules) + Security (authorization)
 */
class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(
        min: 6,
        max: 128,
        minMessage: 'Password must be at least {{ limit }} characters.',
        maxMessage: 'Password cannot exceed {{ limit }} characters.',
    )]
    public string $password = '';
}
