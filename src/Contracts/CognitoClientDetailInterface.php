<?php

namespace TeamGantt\UserSync\Contracts;

interface CognitoClientDetailInterface
{
    /**
     * @return string
     */
    public function getPoolId();
}
