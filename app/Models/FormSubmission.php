<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'form_type',
    'name',
    'email',
    'phone',
    'company',
    'country',
    'subject',
    'message',
    'payload',
    'status',
    'admin_notes',
    'submitted_at',
])]
class FormSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\FormSubmissionFactory> */
    use HasFactory;

    public const TYPE_CONTACT = 'contact';

    public const TYPE_INVESTOR = 'investor';

    public const TYPE_MORTGAGE = 'mortgage';

    public const TYPES = [
        self::TYPE_CONTACT,
        self::TYPE_INVESTOR,
        self::TYPE_MORTGAGE,
    ];

    public const STATUS_NEW = 'new';

    public const STATUS_READ = 'read';

    public const STATUS_REPLIED = 'replied';

    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_READ,
        self::STATUS_REPLIED,
        self::STATUS_ARCHIVED,
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeFormType(Builder $query, string $type): Builder
    {
        return $query->where('form_type', $type);
    }
}
