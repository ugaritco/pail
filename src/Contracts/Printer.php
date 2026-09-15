<?php

namespace Ugarit\Pail\Contracts;

use Ugarit\Pail\ValueObjects\MessageLogged;

interface Printer
{
    /**
     * Prints the given message logged.
     */
    public function print(MessageLogged $messageLogged): void;
}
