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
    $app->get('/service', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL SelectService');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // get by id
    $app->get('/service/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $serviceId = $args['id'];
    
        try {
            $query = $db->prepare('CALL SelectServiceByID(:service_id)');
            $query->bindParam(':service_id', $serviceId, PDO::PARAM_INT);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($results)) {
                $response->getBody()->write(json_encode(['error' => 'Data layanan tidak ditemukan']));
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
    $app->post('/service', function (Request $request, Response $response) {
        $parsedBody = $request->getParsedBody();
        
        $customerId = $parsedBody["customer_id"];
        $barberId = $parsedBody["barber_id"];
        $serviceMenuId = $parsedBody["service_menu_id"];
        $paymentDetailId = $parsedBody["payment_detail_id"];
        $price = $parsedBody["price"];
        $transactionDate = $parsedBody["transaction_date"];
    
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL CreateServiceWithTransaction(:customer_id, :barber_id, :service_menu_id, :payment_detail_id, :price, :transaction_date)');
            $query->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
            $query->bindParam(':barber_id', $barberId, PDO::PARAM_INT);
            $query->bindParam(':service_menu_id', $serviceMenuId, PDO::PARAM_INT);
            $query->bindParam(':payment_detail_id', $paymentDetailId, PDO::PARAM_INT);
            $query->bindParam(':price', $price, PDO::PARAM_INT);
            $query->bindParam(':transaction_date', $transactionDate, PDO::PARAM_STR);
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
    $app->put('/service/{id}', function (Request $request, Response $response, $args) {
        $parsedBody = $request->getParsedBody();
        $currentId = $args['id'];
        $newServiceMenuPrice = $parsedBody["service_menu_price"];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL UpdateServiceWithTransaction(?, ?)');
            $query->execute([$currentId, $newServiceMenuPrice]);
    
            if ($query->rowCount() > 0) {
                // Data telah diperbarui
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Service dengan ID ' . $currentId . ' telah diupdate dengan harga ' . $newServiceMenuPrice
                    ]
                ));
            } else {
                // ID tidak ditemukan
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Service dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
                return $response->withStatus(404); // Status kode not found
            }
        } catch (Exception $e) {
            // Penanganan kesalahan saat menjalankan permintaan ke database
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan saat mengupdate Service: ' . $e->getMessage()
                ]
            ));
            return $response->withStatus(500); // Status kode kesalahan server
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });

    // delete data
    $app->delete('/service/{id}', function (Request $request, Response $response, $args) {
        $currentId = $args['id'];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL DeleteServiceWithTransaction(?)');
            $query->execute([$currentId]);
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data Service dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Service dengan ID ' . $currentId . ' dihapus dari database'
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