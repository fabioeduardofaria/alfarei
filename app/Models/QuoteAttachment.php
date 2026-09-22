<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAttachment extends Model
{
    protected $fillable = ['quote_id', 'uploaded_by', 'original_name', 'path', 'mime_type', 'size', 'category', 'version', 'approved'];

    protected function casts(): array
    {
        return ['approved' => 'boolean'];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
