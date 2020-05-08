<?php

namespace TeamGantt\UserSync\Contracts;

interface SyncInterface
{
    /**
     * Sync new user attributes with an external store.
     *
     * @param SyncableUserInterface $user
     * @param SyncRequestInterface $request
     *
     * @return bool
     */
    public function __invoke(SyncableUserInterface $user, SyncRequestInterface $request);
}
