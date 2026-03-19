<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $adminUserId = $request->query('userId');
        $search = (string) $request->query('search', '');

        $query = Contact::query();

        if ($user->is_admin && $adminUserId) {
            $query->where('user_id', (int) $adminUserId);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $contacts = $query->orderByDesc('id')->get()->map(function (Contact $contact) {
            return [
                '_id' => (string) $contact->id,
                'userId' => (string) $contact->user_id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'notes' => $contact->notes ?? '',
                'createdAt' => $contact->created_at?->toIso8601String(),
                'updatedAt' => $contact->updated_at?->toIso8601String(),
                'sentimentScore' => $contact->sentiment_score,
                'sentimentLabel' => $contact->sentiment_label,
                'sentimentAnalyzedAt' => $contact->sentiment_analyzed_at,
                'sentimentMessageCount' => $contact->sentiment_message_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $contacts,
        ]);
    }

    public function show(Request $request, Contact $contact): JsonResponse
    {
        $this->authorizeContact($request, $contact);

        $identities = $contact->identities()
            ->with('channel')
            ->get()
            ->map(function ($identity) {
                $channel = $identity->channel;

                return [
                    '_id' => (string) $identity->id,
                    'contactId' => (string) $identity->contact_id,
                    'channelId' => $channel ? [
                        '_id' => (string) $channel->id,
                        'name' => $channel->name,
                        'platform' => $channel->platform,
                    ] : (string) $identity->channel_id,
                    'platformUserId' => $identity->platform_user_id,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'contact' => [
                    '_id' => (string) $contact->id,
                    'userId' => (string) $contact->user_id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'phone' => $contact->phone,
                    'notes' => $contact->notes ?? '',
                    'createdAt' => $contact->created_at?->toIso8601String(),
                    'updatedAt' => $contact->updated_at?->toIso8601String(),
                    'sentimentScore' => $contact->sentiment_score,
                    'sentimentLabel' => $contact->sentiment_label,
                    'sentimentAnalyzedAt' => $contact->sentiment_analyzed_at,
                    'sentimentMessageCount' => $contact->sentiment_message_count,
                ],
                'identities' => $identities,
                'conversations' => [],
            ],
        ]);
    }

    public function update(Request $request, Contact $contact): JsonResponse
    {
        $this->authorizeContact($request, $contact);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $contact->fill($validated);
        $contact->save();

        return response()->json([
            'success' => true,
            'data' => [
                '_id' => (string) $contact->id,
                'userId' => (string) $contact->user_id,
                'name' => $contact->name,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'notes' => $contact->notes ?? '',
                'createdAt' => $contact->created_at?->toIso8601String(),
                'updatedAt' => $contact->updated_at?->toIso8601String(),
                'sentimentScore' => $contact->sentiment_score,
                'sentimentLabel' => $contact->sentiment_label,
                'sentimentAnalyzedAt' => $contact->sentiment_analyzed_at,
                'sentimentMessageCount' => $contact->sentiment_message_count,
            ],
        ]);
    }

    protected function authorizeContact(Request $request, Contact $contact): void
    {
        $user = $request->user();
        if (!$user->is_admin && $contact->user_id !== $user->id) {
            abort(403);
        }
    }
}

