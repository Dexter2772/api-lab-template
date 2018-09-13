<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require './vendor/autoload.php';
$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = 'root';
$config['db']['dbname'] = 'apilab';

$app = new \Slim\App(['settings' => $config]);

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$container = $app->getContainer();
$container['logger'] = function($c) {
    $logger = new \Monolog\Logger('my_logger');
    $file_handler = new \Monolog\Handler\StreamHandler('../logs/app.log');
    $logger->pushHandler($file_handler);
    return $logger;
};

$app->get('/books', function (Request $request, Response $response) {
    $this->logger->addInfo("GET /books");
    $people = $this->db->query('SELECT * from books')->fetchAll();
    $jsonResponse = $response->withJson($people);
    return $jsonResponse;
});

$app->get('/books/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $this->logger->addInfo("GET /books/{id}");
    $people = $this->db->query('SELECT * from books WHERE id='.$id)->fetch();
    $jsonResponse = $response->withJson($people);
    return $jsonResponse;
});

$app->put('/books/{id}', function (Request $request, Response $response, array $args) {
    $id = $args['id'];
    $this->logger->addInfo("PUT /books/".$id);
    // build query string
    $updateString = "UPDATE books SET ";
    $fields = $request->getParsedBody();
    $keysArray = array_keys($fields);
    $last_key = end($keysArray);
    foreach($fields as $field => $value) {
      $updateString = $updateString . "$field = '$value'";
      if ($field != $last_key) {
        // conditionally add a comma to avoid sql syntax problems
        $updateString = $updateString . ", ";
      }
    }
    $updateString = $updateString . " WHERE id = $id;";
    // execute query
    $this->db->exec($updateString);
    // return updated record
    $book = $this->db->query('SELECT * from books where id='.$id)->fetch();
    $jsonResponse = $response->withJson($book);
    return $jsonResponse;
});

$app->delete('/people/{id}', function (Request $request, Response $response, array $args) {
  $id = $args['id'];
  $this->logger->addInfo("DELETE /books/".$id);
  $book = $this->db->exec('DELETE FROM books where id='.$id);
  $jsonResponse = $response->withJson($book);
  return;
});

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'],
        $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$app->run();
