<?php

it('returns a successful response', function () {
    $this->get('/')->assertStatus(200);
});

it('healthz endpoint returns 200', function () {
    $this->get('/healthz')->assertStatus(200);
});
