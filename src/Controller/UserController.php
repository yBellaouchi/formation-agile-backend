<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends AbstractController
{
    private $manager;
    private $user;
    public function __construct(EntityManagerInterface $manager, UserRepository $user)
    {
        $this->manager = $manager;
        $this->user = $user;
    }
    /**
     * @Route("/user", name="app_user")
     */
    public function index(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'];
        $password = $data['password'];
        $email_exist = $this->user->findOneByEmail($email);
        if ($email_exist) {
            return new JsonResponse(
                [
                    'status' => false ,
                    'messsage' => 'user exist'
                ]
            );
        }
        $user = new User();
        $user->setEmail($email);

        $user->setPassword(sha1($password));
        $this->manager->persist($user);
        $this->manager->flush();

        return new JsonResponse(
            [
                'status' => true ,
                'messsage' => 'user added successfuly'
            ]
        );
    }
    /**
     * @Route("/getAllUser", name="app_get_user")
     */
    public function getAll(): Response
    {
        $users = $this->user->findAll();
        // dd($users);
        foreach ($users as $key => $value) {
            $arr[$key]['id'] = $value->getId();
            $arr[$key]['email'] = $value->getEmail();
            foreach ($value->getRoles() as $role) {
                $arr[$key]['roles'] = $role;
            }
            $arr[$key]['password'] = $value->getPassword();
        }
        $x = serialize($users);
        // $z = json_encode($users);
        return new JsonResponse(
            [
                'status' => true ,
                'users' => $arr
                // 'users' => serialize($x)
            ]
        );
    }
}
