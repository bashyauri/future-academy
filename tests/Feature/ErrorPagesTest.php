<?php

test('404 error page is displayed', function () {
    $response = $this->get('/non-existent-page-xyz-123');

    $response->assertStatus(404)
        ->assertSee('Page not found')
        ->assertSee('exist');
});

test('API returns user-friendly error messages for 404', function () {
    $response = $this->getJson('/api/v1/non-existent-endpoint');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'The requested URL was not found.',
        ]);
});

test('API returns user-friendly error messages for 401', function () {
    $response = $this->getJson('/api/v1/user');

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'You need to be logged in to access this resource.',
        ]);
});
