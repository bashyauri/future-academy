<?php

declare(strict_types=1);

it('loads the privacy policy page successfully', function () {
    $this->get(route('privacy-policy'))
        ->assertOk()
        ->assertSee('Privacy Policy')
        ->assertSee('Information We Collect')
        ->assertSee('Future Academy');
});

it('privacy policy page is accessible without authentication', function () {
    $this->get('/privacy-policy')
        ->assertOk();
});

it('privacy policy route is named correctly', function () {
    expect(route('privacy-policy'))->toContain('/privacy-policy');
});

it('privacy policy page contains contact section', function () {
    $this->get(route('privacy-policy'))
        ->assertSee('Contact Us')
        ->assertSee('Data Security');
});
