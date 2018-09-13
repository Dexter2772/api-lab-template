<?php
namespace feather\firstSlimClient;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\PhpRenderer;
require './vendor/autoload.php';
class App
{
   private $app;
   private const SCRIPT_INCLUDE = '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
   <script
     src="https://code.jquery.com/jquery-3.3.1.min.js"
     integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
     crossorigin="anonymous"></script>
   </head>
   <script src=".public/script.js"></script>';
   public function __construct() {
     $app = new \Slim\App(['settings' => $config]);
     $container = $app->getContainer();
     $container['logger'] = function($c) {
         $logger = new \Monolog\Logger('my_logger');
         $file_handler = new \Monolog\Handler\StreamHandler('./logs/app.log');
         $logger->pushHandler($file_handler);
         return $logger;
     };
     $container['renderer'] = new PhpRenderer("./templates");
     function makeApiRequest($path){
       $ch = curl_init();
       //Set the URL that you want to GET by using the CURLOPT_URL option.
       curl_setopt($ch, CURLOPT_URL, "http://localhost/api/$path");
       curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
       $response = curl_exec($ch);
       return json_decode($response, true);
     }
     //PUT TEST
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
    //PUT TEST END
     $app->get('/', function (Request $request, Response $response, array $args) {
       $responseRecords = makeApiRequest('books');
       $tableRows = "";
       foreach($responseRecords as $book) {
         $tableRows = $tableRows . "<tr>";
         $tableRows = $tableRows . "<td>".$book["title"]."</td><td>".$book["author"]."</td><td>".$book["year"]."</td>";
         $tableRows = $tableRows . "<td>
         <a href='http://localhost:8080/api/apiClient/books/".$book["id"]."' class='btn btn-primary'>View Details</a>
         <a href='http://localhost:8080/api/apiClient/books/".$book["id"]."/edit' class='btn btn-secondary'>Edit</a>
         <a data-id='".$book["id"]."' class='btn btn-danger deletebtn'>Delete</a>
         </td>";
         $tableRows = $tableRows . "</tr>";
       }
       $templateVariables = [
           "title" => "Books",
           "tableRows" => $tableRows
       ];
       return $this->renderer->render($response, "/books.html", $templateVariables);
     });
     $app->get('/books/add', function(Request $request, Response $response) {
       $templateVariables = [
         "type" => "new",
         "title" => "Create Book"
       ];
       return $this->renderer->render($response, "/bookForm.html", $templateVariables);
     });
     $app->get('/books/{id}', function (Request $request, Response $response, array $args) {
         $id = $args['id'];
         $responseRecords = makeApiRequest('books/'.$id);
         $body = "<h1>Title: ".$responseRecords['title']."</h1>";
         $body = $body . "<h2>Author: ".$responseRecords['author']."</h2>";
         $body = $body . "<h3>Year: ".$responseRecords['year']."</h3>";
         $response->getBody()->write($body);
         return $response;
     });
     $app->get('/books/{id}/edit', function (Request $request, Response $response, array $args) {
         $id = $args['id'];
         $responseRecord = makeApiRequest('books/'.$id);
         $templateVariables = [
           "type" => "edit",
           "title" => "Edit Book",
           "books" => $responseRecord
         ];


         return $this->renderer->render($response, "/bookEditForm.html", $templateVariables);
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
