<?php

namespace Controller;

final class E404 extends AbstractController {
    public function init(): never {
        throw new \Exception('Not found', ERROR_CODE_NOT_FOUND);
    }
}
