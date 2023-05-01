<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VtigerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Publisher;
use Symfony\Component\Mercure\Update;

class TestServiceController extends AbstractController
{
    /**
     * @Route("/test/service/mercure", name="app_test_service_mercure")
     */
    public function mercure(VtigerService $VtigerService): Response
    {
        $response = $this->render('/test_service/index.html.twig', [
            'controller_name' => 'MercureController',
        ]);
        // $response->headers->set('set-cookie', $cookieGenerator->generate());
        //$response->headers->set('Authorization', 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtZXJjdXJlIjp7InB1Ymxpc2giOlsiKiJdfX0.obDjwCgqtPuIvwBlTxUEmibbBf0zypKCNzNKP7Op2UM');
        return $response;
    }
    /**
     * @Route("/test/service", name="app_test_service")
     */
    public function getChallenge(VtigerService $VtigerService): Response
    {
        try {
            $response = new JsonResponse();
            $challenge = $VtigerService->getChallenge();
            $response->setData(["challenge" => $challenge]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/login",name="app_test_login")
     */
    public function login(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $response = new JsonResponse();
            $token = $VtigerService->login();
            $response->setData(["token" => $token]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/retrieve",name="app_test_retreive")
     */
    public function retrieveById(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $response = new JsonResponse();
            $idElement = $request->get('id');
            $elementType = $request->get('elementType');
            $VtigerService->login();
            $selectedFields = ['Nom du projet' , 'Assigné à','Created At', 'Modified At'];
            $element = $VtigerService->retrieveById($elementType, $idElement, $selectedFields);
            $response = new JsonResponse();
            $response->setData(['element' => $element]);
                return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/describe",name="app_test_describe")
     */
    public function describe(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $elementType = $request->get('elementType');
            $VtigerService->login();
            $res = $VtigerService->describe($elementType);
            $response = new JsonResponse($res);
                return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }

    /**
     * @Route("/test/service/create",name="app_test_create")
     */
    public function create(VtigerService $VtigerService, Request $request)
    {
        try {
            $response = new JsonResponse();
            $data = json_decode($request->getContent(), true);
            $elementType = $data['elementType'];
            $data = $data['data'];
            $VtigerService->login();
            $res = $VtigerService->create($elementType, $data);
            $response->setData(["project added" => $res]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/getOperations", name="app_test_get_operations")
     */
    public function getOperation(VtigerService $VtigerService): Response
    {
        try {
            $operations = $VtigerService->getOperations();
            $response = new JsonResponse();
            $response->setData(["operations" => $operations]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/edit", name="app_test_edit")
     */
    public function edit(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $data = json_decode($request->getContent(), true);
            $elementType = $data['elementType'];
            $data = $data['data'];
            $response = new JsonResponse();
            $VtigerService->login();
            $res = $VtigerService->edit($elementType, $data);
            $response->setData(["project edited" => $res]);
                return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/delete", name="app_test_delete")
     */
    public function delete(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $idElement = $request->get('id');
            $response = new JsonResponse();
            $VtigerService->login();
            $res = $VtigerService->delete($idElement);
            $response->setData(["project deleted " => $res]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/get-all", name="app_test_get_all")
     */
    public function getAll(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $response = new JsonResponse();
            $elementType = $request->get('elementType');
            $VtigerService->login();

            $selectedFields = ['Nom du projet' , 'Assigné à','Created At', 'Modified At'];

            $dateInterval = 6;
            $dateType = 'd';
            $all = $VtigerService->getAll($elementType, $selectedFields, $dateInterval, $dateType);
            $response->setData(["all projects" => $all]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/retrieveBy", name="app_test_retrive_by")
     */
    public function retrieveBy(VtigerService $VtigerService, Request $request): Response
    {
        try {
            $response = new JsonResponse();
            $elementType = $request->get('elementType');
            $VtigerService->login();
            // $fields = ['Créé par','Nom du projet'];
            // $values = ['19x1','PR87_ADV_VLG'];

            $fields = ['Créé par','Modified By'];
            $values = ['19x1','19x1'];

            $selectedFields = ['Nom du projet' , 'Assigné à','Created At', 'Modified At'];

            $dateInterval = 2;
            $dateType = 'm';

            $retrievedBy = $VtigerService->retrieveBy(
                $elementType,
                $fields,
                $values,
                $selectedFields,
                $dateInterval,
                $dateType
            );
             $retrivedByArrays = [];
            foreach ($retrievedBy as $rv) {
                $element = $VtigerService->convertNameToLabel($elementType, $rv);
                array_push($retrivedByArrays, $element);
            }
            $response->setData(["retrived by arrays " => $retrivedByArrays]);
            return $response;
        } catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
    /**
     * @Route("/test/service/publish", name="app_test_publish")
     */
    public function publish(HubInterface $hub): Response
    {
        $update = new Update(
            'chat',
            json_encode(['status' => 'message recus'])
        );
        // $publisher($update);
        $hub->publish($update);

        return new JsonResponse(['status' => 'message recus']);
    }
}
