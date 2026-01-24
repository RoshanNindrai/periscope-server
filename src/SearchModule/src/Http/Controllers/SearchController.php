<?php

namespace Periscope\SearchModule\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Periscope\SearchModule\Enums\SearchResponseState;
use Periscope\SearchModule\Enums\SearchErrorCode;
use Throwable;

class SearchController extends Controller
{
    protected function getUserModel(): string
    {
        return config('search-module.user_model', \App\Models\User::class);
    }

    protected function getMinSearchLength(): int
    {
        return config('search-module.min_search_length', 2);
    }

    protected function getResultsPerPage(): int
    {
        return config('search-module.results_per_page', 15);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $userModel = $this->getUserModel();
        $minLength = $this->getMinSearchLength();
        $perPage = $this->getResultsPerPage();

        try {
            $validated = $request->validate([
                'q' => "required|string|min:{$minLength}|max:100",
            ]);
        } catch (ValidationException $e) {
            $error = SearchErrorCode::VALIDATION_ERROR;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
                'errors' => $e->errors(),
            ], $error->statusCode());
        }

        try {
            $searchTerm = trim($validated['q']);
            $normalizedTerm = strtolower($searchTerm);

            // Exact username match
            $exactMatch = $userModel::where('username', $normalizedTerm)
                ->select('id', 'name', 'username', 'phone_verified_at')
                ->first();

            if ($exactMatch) {
                return response()->json([
                    'status' => SearchResponseState::USERS_FOUND->value,
                    'message' => SearchResponseState::USERS_FOUND->message(),
                    'data' => [$exactMatch],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 1,
                        'last_page' => 1,
                    ],
                ], 200);
            }

            // Username prefix + Name prefix search
            $results = $userModel::query()
                ->select('id', 'name', 'username', 'phone_verified_at')
                ->where(function ($query) use ($normalizedTerm, $searchTerm) {
                    $query->where('username', 'like', $normalizedTerm . '%')
                          ->orWhere('name', 'like', $searchTerm . '%');
                })
                ->orderByRaw("
                    CASE 
                        WHEN username LIKE ? THEN 1
                        WHEN name LIKE ? THEN 2
                        ELSE 3
                    END
                ", [$normalizedTerm . '%', $searchTerm . '%'])
                ->orderBy('username')
                ->paginate($perPage);

            if ($results->isEmpty()) {
                $state = SearchResponseState::NO_RESULTS_FOUND;
                return response()->json([
                    'status' => $state->value,
                    'message' => $state->message(),
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                        'last_page' => 1,
                    ],
                ], 200);
            }

            $state = SearchResponseState::USERS_FOUND;
            return response()->json([
                'status' => $state->value,
                'message' => $state->message(),
                'data' => $results->items(),
                'meta' => [
                    'current_page' => $results->currentPage(),
                    'per_page' => $results->perPage(),
                    'total' => $results->total(),
                    'last_page' => $results->lastPage(),
                ],
            ], 200);

        } catch (Throwable $e) {
            Log::error('User search failed', [
                'search_term_length' => isset($searchTerm) ? strlen($searchTerm) : 0,
                'error' => $e->getMessage(),
            ]);

            $error = SearchErrorCode::SEARCH_FAILED;
            return response()->json([
                'status' => $error->value,
                'error' => $error->value,
                'message' => $error->message(),
            ], $error->statusCode());
        }
    }
}
