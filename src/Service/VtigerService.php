<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;


class VtigerService{
    
    private  $client;
    private  $baseUrl;
    private $username;
    private $accessKey;
    private $sessionName;
    private $id;
    // private $arrayRetrive;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->baseUrl = "https://admin.a-gile.dev/webservice.php";
        $this->username ="youness.bellaouchi@a-gile.com";
        $this->accessKey ="5xq3wNkxNuKq5orC";
        $this->sessionName="7cbc81c2642ae31baee82";
        $this->id="57x26452";
        // $this->arrayRetrive=$this->describe()['result']['fields'];
        
    }
    public function getChallenge(){
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            ["query" => ["operation" => "getchallenge", "username" => $this->username]]
        );
        if (200 == $response->getStatusCode()) {
            $response->getContent();
            $content = $response->toArray();
            return $content['result']['token'];
            // dd($content);
        } else {
            dd($response->getInfo('error'));
        }
    }
    
    public function Login() {

        $token = $this->getChallenge();
        $response = $this->client->request(
        'POST',
        $this->baseUrl, 
         [
            'body' => ['operation' => 'login', 'username'=>$this->username,'accessKey'=> md5($token . $this->accessKey)],
         ]);
        $response->getContent();
        $content = $response->toArray();
        return $content['result']['sessionName'];
        dd($content());
    }

    // 57x26452
    public function retrieveById($id,$elementType) {

        $response = $this->client->request(
        'GET',
        $this->baseUrl,
        [
            'query' => ['operation' => 'retrieve' , 'sessionName' => $this->login(),'id' => $id]
        ]
        );
        if (200 == $response->getStatusCode()) {
            $response->getContent();
            $content = $response->toArray();
            $decribe = $this->describe();
            // dd($content);
            $res = $this->convertNameToLabel($content['result']);
             return $res;
            // return $content['result'];
        } else {
            dd($response->getInfo('error'));
        }
    }
    
    public function describe(){
            $response = $this->client->request(
                'GET',
                $this->baseUrl,
                [
                    'query' => ['operation' => 'describe' , 'sessionName' => $this->login(),'elementType' => 'Projets']
                ]
                );
                if (200 == $response->getStatusCode()) {
                    $response->getContent();
                    $content = $response->toArray();
                        return $content['result']['fields'];
             //   dd(array_search('Nom du projet',$content['result']['fields'][0]));

                } else {
                    dd($response->getInfo('error'));
                }
        }

     
        // elementType, element
        public function convertNameToLabel($elementType, $element)   
        {
            // dd($this->describe($element),$fieldsDesc);
            // describe()
            $keys = array_keys($element);
            $result = [];
            $fieldsDesc = $this->describe($elementType);
            foreach($fieldsDesc as $fieldDesc){
                $x = array_search($fieldDesc['name'], $keys);
                if($x !== false) {
                    $label = $fieldDesc['label'];
                    $result[$label] = $element[$fieldDesc['name']];
                }
            }
           return $result;
        }

        public function convertLabelToName($obj){
            $describes = $this->describe("Projets");
            $keys = array_keys($obj);
            // dd($keys);   
            $result = [];

            // dd($obj);
            foreach ($describes as $describe)
            { 
               $label = $describe['label'];
               $x = array_search($label,$keys);
               if($x !== false)
               {
                //  dd($keys);               
                $name = $describe['name'];
                $result[$name]=$obj[$label];

               }
             
            }
            //  dd($result);
            return $result;
           
        }

        public function Create($elementType, $obj) {
        // dd($this->login());
        // dd($elementType, $obj);
            $response = $this->client->request(
            'POST',
            $this->baseUrl, 
                [
                    'body' => ['operation' => 'create',
                        'sessionName'=> $this->login(),
                        'element' => json_encode($this->convertLabelToName($obj), true),
                        'elementType' => $elementType]
                ]
            );

            $response = json_decode($response->getContent(), true);
            if($response['success']) {
                // return $this->convertNameToLabel($response['result']);
                return $this->convertNameToLabel($elementType, $response['result']);
            }
            
            throw new Exception($response['message']);
        }        

        public function getOperations()
        {
            $response = $this->client->request(
                'GET',
                $this->baseUrl,
                [
                    'query' => ['operation' => 'listtypes' , 'sessionName' => $this->login()]
                ]
                );
                if (200 == $response->getStatusCode()) {
                    $response->getContent();
                    $content = $response->toArray();
                    dd($content);
                    // $res = $this->convertNameToLabel($content['result'], $decribe);
                     return $res;
                    // return $content['result'];
                } else {
                    dd($response->getInfo('error'));
                }
        }
        public function Edit($elementType, $obj)
        {
            $response = $this->client->request(
                'POST',
                $this->baseUrl, 
                    [
                        'body' => ['operation' => 'update',
                            'sessionName'=> $this->login(),
                            'element' => json_encode($this->convertLabelToName($obj), true)],
                    ]
                );

            $response = json_decode($response->getContent(), true);
            // dump($response);
            // die();
            if(false === $response["success"]) {
                throw new Exception($response["message"]);
            }
          
            return $this->convertNameToLabel($elementType, $response['result']);
        }
     public function Delete($id, $obj){

        $response = $this->client->request(
            'POST',
            $this->baseUrl, 
                [
                    'body' => [
                        'operation' => 'delete',
                        'sessionName'=> $this->login(),
                        'id' =>  $id ]
                ]
            );

            $response = json_decode($response->getContent(), true);
            dd($response);
            if($response['success']) {
                dd($response);
                // return $this->convertNameToLabel($response['result']);
                dd($this->convertNameToLabel($elementType,$response['result']));
            }
            
            throw new Exception($response['message']);           
     }
    
}