<?php

declare(strict_types=1);

namespace Tagging\GTM\Model\HelperScript;

/**
 * Sends the response to the visitor, so work after it does not keep the visitor waiting
 */
class ResponseFinisher
{
    /**
     * @return bool Whether the response is sent, false when the server cannot do that before the script ends
     */
    public function finish(): bool
    {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        // Magento writes the session in its own shutdown function, which runs before this one. Closing it
        // keeps the next request of the visitor from waiting for the session lock
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        if (function_exists('fastcgi_finish_request')) {
            return fastcgi_finish_request();
        }

        if (function_exists('litespeed_finish_request')) {
            return litespeed_finish_request();
        }

        return false;
    }
}
