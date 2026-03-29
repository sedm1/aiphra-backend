<?php

namespace Models;

final class User extends AbstractModel {
    public const string T = 'aiphra.users';

    public const array AVAILABLE_FIELD_NAMES = [
        'id',
        'email',
    ];
}
