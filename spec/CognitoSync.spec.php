<?php

use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Kahlan\Plugin\Double;
use Psr\Log\LoggerInterface;
use TeamGantt\UserSync\CognitoSync;
use TeamGantt\UserSync\Contracts\CognitoClientDetailInterface;
use TeamGantt\UserSync\Contracts\SyncableUserInterface;
use TeamGantt\UserSync\SyncRequest;

describe('CognitoSync', function () {
    beforeEach(function () {
        $this->client = Double::instance(['extends' => CognitoIdentityProviderClient::class, 'methods' => ['__construct']]);
        $this->detail = Double::instance(['implements' => CognitoClientDetailInterface::class]);
        $this->logger = Double::instance(['implements' => LoggerInterface::class]);
        $this->user = Double::instance(['implements' => SyncableUserInterface::class]);
        $this->sync = new CognitoSync($this->client, $this->detail, $this->logger);

        allow($this->detail)->toReceive('getPoolId')->andReturn('pool-id');
        allow($this->user)->toReceive('getSyncableUsername')->andReturn('dev@tg.com');
    });

    describe('__invoke()', function () {
        it('should return true if the request is empty', function () {
            $request = SyncRequest::fromArray([]);
            expect($this->sync($this->user, $request))->toBe(true);
        });

        it('should throw an exception for an unsupported action', function () {
            $request = SyncRequest::fromArray(['action' => 'party', 'email_address' => 'brian@internet.com']);
            $sut = function () use ($request) {
                $this->sync($this->user, $request);
            };
            expect($sut)->toThrow(new RuntimeException('Invalid action "party" given'));
        });

        it('should sync the password to what is in the request', function () {
            $request = SyncRequest::fromArray(['password' => 'secret']);
            
            allow($this->client)->toReceive('adminSetUserPassword')->andReturn(true);
            expect($this->client)->toReceive('adminSetUserPassword')->with([
                'Password' => 'secret',
                'Permanent' => true,
                'Username' => 'dev@tg.com',
                'UserPoolId' => 'pool-id'
            ]);

            expect($this->sync($this->user, $request))->toBe(true);
        });

        it('should sync the email to what is in the request', function () {
            $request = SyncRequest::fromArray(['email_address' => 'dev@tg.com']);
            allow($this->user)->toReceive('getSyncableUsername')->andReturn('original@tg.com');

            allow($this->client)->toReceive('adminUpdateUserAttributes')->andReturn(true);
            expect($this->client)->toReceive('adminUpdateUserAttributes')->with([
                'UserPoolId' => 'pool-id',
                'Username' => 'original@tg.com',
                'UserAttributes' => [
                    [
                        'Name' => 'email',
                        'Value' => 'dev@tg.com'
                    ],
                    [
                        'Name' => 'email_verified',
                        'Value' => 'true'
                    ]
                ]
            ]);

            expect($this->sync($this->user, $request))->toBe(true);
        });

        it('should delete a user based on a delete action and the request data', function () {
            $request = SyncRequest::fromArray(['email_address' => 'brian@internet.com', 'action' => 'delete']);
            allow($this->client)->toReceive('adminDeleteUser')->andReturn([]);
            expect($this->client)->toReceive('adminDeleteUser')->with([
                'UserPoolId' => 'pool-id',
                'Username' => 'brian@internet.com'
            ]);
            expect($this->sync($this->user, $request))->toBe(true);
        });

        it('should return false if the delete request has no email address', function () {
            $request = SyncRequest::fromArray(['password' => 'secret', 'action' => 'delete']);
            expect($this->sync($this->user, $request))->toBe(false);
        });

        it('should ignore UserNotFoundExceptions', function () {
            $request = SyncRequest::fromArray(['password' => 'secret']);
            
            allow($this->client)->toReceive('adminSetUserPassword')->andRun(function () {
                $code = 'UserNotFoundException';
                $command = Double::instance(['implements' => CommandInterface::class]);
                throw new AwsException('message', $command, ['code' => $code]);
            });

            expect($this->logger)->toReceive('info')->with('User not found');

            expect($this->sync($this->user, $request))->toBe(true);
        });

        it('should return false for other aws exceptions', function () {
            $request = SyncRequest::fromArray(['password' => 'secret']);
            
            allow($this->client)->toReceive('adminSetUserPassword')->andRun(function () {
                $code = 'BadTimesManException';
                $command = Double::instance(['implements' => CommandInterface::class]);
                throw new AwsException('ignored', $command, ['code' => $code, 'message' => 'Bad times man']);
            });

            expect($this->logger)->toReceive('error')->with('Bad times man');

            expect($this->sync($this->user, $request))->toBe(false);
        });

        it('should log non aws exceptions and return false', function () {
            $request = SyncRequest::fromArray(['password' => 'secret']);
            
            allow($this->client)->toReceive('adminSetUserPassword')->andRun(function () {
                throw new Exception('WHOA');
            });

            expect($this->logger)->toReceive('error')->with('WHOA');

            expect($this->sync($this->user, $request))->toBe(false);
        });
    });
});
