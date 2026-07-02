<?php

namespace App\Events;

use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Comment $comment)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tasks.'.$this->comment->task_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    public function broadcastWith(): array
    {
        $this->comment->loadMissing('user', 'task');

        return [
            'comment' => [
                'id' => $this->comment->id,
                'task_id' => $this->comment->task_id,
                'user_id' => $this->comment->user_id,
                'body' => $this->comment->body,
                'created_at' => $this->comment->created_at?->toJSON(),
                'updated_at' => $this->comment->updated_at?->toJSON(),
                'user' => [
                    'id' => $this->comment->user?->id,
                    'name' => $this->comment->user?->name,
                ],
            ],
            'message' => sprintf(
                '%s commented on "%s".',
                $this->comment->user?->name ?? 'Someone',
                $this->comment->task?->name ?? 'a task'
            ),
        ];
    }
}
