<?php

namespace App\Services\Contacts;

use App\Models\Channel;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class PlatformSenderContactLinker
{
    /**
     * Ensure a CRM contact + identity exist for this channel and platform user, and attach to the conversation.
     */
    public function link(
        Conversation $conversation,
        Channel $channel,
        string $platformUserId,
        ?string $displayName = null,
        ?string $phone = null,
    ): void {
        if ($channel->user_id === null) {
            return;
        }

        $displayName = $displayName !== null ? trim($displayName) : null;
        if ($displayName === '') {
            $displayName = null;
        }
        $phone = $phone !== null ? trim($phone) : null;
        if ($phone === '') {
            $phone = null;
        }

        DB::transaction(function () use ($conversation, $channel, $platformUserId, $displayName, $phone): void {
            $identity = ContactIdentity::query()
                ->where('channel_id', $channel->id)
                ->where('platform_user_id', $platformUserId)
                ->lockForUpdate()
                ->first();

            if ($identity === null) {
                $contact = Contact::query()->create([
                    'user_id' => $channel->user_id,
                    'name' => $displayName,
                    'phone' => $phone,
                    'email' => null,
                    'notes' => null,
                ]);
                $identity = ContactIdentity::query()->create([
                    'contact_id' => $contact->id,
                    'channel_id' => $channel->id,
                    'platform_user_id' => $platformUserId,
                ]);
            } else {
                /** @var Contact|null $contact */
                $contact = $identity->contact()->lockForUpdate()->first();
                if ($contact instanceof Contact) {
                    $dirty = false;
                    if ($displayName !== null && blank($contact->name)) {
                        $contact->name = $displayName;
                        $dirty = true;
                    }
                    if ($phone !== null && blank($contact->phone)) {
                        $contact->phone = $phone;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $contact->save();
                    }
                }
            }

            if ((int) $conversation->contact_id !== (int) $identity->contact_id) {
                $conversation->forceFill(['contact_id' => $identity->contact_id])->save();
            }
        });
    }
}
