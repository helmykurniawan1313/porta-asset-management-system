<?php

namespace App\BrandHealth\Models;

use Illuminate\Database\Eloquent\Model;

class BrandLegalPage extends Model
{
    protected $table = 'brand_legal_pages';

    protected $fillable = [
        'slug',
        'title',
        'content_html',
    ];

    public const PRIVACY_POLICY = 'privacy-policy';

    public const DATA_DELETION = 'data-deletion';
}
