<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use Slim\Routing\RouteContext;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use DI\ContainerBuilder;
use MongoDB\BSON\ObjectID;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions(
    [
        'settings' => function () {
            return include __DIR__ . '/../config/settings.php';
        },
        'view'     => function () {
            return Twig::create(__DIR__ . '/../views');
        },
        'mongo'    => function ($c) {
            return new MongoDB\Client($c->get('settings')['mongo']['uri']);
        },
        'guzzle'   => function ($c) {
            $token = $c->get('settings')['rev']['token'];
            return new Client(
                [
                    'base_uri' => 'https://api.rev.ai/speechtotext/v1/jobs',
                    'headers'  => ['Authorization' => "Bearer $token"],
                ]
            );
        },
    ]
);

$container = $containerBuilder->build();

AppFactory::setContainer($container);

$app = AppFactory::create();

$app->add(TwigMiddleware::createFromContainer($app));

$app->addErrorMiddleware(true, true, true);

$app->get(
    '/',
    function (Request $request, Response $response, $args) {
        $url = RouteContext::fromRequest($request)->getRouteParser()->urlFor('index');
        return $response->withStatus(302)->withHeader('Location', $url);
    }
);

$app->get(
    '/index[/[{status}]]',
    function (Request $request, Response $response, $args) {
        $mongoClient = $this->get('mongo');
        return $this->get('view')->render(
            $response,
            'index.twig',
            [
                'status' => $request->getAttribute('status'),
                'data'   => $mongoClient->mydb->notes->find(
                    [],
                    [
                        'sort' => [
                            'ts' => -1,
                        ],
                    ]
                ),
            ]
        );
    }
)->setName('index');

$app->get(
    '/add',
    function (Request $request, Response $response, $args) {
        return $this->get('view')->render(
            $response,
            'add.twig',
            []
        );
    }
)->setName('add');

$app->post(
    '/add',
    function (Request $request, Response $response) {
        $mongoClient = $this->get('mongo');
        try {
            $insertResult = $mongoClient->mydb->notes->insertOne(
                [
                    'status' => 'JOB_RECORDED',
                    'ts'     => time(),
                    'jid'    => false,
                    'error'  => false,
                    'data'   => false,
                ]
            );
            $id = (string)$insertResult->getInsertedId();

            $uploadedFiles = $request->getUploadedFiles();
            // handle single input with single file upload
            $uploadedFile = $uploadedFiles['file'];

            if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                $mongoClient->mydb->notes->updateOne(
                    [
                        '_id' => new ObjectID($id),
                    ],
                    [
                        '$set' => ['status' => 'JOB_UPLOADED'],
                    ]
                );
                $revClient   = $this->get('guzzle');
                $revResponse = $revClient->request(
                    'POST',
                    'jobs',
                    [
                        'multipart' => [
                            [
                                'name'     => 'media',
                                'contents' => fopen($uploadedFile->getFilePath(), 'r'),
                            ],
                            [
                                'name'     => 'options',
                                'contents' => json_encode(
                                    [
                                        'metadata'         => $id,
                                        'callback_url'     => $this->get('settings')['rev']['callback'],
                                        'skip_diarization' => 'true',
                                    ]
                                ),
                            ],
                        ],
                    ]
                )->getBody()->getContents();
                $json        = json_decode($revResponse);

                $mongoClient->mydb->notes->updateOne(
                    [
                        '_id' => new ObjectID($id)
                    ],
                    [
                        '$set' => [
                            'status' => 'JOB_TRANSCRIPTION_IN_PROGRESS',
                            'jid'    => $json->id,
                        ],
                    ]
                );
                $response->getBody()->write(json_encode(['data' => ['status' => 'success']]));
                return $response->withHeader('Content-Type', 'application/vnd.api+json')->withStatus(200);
            }//end if
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $mongoClient->mydb->notes->updateOne(
                [
                    '_id' => new ObjectID($id)
                ],
                [
                    '$set' => [
                        'status' => 'JOB_TRANSCRIPTION_FAILURE',
                        'error'  => $e->getMessage(),
                    ],
                ]
            );
            $response->getBody()->write(
                json_encode(
                    [
                        'error' => [
                            'detail' => $e->getMessage(),
                            'status' => $e->getResponse()->getStatusCode(),
                        ],
                    ]
                )
            );
            return $response->withHeader('Content-Type', 'application/vnd.api+json')->withStatus($e->getResponse()->getStatusCode());
        } catch (\Exception $e) {
          $mongoClient->mydb->notes->updateOne(
              [
                  '_id' => new ObjectID($id)
              ],
              [
                  '$set' => [
                      'status' => 'JOB_TRANSCRIPTION_FAILURE',
                      'error'  => $e->getMessage(),
                  ],
              ]
          );
          $response->getBody()->write(
              json_encode(
                  [
                      'error' => [
                          'detail' => $e->getMessage(),
                          'status' => 500,
                      ],
                  ]
              )
          );
          return $response->withHeader('Content-Type', 'application/vnd.api+json')->withStatus(500);
      }

    }
);

$app->get(
    '/delete/{id}',
    function (Request $request, Response $response, $args) {
        $id        = filter_var($args['id'], FILTER_SANITIZE_STRING);
        $mongoClient = $this->get('mongo');
        $mongoClient->mydb->notes->deleteOne(
            [
                '_id' => new ObjectID($id)
            ]
        );
        return $response->withHeader('Location', '/index/success-deleted')->withStatus(200);
    }
)->setName('delete');

$app->post(
    '/hook',
    function (Request $request, Response $response) {
        $mongoClient = $this->get('mongo');
        $json        = json_decode($request->getBody());
        $jid         = $json->job->id;
        $id          = $json->job->metadata;
        if ($json->job->status === 'transcribed') {
            $mongoClient->mydb->notes->updateOne(
                [
                    '_id' => new ObjectID($id)
                ],
                [
                    '$set' => ['status' => 'JOB_TRANSCRIPTION_SUCCESS'],
                ]
            );
            $revClient   = $this->get('guzzle');
            $revResponse = $revClient->request(
                'GET',
                "jobs/$jid/transcript",
                [
                    'headers' => ['Accept' => 'text/plain'],
                ]
            )->getBody()->getContents();
            $transcript  = explode('    ', $revResponse){2};
            $mongoClient->mydb->notes->updateOne(
                [
                    '_id' => new ObjectID($id)
                ],
                [
                    '$set' => ['data' => $transcript],
                ]
            );
        } else {
            $mongoClient->mydb->notes->updateOne(
                [
                    '_id' => new ObjectID($id)
                ],
                [
                    '$set' => [
                        'status' => 'JOB_TRANSCRIPTION_FAILURE',
                        'error'  => $json->job->failure_detail,
                    ],
                ]
            );
        }

        return $response->withStatus(200);
    }
);

$app->run();
