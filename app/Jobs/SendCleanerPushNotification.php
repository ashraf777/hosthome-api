<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCleanerPushNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly array $userIds,
        public readonly string $title,
        public readonly string $body,
        public readonly string $type = 'general',
        public readonly ?int $referenceId = null,
    ) {}

    /**
     * Execute the job.
     * 1. Creates in-app notification records for all users.
     * 2. Sends FCM push notifications to users who have a registered fcm_token.
     */
    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)
            ->whereNotNull('fcm_token')
            ->get(['id', 'fcm_token']);

        // Create in-app notification records for ALL users (even without FCM token)
        foreach ($this->userIds as $userId) {
            UserNotification::create([
                'user_id'      => $userId,
                'title'        => $this->title,
                'body'         => $this->body,
                'type'         => $this->type,
                'reference_id' => $this->referenceId,
                'is_read'      => false,
            ]);
        }

        // Send FCM push to users who have a token
        $fcmServerKey = config('services.fcm.server_key');
        if (!$fcmServerKey || $users->isEmpty()) {
            return;
        }

        $tokens = $users->pluck('fcm_token')->toArray();

        try {
            $response = Http::withHeaders([
                'Authorization' => "key={$fcmServerKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => $tokens,
                'notification'     => [
                    'title' => $this->title,
                    'body'  => $this->body,
                    'sound' => 'default',
                ],
                'data' => [
                    'type'         => $this->type,
                    'reference_id' => (string) ($this->referenceId ?? ''),
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ]);

            if (!$response->successful()) {
                Log::warning('FCM push notification failed', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM push notification exception: ' . $e->getMessage());
        }
    }
}
