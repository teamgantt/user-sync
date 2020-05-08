<?php

namespace TeamGantt\UserSync\Contracts;

/**
 * An interface that guarantees a user can provide attributes that sync across stores
 */
interface SyncableUserInterface
{
    /**
     * Get the email address of the syncable user
     *
     * @return string
     */
    public function getSyncableUsername();
}
