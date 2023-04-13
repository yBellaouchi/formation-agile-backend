<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VtigerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Exception;

class TestServiceController extends AbstractController
{
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $token = $VtigerService->login($currentUser, $accessKey);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
            $element = $VtigerService->retrieveById($elementType, $idElement);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
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
            $data = json_decode($request->getContent(), true);
            $elementType = $data['elementType'];
            $data = $data['data'];
            $response = new JsonResponse();
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
            $all = $VtigerService->getAll($elementType);
            $response->setData(["all projects " => $all]);
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
            $currentUser = $request->get('currentUser');
            $accessKey = $request->get('accessKey');
            $VtigerService->login($currentUser, $accessKey);
            // $filds = ['projetsid','Nom du projet'];
            // $values = ['57x1614','PR87_ADV_VLG'];

            $filds = ['Créé par','Modified By'];
            $values = ['19x1','19x1'];

            $filds_converted = [];
            foreach ($filds as $fild) {
                $fild =  $VtigerService->labelToName($fild);
                array_push($filds_converted, $fild);
            }
             $retrievedBy = $VtigerService->retrieveBy($elementType, $filds_converted, $values);
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
}
