<?php
namespace dhernandez\api;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
require './vendor/autoload.php';

class App
{

   private $app;
   public function __construct($db) {

     $config['db']['host']   = 'localhost';
     $config['db']['user']   = 'root';
     $config['db']['pass']   = 'root';
     $config['db']['dbname'] = 'apilab';

     $app = new \Slim\App(['settings' => $config]);

     $container = $app->getContainer();
     $container['db'] = $db;

     $container['logger'] = function($c) {
         $logger = new \Monolog\Logger('my_logger');
         $file_handler = new \Monolog\Handler\StreamHandler('./logs/app.log');
         $logger->pushHandler($file_handler);
         return $logger;
     };

     $app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
         $name = $args['name'];
         $this->logger->addInfo('get request to /hello/'.$name);
         $response->getBody()->write("Hello, $name");

         return $response;
     });
     $app->get('/books', function (Request $request, Response $response) {
         $this->logger->addInfo("GET /books");
         $books = $this->db->query('SELECT * from books')->fetchAll();
         $jsonResponse = $response->withJson($books);
         return $jsonResponse;
     });
     $app->get('/books/{id}', function (Request $request, Response $response, array $args) {
         $id = $args['id'];
         $this->logger->addInfo("GET /books/".$id);
         $book = $this->db->query('SELECT * from books where id='.$id)->fetch();
         $jsonResponse = $response->withJson($book);

         if($book){
           $response =  $response->withJson($book);
         } else {
           $errorData = array('status' => 404, 'message' => 'not found');
           $response = $response->withJson($errorData, 404);
         }
         return $response;

     });
     $app->post('/books', function (Request $request, Response $response) {
         $this->logger->addInfo("POST /books");

         // check that peron exists
         // $person = $this->db->query('SELECT * from people where id='.$id)->fetch();
         // if(!$person){
         //   $errorData = array('status' => 404, 'message' => 'not found');
         //   $response = $response->withJson($errorData, 404);
         //   return $response;
         // }

         // build query string
         $createString = "INSERT INTO books ";
         $fields = $request->getParsedBody();
         $keysArray = array_keys($fields);
         $last_key = end($keysArray);
         $values = '(';
         $fieldNames = '(';
         foreach($fields as $field => $value) {
           $values = $values . "'"."$value"."'";
           $fieldNames = $fieldNames . "$field";
           if ($field != $last_key) {
             // conditionally add a comma to avoid sql syntax problems
             $values = $values . ", ";
             $fieldNames = $fieldNames . ", ";
           }
         }
         $values = $values . ')';
         $fieldNames = $fieldNames . ') VALUES ';
         $createString = $createString . $fieldNames . $values . ";";
         // execute query
         try {
           $this->db->exec($createString);
         } catch (\PDOException $e) {
           var_dump($e);
           $errorData = array('status' => 400, 'message' => 'Invalid data provided to create book');
           return $response->withJson($errorData, 400);
         }
         // return updated record
         $book = $this->db->query('SELECT * from books ORDER BY id desc LIMIT 1')->fetch();
         $jsonResponse = $response->withJson($book);

         return $jsonResponse;
     });
     $app->put('/books/{id}', function (Request $request, Response $response, array $args) {
         $id = $args['id'];
         $this->logger->addInfo("PUT /books/".$id);

         // check that peron exists
         $book = $this->db->query('SELECT * from books where id='.$id)->fetch();
         if(!$book){
           $errorData = array('status' => 404, 'message' => 'not found');
           $response = $response->withJson($errorData, 404);
           return $response;
         }

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
         try {
           $this->db->exec($updateString);
         } catch (\PDOException $e) {
           $errorData = array('status' => 400, 'message' => 'Invalid data provided to update');
           return $response->withJson($errorData, 400);

         }
         // return updated record
         $book = $this->db->query('SELECT * from books where id='.$id)->fetch();
         $jsonResponse = $response->withJson($book);

         return $jsonResponse;
     });
     $app->delete('/books/{id}', function (Request $request, Response $response, array $args) {
       $id = $args['id'];
       $this->logger->addInfo("DELETE /books/".$id);
       $deleteSuccessful = $this->db->exec('DELETE FROM books where id='.$id);
       if($deleteSuccessful){
         $response = $response->withStatus(200);
       } else {
         $errorData = array('status' => 404, 'message' => 'not found');
         $response = $response->withJson($errorData, 404);
       }
       return $response;
     });

     $this->app = $app;
   }

   /**
    * Get an instance of the application.
    *
    * @return \Slim\App
    */
   public function get()
   {
       return $this->app;
   }
 }
