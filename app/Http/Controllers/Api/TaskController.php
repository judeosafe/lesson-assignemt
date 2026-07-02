<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private const VALID_STATUSES = ['todo', 'in_progress', 'done'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:all,todo,in_progress,done',
            'search' => 'nullable|string|max:255',
        ]);

        $tasks = $request->user()
            ->tasks()
            ->when(
                ($validated['status'] ?? 'all') !== 'all',
                fn ($query) => $query->where('status', $validated['status'])
            )
            ->when(
                filled($validated['search'] ?? null),
                fn ($query) => $query->where('name', 'like', '%'.$validated['search'].'%')
            )
            ->latest()
            ->get();

        return response()->json(
            $tasks
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:'.implode(',', self::VALID_STATUSES),
            'priority' => 'required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task = $request->user()->tasks()->create($validated);

        return response()->json($task, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Task $task)
    {
        $task->load('comments.user');

        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:'.implode(',', self::VALID_STATUSES),
            'priority' => 'sometimes|required|in:low,medium,high',
            'due_date' => 'nullable|date',
        ]);

        $task->update($validated);

        return response()->json($task);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
    {
        $this->ensureTaskOwner($request, $task);

        $task->delete();

        return response()->json(null, 204);
    }

    private function ensureTaskOwner(Request $request, Task $task): void
    {
        if ($task->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
