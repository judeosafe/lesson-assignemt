<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_logs_activity_when_task_is_created(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'name' => 'Complete documentation review',
            'status' => 'todo',
            'priority' => 'high',
        ]);

        $response->assertCreated();
        $taskId = $response->json('id');

        $this->assertDatabaseHas('activities', [
            'task_id' => $taskId,
            'user_id' => $user->id,
            'event' => 'created',
            'description' => 'created the task',
        ]);
    }

    public function test_it_logs_activity_when_task_details_are_updated(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::create([
            'user_id' => $user->id,
            'name' => 'Draft roadmap',
            'status' => 'todo',
            'priority' => 'low',
        ]);

        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('activities', [
            'task_id' => $task->id,
            'user_id' => $user->id,
            'event' => 'updated',
            'description' => 'changed status to "In Progress", priority to "high"',
        ]);
    }

    public function test_it_logs_activity_when_comment_is_added(): void
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'name' => 'Refine onboarding flow',
            'status' => 'in_progress',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($commenter);

        $response = $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'I have updated the design prototype.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activities', [
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'event' => 'comment_added',
            'description' => 'added a comment',
        ]);
    }

    public function test_task_details_response_includes_activities_with_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $task = Task::create([
            'user_id' => $user->id,
            'name' => 'Audit codebase',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'activities' => [
                '*' => [
                    'id',
                    'task_id',
                    'user_id',
                    'event',
                    'description',
                    'created_at',
                    'user' => [
                        'id',
                        'name',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('activities.0.event', 'created');
        $response->assertJsonPath('activities.0.user.id', $user->id);
        $response->assertJsonPath('activities.0.user.name', $user->name);
    }

    public function test_non_owner_cannot_view_task_details(): void
    {
        $owner = User::factory()->create();
        $nonOwner = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'name' => 'Private Task',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($nonOwner);

        $response = $this->getJson("/api/tasks/{$task->id}");
        $response->assertForbidden();
    }
}
