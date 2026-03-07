<?php

namespace Services\Users\Objects;

final class User {
    public int $id = -1;
    public string $email = '';

    public function set(int $id, string $email): void {
        $this->id = $id;
        $this->email = $email;
    }

    public function reset(): void {
        $this->id = -1;
        $this->email = '';
    }

    public function isAuth(): bool {
        return $this->id > 0;
    }
}
