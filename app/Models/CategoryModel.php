<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table            = 'categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;
    protected $useTimestamps          = true;
    protected $dateFormat             = 'datetime';
    protected $createdField           = 'created_at';
    protected $updatedField           = 'updated_at';

    protected array $casts = [
        'id'         => 'integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];
}
