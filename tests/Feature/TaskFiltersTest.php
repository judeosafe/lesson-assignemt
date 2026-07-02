<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_tasks_by_status_and_search_term(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Sanctum::actingAs($user);

        Task::create([
            'user_id' => $user->id,
            'name' => 'Prepare sprint brief',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $matchingTask = Task::create([
            'user_id' => $user->id,
            'name' => 'Prepare release notes',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        Task::create([
            'user_id' => $user->id,
            'name' => 'Archive release notes',
            'status' => 'done',
            'priority' => 'low',
        ]);

        Task::create([
            'user_id' => $otherUser->id,
            'name' => 'Prepare release notes',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $response = $this->getJson('/api/tasks?status=in_progress&search=release');

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $matchingTask->id);
        $response->assertJsonPath('0.name', $matchingTask->name);
    }

    public function test_it_rejects_invalid_status_filters(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks?status=blocked');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }
}
