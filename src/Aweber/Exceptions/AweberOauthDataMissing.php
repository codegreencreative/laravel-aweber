<?php

namespace CodeGreenCreative\Aweber\Aweber\Exceptions;

/**
 * AWeberOAuthDataMissing
 *
 * Used when a specific piece or pieces of data was not found in the
 * response. This differs from the exception that might be thrown as
 * an AWeberOAuthException when parameters are not provided because
 * it is not the servers' expectations that were not met, but rather
 * the expectations of the client were not met by the server.
 *
 * @uses AWeberException
 * @package
 * @version $id$
 */
class AweberOauthDataMissing extends AweberException
{
    public function __construct($missing)
    {
        if (!is_array($missing)) {
            $missing = [$missing];
        }
        $this->missing = $missing;
        $required = join(', ', $this->missing);
        parent::__construct("OAuthDataMissing: Response was expected to contain: {$required}");
    }
}
