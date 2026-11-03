<?php

namespace Wereldkundig\GravityForms\Tests\Unit\Doubles;

class WPError {
    private string|int $code;

    private string $message;

    private mixed $data;

    public function __construct(string|int $code = '', string $message = '', mixed $data = '') {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    public function get_error_code(): string|int {
        return $this->code;
    }

    public function get_error_message(): string {
        return $this->message;
    }

    public function get_error_data(): mixed {
        return $this->data;
    }
}
