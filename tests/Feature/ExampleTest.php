<?php

test('unauthenticated users are redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});
