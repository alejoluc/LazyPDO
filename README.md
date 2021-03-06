# alejoluc\LazyPDO

## Contents
1. [Installation](#installation)
2. [On Connection Errors](#on-connection-errors)
3. [Usage](#usage)
4. [On Dependency Injection Containers](#why-not-use-a-dependency-injection-container-instead)

This package provides a drop-in replacement for PHP's native PDO class.

The LazyPDO class is *lazy* in the sense that, unlike native PDO, it will not attempt to connect to the database server upon instantiation. Instead, it will store all the connection details and wait until it actually needs a connection, for example, to execute a query.

This is useful if you have an effective caching mechanism in place, and the database may not need to respond to all requests.

LazyPDO *extends* PDO, so any instance of LazyPDO [*is an*](https://en.wikipedia.org/wiki/Liskov_substitution_principle) instance of PDO. That means you can pass along an instance of LazyPDO anywhere a PDO instance is expected.

```php
<?php
// autoloading, etc.
use alejoluc\LazyPDO\LazyPDO;

function expectsPDO(PDO $dependency) {
    //...
}

$lazypdo = new LazyPDO('mysql:host=localhost;dbname=db;charset=utf8', 'root', 'root');
expectsPDO($lazypdo); // Valid
```

## Installation

Option A) From the command line

`composer require alejoluc/lazypdo:*`

Option B) Add the dependency in the "require" section of composer.json, then run `composer install `or `composer update`, as needed

```json
{
  "require": {
    "alejoluc/lazypdo": "*"
  }
}
```

## On Connection errors

Before getting into usage details, one thing you have to consider is connection errors. In PHP's native PDO, the connection error would be raised as soon as you try to instantiate the class with a bad connection string, or with credentials rejected by the database server. A simple try/catch construct around PDO's instantiation is sufficient in that case. However, since this class is "lazy" and delays the connection until it needs it, this means the connection error could be raised anywhere in your code (that is, upon the first call of a method that requires a connection to be established). To avoid having to wrap every database call inside try/catch blocks (as long as you are not using `PDO::ERRMODE_EXCEPTION`), you can use the `onConnectionError()` method to specify a callback to handle a potential `PDOException` upon connection. Any `callable` can be passed to the `onConnectionError()` method, not just functions, so `$lazypdo->onConnectionError([$myErrorHandler, 'handle']);` is also valid. An example with a function instead:

```php
<?php
// autoload, etc....
use alejoluc\LazyPDO\LazyPDO;

$pdo = new LazyPDO('mysql:host=localhost;dbname=db;charset=utf8', 'not_a_valid_user', 'pass');
$pdo->onConnectionError(function($ex) use ($app){
    $error = $ex->getMessage();
    $app->logError('PDO reported an error: ' . $error);
    $app->getDevelopers->angryEmail($error);
    $app->redirect('/database-maintenance');
    $app->shutdown();
});


$stmt = $pdo->prepare('SELECT ...'); // This will attempt a connection, the connection will fail, and the previously defined callback will handle the raised PDOException.
```

However, many applications have a top-level try/catch, or they have custom error handling. So, by default, if you do not specify a callback, LazyPDO will just bubble up the exception until it is (hopefully) catched and handled properly.


## Usage

LazyPDO can be used as you would use PDO. If you know PDO, you know LazyPDO. Simple as that. Here are some examples to refresh the mind. Note that you can access the constants from either PDO or LazyPDO, and you can intermingle them, but why do that? Just stick with PDO::* constants to make it clear that they are interoperable.

#### With PDO::ERRMODE as ERRMODE_SILENT (default PDO behavior)

```php
<?php
// require composer autoloading here

use alejoluc\LazyPDO\LazyPDO;

$pdo = new LazyPDO('mysql:host=localhost;dbname=information_schema;charset=utf8', 'root', 'root', [
    LazyPDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES       => false,
]);

// This will fail, there is no CHARACTER_SET table. To test the success scenario, change it to CHARACTER_SETS
$stmt = $pdo->prepare('SELECT * FROM CHARACTER_SET WHERE DEFAULT_COLLATE_NAME = ?');

if ($stmt === false) { // With this error mode, you must manually check errors
    $error = $pdo->errorInfo()[2];
    echo "Database error: $error";
    die();
}

$stmt->bindValue(1, 'utf8_general_ci');
$stmt->execute();

var_dump($stmt->fetchAll());
```

#### With ERR_MODE as ERRMODE_EXCEPTION, the recommended behavior, or so it was a week or two ago

```php
<?php
// require composer autoloading here

use alejoluc\LazyPDO\LazyPDO;

$pdo = new LazyPDO('mysql:host=localhost;dbname=information_schema;charset=utf8', 'root', 'root', [
    PDO::ATTR_ERRMODE                => LazyPDO::ERRMODE_EXCEPTION,
    LazyPDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES       => false,
]);

try {
    // This will fail, there is no CHARACTER_SET table. To test the success scenario, change it to CHARACTER_SETS
    $stmt = $pdo->prepare('SELECT * FROM CHARACTER_SET WHERE DEFAULT_COLLATE_NAME = ?');
    $stmt->bindValue(1, 'utf8_general_ci');
    $stmt->execute();

    var_dump($stmt->fetchAll());
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    die();
}
```

Again, if you have an application level try/catch, or a custom error handler that catches all errors and exceptions, which you probably should, the try/catch construct is unnecessary.


## Why not use a Dependency Injection Container instead?

You can, and you can put LazyPDO into it. But if you want to use native PDO, you could also get the lazy behavior by using a DIC, as long as you never pass the PDO key directly and pass the container instead (in which case, it's not a DIC, it's a Service Locator, which [some people](http://www.davidarno.org/2015/10/21/is-the-service-locator-an-anti-pattern/) consider an [anti-pattern](http://blog.ploeh.dk/2010/02/03/ServiceLocatorisanAnti-Pattern/) that should be avoided, but not [all of them](https://candordeveloper.com/2013/04/10/dependency-injection-is-over-hyped/)). If you do pass the PDO key directly, however, you do not get the same behavior as with LazyPDO, because when you access a member in a DIC, the DIC will most likely instantiate what it contains in said key. Consider [Pimple](https://github.com/silexphp/pimple), which by the way I think is great.

```php
<?php
// autoload, instantiate a pimple container into $c, store an hypothetical caching server connection into it, etc.
$c['db'] = function(){
    return new PDO('....');
};

function getUserData($userId, $db, $cacheConnection) {
    if ($cacheConnection->inCache('user:' . $userId)) {
        return $cacheConnection->getCached('user:' . $userId);
    } else {
        $stmt = $db->prepare('SELECT ...');
        // and so on and so on
    }
}

$data = getUserData('admin', $c['db'], $c['cache']); // You are accessing the 'db' key inside the Container, and a connection will try to be established because of that, although you can see in the getUserData() definition that no connection may be needed at all.
```

To make sure native PDO is not instantiated unless necessary, the function and the call should be refactored to something like this:

```php
<?php
function getUserData($userId, $c) {
    if ($c['cache']->inCache('user:' . $userId)) {
        return $c['cache']->getCached('user:' . $userId);
    } else {
        $stmt = $c['db']->prepare('SELECT ...'); // PDO instantiation happens here, so it will not happen if the data is cached
        // and so on and so on
    }
}

getUserData('admin', $c);
```
However, if instead of PDO you are using LazyPDO inside the DIC, in any of the previous two code examples the database connection will not be established unless the cached data cannot be found. Let's see the first example again, but with LazyPDO instead:

```php
<?php
// autoload, instantiate a pimple container into $c, store an hypothetical caching server connection into it, etc.
use alejoluc\LazyPDO\LazyPDO;
$c['db'] = function(){
    return new LazyPDO('....');
};

function getUserData($userId, $db, $cacheConnection) {
    if ($cacheConnection->inCache('user:' . $userId)) {
        return $cacheConnection->getCached('user:' . $userId);
    } else {
        $stmt = $db->prepare('SELECT ...'); // The connection will try to be established here, not on the getUserData() function call
    }
}

$data = getUserData('admin', $c['db'], $c['cache']); // No connection to the database will try to be established here because $c['db'] will return an instance of LazyPDO
```