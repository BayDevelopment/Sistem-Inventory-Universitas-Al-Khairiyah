<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Procurement extends Model
{
    use HasFactory;

    /**
     * Procurement status.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_COMPLETED = 'completed';

    /**
     * Procurement type.
     */
    public const TYPE_REPLACEMENT = 'replacement';

    public const TYPE_NEW_ITEM = 'new_item';

    protected $fillable = [
        'faculty_id',
        'requested_by',
        'room_id',
        'item_name',
        'quantity',
        'type',
        'reason',
        'subject',
        'attachments',
        'requester_signature',
        'requested_at',
        'status',
        'document_number',
        'processed_by',
        'approver_signature',
        'processed_at',
        'admin_note',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'attachments' => 'array',
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Faculty pemilik pengajuan pengadaan.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * User yang membuat pengajuan.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    /**
     * User yang memproses pengajuan.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'processed_by'
        );
    }

    /**
     * Ruangan tujuan pengadaan.
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Scope berdasarkan status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope berdasarkan fakultas.
     */
    public function scopeFaculty($query, int $facultyId)
    {
        return $query->where('faculty_id', $facultyId);
    }

    /**
     * Check pending status.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check approved status.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check rejected status.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check completed status.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}