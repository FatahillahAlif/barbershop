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
    $app->get('/service_menu', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL SelectServiceMenu()');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // get by id
    $app->get('/service_menu/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $serviceMenuId = $args['id'];
    
        try {
            $query = $db->prepare('CALL SelectServiceMenuByID(:service_menu_id)');
            $query->bindParam(':service_menu_id', $serviceMenuId, PDO::PARAM_INT);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($results)) {
                $response->getBody()->write(json_encode(['error' => 'Data menu layanan tidak ditemukan']));
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
    $app->post('/service_menu', function (Request $request, Response $response) {
        $parsedBody = $request->getParsedBody();
    
        $serviceMenuName = $parsedBody["service_menu_name"];
        $serviceMenuPrice = $parsedBody["service_menu_price"];
    
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL CreateServiceMenu(:service_menu_name, :service_menu_price)');
            $query->bindParam(':service_menu_name', $serviceMenuName, PDO::PARAM_STR);
            $query->bindParam(':service_menu_price', $serviceMenuPrice, PDO::PARAM_INT);
            $query->execute();
    
            $lastId = $db->lastInsertId();
    
            $response->getBody()->write(json_encode(
                [
                    'message' => 'Menu layanan disimpan dengan ID ' . $lastId
                ]
            ));
    
            return $response->withHeader("Content-Type", "application/json")->withStatus(200);
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Internal Server Error: ' . $e->getMessage()]));
            return $response->withStatus(500)->withHeader("Content-Type", "application/json");
        }
    });

    // put data
    $app->put('/service_menu/{id}', function (Request $request, Response $response, $args) {
        $parsedBody = $request->getParsedBody();
        $currentId = $args['id'];
        $newServiceMenuName = $parsedBody["service_menu_name"];
        $newServiceMenuPrice = $parsedBody["service_menu_price"];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL UpdateServiceMenuWithTransaction(?, ?, ?)');
            $query->execute([$currentId, $newServiceMenuName, $newServiceMenuPrice]);
    
            if ($query->rowCount() > 0) {
                // Data telah diperbarui
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Service Menu dengan ID ' . $currentId . ' telah diupdate dengan nama ' . $newServiceMenuName . ' dan harga ' . $newServiceMenuPrice
                    ]
                ));
            } else {
                // ID tidak ditemukan
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Service Menu dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
                return $response->withStatus(404); // Status kode not found
            }
        } catch (Exception $e) {
            // Penanganan kesalahan saat menjalankan permintaan ke database
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan saat mengupdate Service Menu: ' . $e->getMessage()
                ]
            ));
            return $response->withStatus(500); // Status kode kesalahan server
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });

    // delete data
    $app->delete('/service_menu/{id}', function (Request $request, Response $response, $args) {
        $currentId = $args['id'];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL DeleteServiceMenuWithTransaction(?)');
            $query->execute([$currentId]);
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data Service Menu dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Service Menu dengan ID ' . $currentId . ' dihapus dari database'
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