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
    $app->get('/detail_pembayaran', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);

        $query = $db->query('CALL SelectPaymentDetail');
        $results = $query->fetchAll(PDO::FETCH_ASSOC);
        $response->getBody()->write(json_encode($results));

        return $response->withHeader("Content-Type", "application/json");
    });

    // get by id
    $app->get('/detail_pembayaran/{id}', function (Request $request, Response $response, $args) {
        $db = $this->get(PDO::class);
        $paymentDetailId = $args['id'];
    
        try {
            $query = $db->prepare('CALL SelectPaymentDetailByID(:payment_detail_id)');
            $query->bindParam(':payment_detail_id', $paymentDetailId, PDO::PARAM_INT);
            $query->execute();
    
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($results)) {
                $response->getBody()->write(json_encode(['error' => 'Data rincian pembayaran tidak ditemukan']));
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
    $app->post('/detail_pembayaran', function (Request $request, Response $response) {
        $parsedBody = $request->getParsedBody();
    
        $paymentMethod = $parsedBody["payment_method"];
    
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL CreatePaymentDetail(:payment_method)');
            $query->bindParam(':payment_method', $paymentMethod, PDO::PARAM_STR);
            $query->execute();
    
            $lastId = $db->lastInsertId();
    
            $response->getBody()->write(json_encode(
                [
                    'message' => 'Detail pembayaran disimpan dengan ID ' . $lastId
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
    $app->put('/detail_pembayaran/{id}', function (Request $request, Response $response, $args) {
        $parsedBody = $request->getParsedBody();
        $currentId = $args['id'];
        $newPaymentMethod = $parsedBody["name"];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL UpdatePaymentDetailWithTransaction(?, ?)');
            $query->execute([$currentId, $newPaymentMethod]);
    
            if ($query->rowCount() > 0) {
                // Data telah diperbarui
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Payment Detail dengan ID ' . $currentId . ' telah diupdate dengan metode pembayaran ' . $newPaymentMethod
                    ]
                ));
            } else {
                // ID tidak ditemukan
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Payment Detail dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
                return $response->withStatus(404); // Status kode not found
            }
        } catch (Exception $e) {
            // Penanganan kesalahan saat menjalankan permintaan ke database
            $response->getBody()->write(json_encode(
                [
                    'error' => 'Terjadi kesalahan saat mengupdate Payment Detail: ' . $e->getMessage()
                ]
            ));
            return $response->withStatus(500); // Status kode kesalahan server
        }
    
        return $response->withHeader("Content-Type", "application/json");
    });

    // delete data
    $app->delete('/detail_pembayaran/{id}', function (Request $request, Response $response, $args) {
        $currentId = $args['id'];
        $db = $this->get(PDO::class);
    
        try {
            $query = $db->prepare('CALL DeleteDetailPembayaranWithTransaction(?)');
            $query->execute([$currentId]);
    
            if ($query->rowCount() === 0) {
                $response = $response->withStatus(404);
                $response->getBody()->write(json_encode(
                    [
                        'error' => 'Data Detail Pembayaran dengan ID ' . $currentId . ' tidak ditemukan'
                    ]
                ));
            } else {
                $response->getBody()->write(json_encode(
                    [
                        'message' => 'Detail Pembayaran dengan ID ' . $currentId . ' dihapus dari database'
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