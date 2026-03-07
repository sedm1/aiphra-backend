<?php

namespace Utils;

final class Core {
    private ?Mail $mailService = null;

    public function mail(): Mail {
        if ($this->mailService === null) {
            $this->mailService = new Mail();
        }

        return $this->mailService;
    }
}
