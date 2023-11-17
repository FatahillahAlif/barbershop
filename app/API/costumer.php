<?php

declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;


return function (App $app) {

    // get
    $app->get('/costumer', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL SelectCostumer()');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // get by id
    $app->get('/costumer/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $costumer_id = $args['id'];
    
        try {
            $query = $db->prepare('CALL SelectCostumerByID(:costumer_id)');
            $query->bindParam(':costumer_id', $costumer_id, PDO::PARAM_INT);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($results)) {
                $response->getBody()->write(json_encode(['error' => 'Data costumer tidak ditemukan']));
                return $response->withStatus(404)->withHeader("Content-Type", "application/json");
            }
    
            $response->getBody()->write(json_encode($results[0]));
            return $response->withHeader("Content-Type", "application/json")->withStatus(200);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    });

    // post data
    $app->post('/costumer', function (Request $request, Response $response) {
        $parsedBody = $request->getParsedBody();
        $customerName = $parsedBody["name"];
        $phoneNumber = $parsedBody["phone_number"];
    
        $db = $this->get(PDO::class);
    
        $query = $db->prepare('CALL CreateCostumerBaru(?, ?)'); // Mengganti dengan pemanggilan procedure
    
        $query->execute([$customerName, $phoneNumber]);
    
        $lastId = $db->lastInsertId();
    
        $response->getBody()->write(json_encode(
            [
                'message' => 'Costumer disimpan dengan ID ' . $lastId
            ]
        ));
    
        return $response->withHeader("Content-Type", "application/json");
    });

    // put data
    $app->put('/costumer/{id}', function (Request $request, Response $response, $args) {
        $parsedBody = $request->getParsedBody();
        $currentId = $args['id'];
        $newPhoneNumber = $parsedBody["phone_number"];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL UpdateCostumer(?, ?)');
            $query->execute([$currentId, $newPhoneNumber]);
    
            if ($query->rowCount() > 0) {
                // Data telah diperbarui
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Costumer dengan ID ' . $currentId . ' telah diupdate dengan nomor HP baru ' . $newPhoneNumber
                    ]
                ));
            } else {
                // ID tidak ditemukan
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Costumer dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
                return $response->withStatus(404); // Status kode not found
            }
        } catch (Exception $e) {
            // Penanganan kesalahan saat menjalankan permintaan ke database
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan saat mengupdate Costumer: ' . $e->getMessage()
                ]
            ));
            return $response->withStatus(500); // Status kode kesalahan server
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });

    // delete data
    $app->delete('/costumer/{id}', function (Request $request, Response $response, $args) {
        $currentId = $args['id'];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL DeleteCostumer(?)'); // Mengganti dengan pemanggilan procedure
            $query->execute([$currentId]);
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data Costumer dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Costumer dengan ID ' . $currentId . ' dihapus dari database'
                    ]
                ));
            }
        } catch (PDOException $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Database error ' . $e->getMessage()
                ]
            ));
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });

};