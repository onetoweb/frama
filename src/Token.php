<?php

namespace Onetoweb\Frama;

use DateTime;

/**
 * Frama Api Token
 *
 * @author Jonathan van 't Ende <jvantende@onetoweb.nl>
 * @copyright Onetoweb B.V.
 */
class Token
{
    const EXPIRES_IN = 800;
    
    /**
     * @var string
     */
    private $token;
    
    /**
     * @var DateTime
     */
    private $expires;
    
    /**
     * @param string $token
     * @param DateTime $expires
     */
    public function __construct(string $token, DateTime $expires)
    {
        $this->token = $token;
        $this->expires = $expires;
    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return $this->token;
    }
    
    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
    
    /**
     * @return DateTime
     */
    public function getExpires(): DateTime
    {
        return $this->expires;
    }
    
    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return (bool) (new DateTime() > $this->expires);
    }
}