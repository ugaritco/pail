<?php

use Heritage\Support\Facades\Context;

test('debug messages', function () {
    expect('app("log")->debug("my debug message")')->toPail(<<<'EOF'
        ┌ 03:04:05 DEBUG ────────────────────────────────┐
        │ my debug message                               │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('info messages', function () {
    expect('app("log")->info("my info message")')->toPail(<<<'EOF'
        ┌ 03:04:05 INFO ─────────────────────────────────┐
        │ my info message                                │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('notice messages', function () {
    expect('app("log")->notice("my notice message")')->toPail(<<<'EOF'
        ┌ 03:04:05 NOTICE ───────────────────────────────┐
        │ my notice message                              │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('warning messages', function () {
    expect('app("log")->warning("my warning message")')->toPail(<<<'EOF'
        ┌ 03:04:05 WARNING ──────────────────────────────┐
        │ my warning message                             │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('error messages', function () {
    expect('app("log")->error("my error message")')->toPail(<<<'EOF'
        ┌ 03:04:05 ERROR ────────────────────────────────┐
        │ my error message                               │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('critical messages', function () {
    expect('app("log")->critical("my critical message")')->toPail(<<<'EOF'
        ┌ 03:04:05 CRITICAL ─────────────────────────────┐
        │ my critical message                            │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('alert messages', function () {
    expect('app("log")->alert("my alert message")')->toPail(<<<'EOF'
        ┌ 03:04:05 ALERT ────────────────────────────────┐
        │ my alert message                               │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('emergency messages', function () {
    expect('app("log")->emergency("my emergency message")')->toPail(<<<'EOF'
        ┌ 03:04:05 EMERGENCY ────────────────────────────┐
        │ my emergency message                           │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('multiple messages', function () {
    expect([
        'app("log")->debug("my debug message")',
        'app("log")->info("my info message")',
        'app("log")->notice("my notice message")',
    ])->toPail(<<<'EOF'
        ┌ 03:04:05 DEBUG ────────────────────────────────┐
        │ my debug message                               │
        └────────────────────────────────── scribe eval ┘
        ┌ 03:04:05 INFO ─────────────────────────────────┐
        │ my info message                                │
        └────────────────────────────────── scribe eval ┘
        ┌ 03:04:05 NOTICE ───────────────────────────────┐
        │ my notice message                              │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('exceptions', function () {
    expect('throw new Exception("my exception message")')->toPail(<<<'EOF'
        ┌ 03:04:05 Exception ──────── app/MyClass.php:12 ┐
        │ my exception message                           │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('runtime exceptions', function () {
    expect('throw new RuntimeException("my runtime exception message")')->toPail(<<<'EOF'
        ┌ 03:04:05 RuntimeException ─ app/MyClass.php:12 ┐
        │ my runtime exception message                   │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('reported strings', function () {
    expect('report("my reported string")')->toPail(<<<'EOF'
        ┌ 03:04:05 Exception ──────── app/MyClass.php:12 ┐
        │ my reported string                             │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('multiple exceptions and messages', function () {
    expect([
        'throw new RuntimeException("my runtime exception message")',
        'app("log")->critical("my critical message")',
        'throw new Exception("my exception message")',
    ])->toPail(<<<'EOF'
        ┌ 03:04:05 RuntimeException ─ app/MyClass.php:12 ┐
        │ my runtime exception message                   │
        └────────────────────────────────── scribe eval ┘
        ┌ 03:04:05 CRITICAL ─────────────────────────────┐
        │ my critical message                            │
        └────────────────────────────────── scribe eval ┘
        ┌ 03:04:05 Exception ──────── app/MyClass.php:12 ┐
        │ my exception message                           │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});

test('multiple exceptions and messages and verbose', function () {
    expect([
        'throw new RuntimeException("my runtime exception message")',
        'app("log")->critical("my critical message")',
        'throw new Exception("my exception message")',
    ])->toPail(<<<'EOF'
        ┌ 2024-01-01 03:04:05 RuntimeException  app/MyClass.php:12
        │ my runtime exception message
        │ 1. app/MyClass.php:12
        │ 2. app/MyClass.php:34
        └─────────────────────────────────── scribe eval
        ┌ 2024-01-01 03:04:05 CRITICAL ───────────────────
        │ my critical message
        │ 1. app/MyClass.php:12
        │ 2. app/MyClass.php:34
        └─────────────────────────────────── scribe eval
        ┌ 2024-01-01 03:04:05 Exception  app/MyClass.php:12
        │ my exception message
        │ 1. app/MyClass.php:12
        │ 2. app/MyClass.php:34
        └─────────────────────────────────── scribe eval

        EOF, verbose: true);
});

test('exception key as string', function () {
    expect([
        'Log::error("log message", ["exception" => "an exception occured"])',
        'throw new Exception("my exception message")',
    ])->toPail(<<<'EOF'
        ┌ 2024-01-01 03:04:05 ERROR ──────────────────────
        │ log message
        │ 1. app/MyClass.php:12
        │ 2. app/MyClass.php:34
        └─────────────────────────────────── scribe eval
        ┌ 2024-01-01 03:04:05 Exception  app/MyClass.php:12
        │ my exception message
        │ 1. app/MyClass.php:12
        │ 2. app/MyClass.php:34
        └─────────────────────────────────── scribe eval

        EOF, verbose: true);
});

test('using context facade', function () {
    expect('Context::add("user_id", 1); Context::push("breadcrumbs", "first_value"); Log::error("log message", ["exception" => "an exception occured"])')->toPail(<<<'EOF'
        ┌ 03:04:05 ERROR ────────────────────────────────┐
        │ log message                                    │
        └ scribe eval • user_id: 1 • breadcrumbs: array ( 0 => 'first_value', ) ┘

        EOF,
    );
})->skip(! class_exists(Context::class), 'Context facade is not available in this version of Ugarit');

test('malformed logs', function () {
    expect([
        'file_put_contents(collect(glob(storage_path("pail/*.pail")))->last(), "invalid json\n", FILE_APPEND)',
        'app("log")->debug("test debug message")',
    ])->toPail(<<<'EOF'
          ⚠ Pail skipped a malformed log line.
        ┌ 03:04:05 DEBUG ────────────────────────────────┐
        │ test debug message                             │
        └────────────────────────────────── scribe eval ┘

        EOF,
    );
});
