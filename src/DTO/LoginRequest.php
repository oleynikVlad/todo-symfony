<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for login requests.
 *
 * LARAVEL vs SYMFONY:
 * - Laravel: Auth::attempt(['email' => $email, 'password' => $password])
 * - Symfony: JSON login authenticator handles this automatically,
 *   but we use this DTO for manual login with JWT generation.
 */
class LoginRequest
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Password is required.')]
    public string $password = '';
}
