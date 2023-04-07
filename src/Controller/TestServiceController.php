<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VtigerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Exception;
use Symfony\Component\Dotenv\Dotenv;
 
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
        }
        catch (Exception $exception) {
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
        
    }
    /**
     * @Route("/test/service/login",name="app_test_login")
     */
    public function login(VtigerService $VtigerService): Response
    {
        try {
            $response = new JsonResponse();
            $token = $VtigerService->login();
            $response->setData(["token" => $token]);
            return $response;
        }
        catch (Exception $exception){
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
    //   dd($elementType);
        $data = json_decode($request->getContent(), true);
        // dd($data);
        // $elementType = $data['elementType'];
        // dd($elementType);
        
        $element = $VtigerService->retrieveById($idElement, $elementType);
        $element = $VtigerService->convertNameToLabel($elementType, $element);
        $response = new JsonResponse();
        $response->setData(['element' => $element]);
            return $response;
        }
        catch (Exception $exception){
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
        try
         {
        $data = json_decode($request->getContent(), true);
        $elementType =$request->get('elementType');
        $res = $VtigerService->describe($elementType);
        $response = new JsonResponse($res);
        // $response->setData([''])
            return $response;
        //
        }  
        catch (Exception $exception) {
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
        $idElement = $request->get('id');
        $data = json_decode($request->getContent(), true);
        $elementType = $data['elementType'];
        $data = $data['data'];
        $res = $VtigerService->create($elementType, $data);
        
        $response = new JsonResponse();
        $response->setData(["project added" => $res]);
        return $response;
        }
        catch (Exception $exception) {
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
        try{
            $operations=$VtigerService->getOperations();
            $response = new JsonResponse();
            $response->setData(["operations" => $operations]);
            return $response;
        }
        catch (Exception $exception){
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
        try{
        $data = json_decode($request->getContent(), true);
        $elementType = $data['elementType'];
        
        $data = $data['data'];
        
        $res = $VtigerService->edit($elementType, $data);
        
        $response = new JsonResponse();
            $response->setData(["project edited" => $res]);
            return $response;
        }
        catch (Exception $exception){
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
        
    }
    /**
     * @Route("/test/service/delete", name="app_test_delete")
     */
    public function Delete(VtigerService $VtigerService, Request $request): Response
    { 
        try{
            $idElement = $request->get('id');

            $res = $VtigerService->delete($idElement);
            $response = new JsonResponse();
            $response->setData(["project deleted " => $res]);
            return $response;
        }
        catch (Exception $exception){
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }
    }
   /**
     * @Route("/test/service/get-all", name="app_test_delete")
     */
    public function getAll(VtigerService $VtigerService, Request $request): Response
    { 
        try{
            $response = new JsonResponse();
            $elementType = $request->get('elementType');
            
             $all = $VtigerService->getAll($elementType);
            
           
            $response->setData(["all projects " => $all]);
            return $response;
        }
        catch (Exception $exception){
            $response->setStatusCode(500);
            $response->setData(["message" => $exception->getMessage()]);
            return $response;
        }

}

}