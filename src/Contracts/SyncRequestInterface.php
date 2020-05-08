<?php

namespace TeamGantt\UserSync\Contracts;

interface SyncRequestInterface
{
    /**
     * @return null|string
     */
    public function getEmailAddress();

    /**
     * @return null|string
     */
    public function getPassword();

    /**
     * @return bool
     */
    public function hasEmailAddress();

    /**
     * @return bool
     */
    public function hasPassword();

    /**
     * @return bool
     */
    public function isEmpty();
}
