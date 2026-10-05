<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('registration screen displays privacy notice', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200)
        ->assertSee('Data Protection & Privacy Notice')
        ->assertSee('By creating an account, you agree to the collection and processing of your personal data')
        ->assertSee('For students: We collect personal information necessary for educational purposes')
        ->assertSee('For guardians: You may be asked to provide information about students under your care')
        ->assertSee('Read our full Privacy Policy');
})->skip('Requires Vite build');

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'student',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('school users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'School User',
        'email' => 'school@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'school',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('community users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Community User',
        'email' => 'community@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'community',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('honeypot field prevents spam registration', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Spam Bot',
        'email' => 'spam@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'account_type' => 'student',
        'website' => 'http://spam-site.com',
    ]);

    $response->assertSessionHasErrors(['website']);
    $this->assertGuest();
});
