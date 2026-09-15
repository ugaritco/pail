<?php

use Heritage\Support\Facades\Scribe;

Scribe::command('eval {code}', function () {
    eval(base64_decode($this->argument('code')));
});
