<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForumThreadRequest;
use App\Models\ForumThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ForumThreadController extends Controller
{
    public function index(): JsonResponse
    {
        $threads = ForumThread::with(['user', 'category'])
            ->where('status', 'active')
            ->latest()
            ->get();

        return response()->json(['data' => $threads]);
    }

    public function store(StoreForumThreadRequest $request): JsonResponse
    {
        $thread = ForumThread::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Diskusi berhasil dibuat.',
            'data' => $thread->load(['user', 'category']),
        ], 201);
    }

    public function show(ForumThread $forumThread): JsonResponse
    {
        return response()->json([
            'data' => $forumThread->load(['user', 'category']),
        ]);
    }

    public function update(Request $request, ForumThread $forumThread): JsonResponse
    {
        abort_unless(
            $request->user() && $request->user()->id === $forumThread->user_id,
            403,
            'Anda tidak berhak mengubah diskusi ini.'
        );

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
        ]);

        $forumThread->update($validated);

        return response()->json([
            'message' => 'Diskusi berhasil diperbarui.',
            'data' => $forumThread->fresh()->load(['user', 'category']),
        ]);
    }

    public function destroy(Request $request, ForumThread $forumThread): JsonResponse
    {
        abort_unless(
            $request->user() && $request->user()->id === $forumThread->user_id,
            403,
            'Anda tidak berhak menghapus diskusi ini.'
        );

        $forumThread->delete();

        return response()->json([
            'message' => 'Diskusi berhasil dihapus.',
        ]);
    }

    public function updateStatus(Request $request, ForumThread $forumThread): JsonResponse
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403,
            'Hanya admin yang dapat memoderasi diskusi.'
        );

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['active', 'hidden', 'closed']),
            ],
        ]);

        $forumThread->update($validated);

        return response()->json([
            'message' => 'Status diskusi berhasil diperbarui.',
            'data' => $forumThread->fresh()->load(['user', 'category']),
        ]);
    }
}