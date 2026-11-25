<?php declare(strict_types=1);

namespace Tagging\GTM\Util;

/**
 * Gives an event that is queued in the session its own identity, so the frontend
 * can recognise it when the same occurrence is delivered again.
 *
 * Queued events are not guaranteed to leave the session after delivery: on some
 * stores session writes during customer/section/load are not persisted, and Hyvä
 * re-caches section data in localStorage. The frontend remembers which event_key
 * values it already pushed and skips anything older than its max age.
 */
class SessionEventStamper
{
    /**
     * @param array $data
     * @return array
     */
    public function stamp(array $data): array
    {
        $meta = isset($data['meta']) && is_array($data['meta']) ? $data['meta'] : [];
        $meta['event_key'] = bin2hex(random_bytes(8));
        $meta['created_at'] = time();
        $data['meta'] = $meta;

        return $data;
    }
}
