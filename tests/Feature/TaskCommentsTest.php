<?php

namespace Tests\Feature;

use App\Events\CommentCreated;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskCommentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_authenticated_user_can_add_a_comment_to_a_task(): void
    {
        Event::fake([CommentCreated::class]);

        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'name' => 'Refine onboarding copy',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs($commenter);

        $response = $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => 'I can take this one after lunch.',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('body', 'I can take this one after lunch.');
        $response->assertJsonPath('user.id', $commenter->id);
        $response->assertJsonPath('user.name', $commenter->name);

        $this->assertDatabaseHas('comments', [
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'I can take this one after lunch.',
        ]);

        Event::assertDispatched(CommentCreated::class);
    }

    public function test_comment_body_is_required_and_capped(): void
    {
        $owner = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'name' => 'Audit notification copy',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $blankResponse = $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => '   ',
        ]);

        $blankResponse->assertUnprocessable();
        $blankResponse->assertJsonValidationErrors(['body']);

        $response = $this->postJson("/api/tasks/{$task->id}/comments", [
            'body' => str_repeat('a', 1001),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['body']);
    }

    public function test_task_detail_includes_comments_with_their_authors(): void
    {
        $owner = User::factory()->create();
        $commenter = User::factory()->create();
        $task = Task::create([
            'user_id' => $owner->id,
            'name' => 'Review QA feedback',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $comment = Comment::create([
            'task_id' => $task->id,
            'user_id' => $commenter->id,
            'body' => 'This looks good from my side.',
        ]);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertOk();
        $response->assertJsonPath('comments.0.id', $comment->id);
        $response->assertJsonPath('comments.0.user.id', $commenter->id);
        $response->assertJsonPath('comments.0.user.name', $commenter->name);
    }
}
