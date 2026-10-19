<?php

declare(strict_types=1);

it('loads the homepage successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Future Academy')
        ->assertSee('1919844')
        ->assertSee('noreply@futureacademy-rm.com')
        ->assertSee('futureacademy-rm@gmail.com')
        ->assertSee('07071100037')
        ->assertSee('Corporate Affairs Commission');
});

it('homepage displays CAC registration and contact links', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Ramenshuka Global Resources')
        ->assertSee('RMS GL')
        ->assertSee('1919844')
        ->assertSee('Company Registered')
        ->assertDontSee('Government Accreditation')
        ->assertSee('https://wa.me/2347071100037', false)
        ->assertSee('mailto:futureacademy-rm@gmail.com', false)
        ->assertSee('mailto:noreply@futureacademy-rm.com', false);
});

it('homepage displays pricing plans', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Monthly Plan')
        ->assertSee('₦2,000')
        ->assertSee('Yearly Plan')
        ->assertSee('₦18,000');
});

it('homepage uses practice questions terminology instead of past questions', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Practice Questions')
        ->assertDontSee('Past Questions');
});

it('homepage uses video lectures terminology instead of low-data', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('Video lectures')
        ->assertDontSee('Low-data')
        ->assertDontSee('low-data');
});
