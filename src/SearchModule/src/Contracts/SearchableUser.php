<?php

namespace Periscope\SearchModule\Contracts;

use Carbon\Carbon;

interface SearchableUser
{
    public function getId(): int;
    public function getName(): string;
    public function getUsername(): string;
    public function getPhoneVerifiedAt(): ?Carbon;
}
