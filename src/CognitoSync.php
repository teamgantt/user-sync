<?php

namespace TeamGantt\UserSync;

use Exception;
use RuntimeException;
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
     * @var string[]
     */
    private static $supportedActions = ['update', 'delete'];

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

        $action = $request->getAction();

        if (array_search($action, self::$supportedActions) === false) {
            throw new RuntimeException("Invalid action \"$action\" given");
        }

        try {
            switch ($action) {
                case 'update':
                    return $this->update($user, $request);
                case 'delete':
                    return $this->delete($request);
                default:
                    return false;
            }
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

    /**
     * Update the user in the remote store
     * 
     * @param SyncableUserInterface $user 
     * @param SyncRequestInterface $request 
     * @return bool 
     */
    private function update(SyncableUserInterface $user, SyncRequestInterface $request)
    {
        if ($request->hasPassword()) {
            $this->client->adminSetUserPassword([
                'Password' => $request->getPassword(),
                'Permanent' => true,
                'Username' => $user->getSyncableUsername(),
                'UserPoolId' => $this->clientDetail->getPoolId(),
            ]);
        }

        if ($request->hasEmailAddress()) {
            $this->client->adminUpdateUserAttributes([
                'UserPoolId' => $this->clientDetail->getPoolId(),
                'Username' => $user->getSyncableUsername(),
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
    }

    /**
     * Remove the user from the remote store.
     * 
     * @todo perhaps SyncRequestInterface should make usernames more generic - i.e getUsername returns an email address
     * 
     * @param SyncRequestInterface $request
     * @return bool
     */
    private function delete(SyncRequestInterface $request)
    {
        if (!$request->hasEmailAddress()) {
            return false;
        }

        $this->client->adminDeleteUser([
            'UserPoolId' => $this->clientDetail->getPoolId(),
            'Username' => $request->getEmailAddress(),
        ]);

        return true;
    }
}
