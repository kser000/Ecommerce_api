<?php

namespace App\Payment;

/**
 * 外部金流回傳結果。
 * reference 為金流端的交易序號（對帳用）。
 */
readonly class ChargeResult
{
    public function __construct(
        public bool $success,
        public ?string $reference,
        public string $message,
    ) {}

    public static function success(string $reference): self
    {
        return new self(true, $reference, 'OK');
    }

    public static function failure(string $message): self
    {
        return new self(false, null, $message);
    }
}
