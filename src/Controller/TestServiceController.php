<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\VtigerService;

class TestServiceController extends AbstractController
{
    /**
     * @Route("/test/service", name="app_test_service")
     */
    public function getChallenge(VtigerService $VtigerService): Response
    {      
        dd($VtigerService->getChallenge());
        return $this->render('test_service/index.html.twig', [
            'controller_name' => 'TestServiceController',
        ]);
    }
    /**
     * @Route("/test/service/login",name="app_test_login")
     */
    public function login(VtigerService $VtigerService): Response
    {
        dd($VtigerService->login());
        return $this->render('test_service/index.html.twig', [
            'controller_name' => 'TestServiceController',
        ]);
    }
    /**
     * @Route("/test/service/retrieve",name="app_test_retreive")
     */
    public function retrieveById(VtigerService $VtigerService, Request $request): Response
    {
        $idElement = $request->get('id');
        $elment = $VtigerService->retrieveById($idElement);
        return $this->json($elment);
    }

    /**
     * @Route("/test/service/describe",name="app_test_describe")
     */
    public function describe(VtigerService $VtigerService, Request $request): Response
    {
        dd($VtigerService->convertNameToLabel($VtigerService->retrieveById("57x26452"),$VtigerService->describe()));
        dd($VtigerService->describe());
        return $this->render('test_service/index.html.twig', [
            'controller_name' => 'TestServiceController',
        ]);
    }
    /**
     * @Route("/test/service/create",name="app_test_create")
     */
    public function create(VtigerService $VtigerService, Request $request)
    {
        // dd($VtigerService->convertNameToLabel($VtigerService->retrieveById("57x26452"),$VtigerService->describe()));
        $idElement = $request->get('id');
        $data = json_decode($request->getContent(), true);
        // dd($data);
        $elementType = $data['elementType'];
        $data = $data['data'];
        // dd($data,$elementType)
        $res = $VtigerService->create($elementType,$data);
      
        return $this->json($res);
    }
/**
     * @Route("/test/service/getOperations", name="app_test_get_operations")
     */
    public function getOperation(VtigerService $VtigerService): Response
    {
        dd($VtigerService->getOperations());
        return $this->render('test_service/index.html.twig', [
            'controller_name' => 'TestServiceController',
        ]);
    }
    /**
     * @Route("/test/service/edit", name="app_test_edit")
     */
    public function Edit(VtigerService $VtigerService, Request $request): Response
    {
       
        $data = json_decode($request->getContent(), true);
        $elementType = $data['elementType'];
        
        $data = $data['data'];
        
        $res = $VtigerService->Edit($elementType, $data);
        return $this->json($res);
    }
    /**
     * @Route("/test/service/delete", name="app_test_delete")
     */
    public function Delete(VtigerService $VtigerService, Request $request): Response
    { 
        $data = json_decode($request->getContent(), true);
        $idElement = $request->get('id');
        
        $data = $data['data'];
        
        $res = $VtigerService->delete($idElement, $data);
        return $this->json($res);
    }

}
