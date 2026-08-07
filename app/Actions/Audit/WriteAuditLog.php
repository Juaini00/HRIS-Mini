<?php

namespace App\Actions\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WriteAuditLog
{
    private const SENSITIVE_KEYS = ['password', 'bank_account', 'basic_salary', 'amount', 'two_factor_secret', 'two_factor_recovery_codes', 'token', 'secret'];

    public function handle(Request $request, string $event, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $request->user()?->id,
            'event' => $event,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request->ip(),
        ]);
    }

    private function sanitize(array $metadata): array
    {
        foreach ($metadata as $key => $value) {
            if (in_array($key, self::SENSITIVE_KEYS, true)) {
                $metadata[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $metadata[$key] = $this->sanitize($value);
            }
        }

        return $metadata;
    }
}
