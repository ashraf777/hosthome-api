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

class SendPushNotification implements ShouldQueue
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
     */
    public function handle(): void
    {
        $users = User::whereIn('id', $this->userIds)
            ->whereNotNull('fcm_token')
            ->get(['id', 'fcm_token']);

        // Create in-app notifications
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

        if ($users->isEmpty()) {
            return;
        }

        $tokens = $users->pluck('fcm_token')->toArray();

        try {
            $accessToken = \App\Services\FirebaseAccessTokenService::getAccessToken();
            
            // Extract Project ID from service account JSON
            $filePath = storage_path('app/firebase-service-account.json');
            if (!file_exists($filePath)) {
                Log::error('FCM HTTP v1: Service account file not found.');
                return;
            }
            $serviceAccount = json_decode(file_get_contents($filePath), true);
            $projectId = $serviceAccount['project_id'] ?? null;

            if (!$projectId) {
                Log::error('FCM HTTP v1: Project ID missing in service account.');
                return;
            }

            foreach ($tokens as $token) {
                $response = Http::withToken($accessToken)
                    ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $this->title,
                                'body'  => $this->body,
                            ],
                            'data' => [
                                'type'         => $this->type,
                                'reference_id' => (string) ($this->referenceId ?? ''),
                                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            ],
                        ]
                    ]);

                if (!$response->successful()) {
                    Log::warning('FCM HTTP v1 push notification failed', [
                        'status'   => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('FCM push notification exception: ' . $e->getMessage());
        }
    }
}
