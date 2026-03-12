<?php

it('healthz endpoint returns 200', function () {
    $this->get('/healthz')->assertStatus(200);
});
