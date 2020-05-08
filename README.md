# user-sync

Sync user data with external stores.

Currently includes support for the following remote stores:
* [AWS Cognito](https://aws.amazon.com/cognito/)

## usage

Currently deals in syncing passwords and email addresses. This is for use cases that
may require it, though the typical use case does not involve syncing passwords. The case
for syncing a password may make sense during a period of migration or phasing out local storage of passwords.


`SyncInterface` exposes a contract for PHP invokable classes, and looks like this:

```php
public function __invoke(SyncableUserInterface $user, SyncRequestInterface $request);
```

There is a default implementation of `SyncRequestInterface` included as `SyncRequest`.

```php
use TeamGantt\UserSync\SyncRequest;
use TeamGantt\UserSync\CognitoSync;

$args = []; // see CognitoSync for required arguments - note: requires aws/aws-sdk-php
$sync = new CognitoSync(...$args);

// note that all params to SyncRequest are optional. Syncing will or will not happen depending on what is given
$request = SyncRequest::fromArray(['password' => 'newcleartextpassword', 'email_address' => 'newemail@email.com']);
$user = ExampleUserRepository::fetch($userId); // $user can be any object that implements the included SyncableUserInterface
$sync($user, $request); // tada!
```

## testing

```
$ composer test
```
