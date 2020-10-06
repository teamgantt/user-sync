<?php

namespace TeamGantt\UserSync;

use TeamGantt\UserSync\Contracts\SyncRequestInterface;

class SyncRequest implements SyncRequestInterface
{
    /**
     * @var string
     */
    private $emailAddress;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $action;

    /**
     * SyncRequest constructor.
     *
     * @param null|string $password
     * @param null|string $emailAddress
     * @param string $action
     */
    private function __construct($password, $emailAddress, $action = 'update')
    {
        $this->password = $password;
        $this->emailAddress = $emailAddress;
        $this->action = $action;
    }

    /**
     * Factory method for creating a SyncRequest.
     *
     * @param array $properties
     *
     * @return SyncRequest
     */
    public static function fromArray(array $properties)
    {
        return new static($properties['password'] ?? null, $properties['email_address'] ?? null, $properties['action'] ?? 'update');
    }

    /**
     * @return null|string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @return null|string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function hasEmailAddress()
    {
        return !empty($this->emailAddress);
    }

    /**
     * @return bool
     */
    public function hasPassword()
    {
        return !empty($this->password);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return !$this->hasPassword() && !$this->hasEmailAddress();
    }

    /**
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }
}
