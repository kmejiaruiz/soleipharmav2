<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserUsernameGenerationTest extends TestCase
{
    use RefreshDatabase;
    public function test_generates_first_letter_and_last_name_for_new_user(): void
    {
        $username = User::generateUsername('Carlos', 'Gomez');
        $this->assertEquals('cgomez', $username);
    }

    public function test_falls_back_to_name_dot_lastname_when_taken(): void
    {
        User::create([
            'username' => 'mmoraga',
            'name'     => 'Maria Moraga',
            'password' => 'secret123',
            'role'     => 'cajero',
        ]);

        $username = User::generateUsername('Maria', 'Moraga');
        $this->assertEquals('maria.moraga', $username);
    }

    public function test_falls_back_to_initial_lastname_number_when_both_taken(): void
    {
        User::create([
            'username' => 'tuser',
            'name'     => 'Test User',
            'password' => 'secret123',
            'role'     => 'cajero',
        ]);
        User::create([
            'username' => 'test.user',
            'name'     => 'Test User',
            'password' => 'secret123',
            'role'     => 'cajero',
        ]);

        $username = User::generateUsername('Test', 'User');
        $this->assertEquals('tuser1', $username);
    }

    public function test_falls_back_when_email_already_taken(): void
    {
        User::create([
            'username' => 'otheruser',
            'email'    => 'jdoe@soleipharma.com',
            'password' => 'secret123',
            'role'     => 'cajero',
        ]);

        // jdoe username is free, but jdoe@soleipharma.com is taken, so it should fall back to john.doe
        $username = User::generateUsername('John', 'Doe');
        $this->assertEquals('john.doe', $username);
    }
}
