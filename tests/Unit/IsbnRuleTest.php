<?php

use App\Rules\Isbn;
use Illuminate\Support\Facades\Validator;

function validateIsbn(string $value): bool
{
    return Validator::make(['isbn' => $value], ['isbn' => ['required', new Isbn]])->passes();
}

describe('ISBN-13 validation', function () {
    it('accepts a valid isbn-13 without hyphens', function () {
        expect(validateIsbn('9783161484100'))->toBeTrue();
    });

    it('accepts a valid isbn-13 with hyphens', function () {
        expect(validateIsbn('978-3-16-148410-0'))->toBeTrue();
    });

    it('accepts a valid isbn-13 starting with 979', function () {
        expect(validateIsbn('9791034344109'))->toBeTrue();
    });

    it('rejects isbn-13 with invalid checksum', function () {
        expect(validateIsbn('9783161484109'))->toBeFalse();
    });

    it('rejects isbn-13 not starting with 978 or 979', function () {
        expect(validateIsbn('9773161484100'))->toBeFalse();
    });

    it('rejects isbn-13 with letters', function () {
        expect(validateIsbn('978316148410A'))->toBeFalse();
    });
});

describe('ISBN-10 validation', function () {
    it('accepts a valid isbn-10 without hyphens', function () {
        expect(validateIsbn('0306406152'))->toBeTrue();
    });

    it('accepts a valid isbn-10 with hyphens', function () {
        expect(validateIsbn('0-306-40615-2'))->toBeTrue();
    });

    it('accepts a valid isbn-10 ending with X', function () {
        expect(validateIsbn('080442957X'))->toBeTrue();
    });

    it('accepts a valid isbn-10 ending with lowercase x', function () {
        expect(validateIsbn('080442957x'))->toBeTrue();
    });

    it('rejects isbn-10 with invalid checksum', function () {
        expect(validateIsbn('0306406153'))->toBeFalse();
    });

    it('rejects isbn-10 with X not in last position', function () {
        expect(validateIsbn('030640X152'))->toBeFalse();
    });
});

describe('invalid formats', function () {
    it('rejects empty string', function () {
        expect(validateIsbn(''))->toBeFalse();
    });

    it('rejects too short string', function () {
        expect(validateIsbn('12345'))->toBeFalse();
    });

    it('rejects too long string', function () {
        expect(validateIsbn('97831614841001234'))->toBeFalse();
    });

    it('rejects non-numeric string', function () {
        expect(validateIsbn('abcdefghij'))->toBeFalse();
    });

    it('rejects 11-digit string', function () {
        expect(validateIsbn('12345678901'))->toBeFalse();
    });
});
