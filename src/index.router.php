<?php

use Controller\Page;

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$page = new Page($uri);
$page->setParams();
$page->setController();
