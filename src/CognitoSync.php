<?php

namespace TeamGantt\UserSync;

use Exception;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Exception\AwsException;
use Psr\Log\LoggerInterface;
use TeamGantt\UserSync\Contracts\CognitoClientDetailInterface;
use TeamGantt\UserSync\Contracts\SyncableUserInterface;
use TeamGantt\UserSync\Contracts\SyncInterface;
use TeamGantt\UserSync\Contracts\SyncRequestInterface;

class CognitoSync implements SyncInterface
{
    /**
     * @var CognitoIdentityProviderClient
     */
    protected $client;

    /**
     * @var CognitoClientDetailInterface
     */
    protected $clientDetail;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CognitoSyncPassword constructor.
     *
     * @param CognitoIdentityProviderClient $client
     * @param CognitoClientDetailInterface $clientDetail
     * @param LoggerInterface $logger
     */
    public function __construct(
        CognitoIdentityProviderClient $client,
        CognitoClientDetailInterface $clientDetail,
        LoggerInterface $logger)
    {
        $this->client = $client;
        $this->clientDetail = $clientDetail;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     *
     * @param SyncableUserInterface $user
     * @param SyncRequestInterface $request
     *
     * @return bool
     */
    public function __invoke(SyncableUserInterface $user, SyncRequestInterface $request)
    {
        if ($request->isEmpty()) {
            return true;
        }

        try {
            if ($request->hasPassword()) {
                $this->client->adminSetUserPassword([
                    'Password' => $request->getPassword(),
                    'Permanent' => true,
                    'Username' => $user->getEmailAddress(),
                    'UserPoolId' => $this->clientDetail->getPoolId(),
                ]);
            }

            if ($request->hasEmailAddress()) {
                $this->client->adminUpdateUserAttributes([
                    'UserPoolId' => $this->clientDetail->getPoolId(),
                    'Username' => $user->getEmailAddress(),
                    'UserAttributes' => [
                        [
                            'Name' => 'email',
                            'Value' => $request->getEmailAddress(),
                        ],
                        [
                            'Name' => 'email_verified',
                            'Value' => 'true',
                        ],
                    ],
                ]);
            }

            return true;
        } catch (AwsException $e) {
            $type = $e->getAwsErrorCode();
            switch ($type) {
                case 'UserNotFoundException':
                    // if a user is not in cognito, we do not want to prevent password updates
                    $this->logger->info('User not found');

                    return true;
                default:
                    $this->logger->error($e->getAwsErrorMessage());

                    return false;
            }
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return false;
    }
}
