<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    public function test_authenticated_user_can_update_name_and_email(): void
    {
        $user = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => 'Dimas Wicaksono Updated',
            'email' => 'admin@kafeina.id',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Dimas Wicaksono Updated',
            'email' => 'admin@kafeina.id',
        ]);

        // Restore original name
        $user->update(['name' => $originalName, 'email' => $originalEmail]);
    }

    public function test_user_can_update_cashier_pin(): void
    {
        $user = User::where('email', 'admin@kafeina.id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'pin_code' => '654321',
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertTrue(Hash::check('654321', $user->pin_code));

        // Restore PIN
        $user->update(['pin_code' => Hash::make('123456')]);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::where('email', 'admin@kafeina.id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'password123',
            'new_password' => 'secret12345',
            'new_password_confirmation' => 'secret12345',
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertTrue(Hash::check('secret12345', $user->password));

        // Restore password
        $user->update(['password' => Hash::make('password123')]);
    }

    public function test_user_cannot_update_password_if_current_password_is_incorrect(): void
    {
        $user = User::where('email', 'admin@kafeina.id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'wrongpassword',
            'new_password' => 'secret12345',
            'new_password_confirmation' => 'secret12345',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $user->refresh();
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_email_must_be_unique_across_users(): void
    {
        $user = User::where('email', 'admin@kafeina.id')->firstOrFail();
        $otherUser = User::where('email', '!=', 'admin@kafeina.id')->firstOrFail();

        $response = $this->actingAs($user)->post(route('profile.update'), [
            'name' => $user->name,
            'email' => $otherUser->email,
        ]);

        $response->assertSessionHasErrors(['email']);
    }
}
