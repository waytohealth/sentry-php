<?php

declare(strict_types=1);

namespace Sentry\Serializer\EnvelopItems;

use Sentry\Event;
use Sentry\Serializer\Traits\BreadcrumbSeralizerTrait;
use Sentry\Tracing\Span;
use Sentry\Tracing\TransactionMetadata;
use Sentry\Util\JSON;

/**
 * @internal
 *
 * @phpstan-type MetricsSummary array{
 *     min: int|float,
 *     max: int|float,
 *     sum: int|float,
 *     count: int,
 *     tags: array<string>,
 * }
 */
class SpanItem implements EnvelopeItemInterface
{
    use BreadcrumbSeralizerTrait;

    public static function toEnvelopeItem(Event $event): string
    {
        $header = [
            'type' => (string) $event->getType(),
            'content_type' => 'application/json',
        ];

        $payload = [
            'platform' => 'php', // -> attr.sentry.platform
            'sdk' => [
                'name' => $event->getSdkIdentifier(), // -> attr.sentry.sdk.name
                'version' => $event->getSdkVersion(), // -> attr.sentry.sdk.version
            ],
        ];

        $span = $event->getSpan();

        $payload['start_timestamp'] = $span->startTimestamp; // -> end_time_unix_nano
        $payload['timestamp'] = $span->endTimestamp; // -> end_time_unix_nano
        
        $payload['exclusive_time'] = $span->exclusiveTime; // TBD maybe not

        $payload['trace_id'] = (string) $span->traceId; // trace_id
        $payload['segment_id'] = (string) $span->segmentId; // attr.sentry.segment_id
        $payload['span_id'] = (string) $span->spanId; // span_id

        $payload['is_segment'] = $span->isSegment;

        if ($span->description !== null) {
            $payload['description'] = $span->description; // name
        }

        if ($span->op !== null) {
            $payload['op'] = $span->op; // attr.sentry.op
        }

        if ($span->status !== null) {
            $payload['status'] = $span->status; // status
        }

        if ($span->data !== null) {
            $payload['data'] = $span->data; // attributes
        }

        if ($event->getRelease() !== null) {
            $payload['release'] = $event->getRelease(); // attr.sentry.release -> check for convetion
        }

        if ($event->getEnvironment() !== null) {
            $payload['environment'] = $event->getEnvironment(); // attr.sentry.environment -> check for convetion
        }

        // TBD: status -> otel convetion, other status will be on attributes
        // TBD: transaction
        // TBD: trace-origin
        // TBD: profiling

        return sprintf("%s\n%s", JSON::encode($header), JSON::encode($payload));
    }
}
