<?php

namespace Controller;

abstract class AbstractController {
    protected Page $page;

    public function __construct(Page $page) {
        $this->page = $page;
    }

    abstract public function init(): void;
}
