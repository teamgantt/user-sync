<?php

namespace TeamGantt\UserSync\Contracts;

interface CognitoClientDetailInterface
{
    /**
     * @return string
     */
    public function getClientId();

    /**
     * @return string
     */
    public function getPoolId();

    /**
     * @return string
     */
    public function getRegion();
}
