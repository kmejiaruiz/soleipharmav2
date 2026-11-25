<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('login') && ($this->has('email') || $this->has('username'))) {
            $this->merge([
                'login' => $this->input('email') ?? $this->input('username'),
            ]);
        }
    }

    /**
     * Acepta login por `username` o `email`.
     */
    public function rules(): array
    {
        return [
            'login'    => ['required', 'string'],   // username o email
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('username', $this->login)->orWhere('email', $this->login)->first();

        if ($user && ($user->is_locked || $user->status === 'locked')) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'user_locked' => 'Ha ocurrido un error, contacte con el administrador del sistema.',
            ]);
        }

        $loginField = filter_var($this->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt(
            [$loginField => $this->login, 'password' => $this->password],
            $this->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
