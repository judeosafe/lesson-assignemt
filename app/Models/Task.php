<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class)->latest();
    }

    protected static function booted(): void
    {
        static::created(function (Task $task) {
            $task->activities()->create([
                'user_id' => auth()->id() ?? $task->user_id,
                'event' => 'created',
                'description' => 'created the task',
            ]);
        });

        static::updating(function (Task $task) {
            $changes = [];

            if ($task->isDirty('status')) {
                $statusMap = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'done' => 'Done'];
                $newStatus = $statusMap[$task->status] ?? $task->status;
                $changes[] = "status to \"{$newStatus}\"";
            }
            if ($task->isDirty('priority')) {
                $changes[] = "priority to \"{$task->priority}\"";
            }
            if ($task->isDirty('name')) {
                $changes[] = "name to \"{$task->name}\"";
            }
            if ($task->isDirty('description')) {
                $changes[] = 'description';
            }
            if ($task->isDirty('due_date')) {
                $formattedDate = $task->due_date ? $task->due_date->format('Y-m-d') : 'none';
                $changes[] = "due date to \"{$formattedDate}\"";
            }

            if (!empty($changes)) {
                $description = 'changed ' . implode(', ', $changes);
                $task->activities()->create([
                    'user_id' => auth()->id() ?? $task->user_id,
                    'event' => 'updated',
                    'description' => $description,
                ]);
            }
        });

        static::deleting(function (Task $task) {
            $task->activities()->create([
                'user_id' => auth()->id() ?? $task->user_id,
                'event' => 'deleted',
                'description' => 'deleted the task',
            ]);
        });
    }
}
