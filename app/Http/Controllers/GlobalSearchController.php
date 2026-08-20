<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController
{
    public function __invoke(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'workspace' => ['required', 'string', 'in:platform,verification,clinic,organization,dso'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return response()->json($search->search($user, $validated['workspace'], $validated['q'] ?? null));
    }
}
