<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';

    public function findByPhone(string $phone): ?array
    {
        return $this->findBy('phone', $phone);
    }
}
