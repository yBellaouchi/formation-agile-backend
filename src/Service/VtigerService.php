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
    
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
        $this->baseUrl = $_SERVER['BASE_URL'];
        $this->username = $_SERVER['USER_NAME'];
        $this->accessKey = $_SERVER['ACCESS_KEY'];

        $this->sessionName = $_SERVER['SESSION_NAME'];
        $this->id = $_SERVER['ID'];
        
    }
    public function getChallenge()
    {
        // $cache = $this->get('cache.app');
        $response = $this->client->request(
            'GET',
            $this->baseUrl,
            ["query" => [
                "operation" => "getchallenge",
                 "username" => $this->username
                 ]
            ]
        );
        $response = json_decode($response->getContent(), true);
        if($response['success']) {
           return $response['result']['token'];
        }

        throw new Exception($response['error']['message']); 
    }
    
    public function Login() {

        $token = $this->getChallenge();
        $response = $this->client->request(
        'POST',
        $this->baseUrl, 
         [
            'body' => [
            'operation' => 'login', 
            'username' =>$this->username,
            'accessKey' => md5($token . $this->accessKey)
            ],
         ]);
        $response->getContent();
        $response = $response->toArray();
        if($response['success']) 
        {
            return $response['result']['sessionName'];
        }
         throw new Exception($response['error']['message']);
    }

    public function retrieveById($id, $elementType)
     {
        $response = $this->client->request(
        'GET',
        $this->baseUrl,
        [
            'query' => [
            'operation' => 'retrieve' ,
            'sessionName' => $this->login(),
            'id' => $id
             ]
        ]
        );
        $response = json_decode($response->getContent(), true);
        if($response['success'])
         {
            // dd($elementType);
            $res = $response['result'];
            // $res = $this->convertNameToLabel($elementType, $response['result']);
            return $res;
        }
        throw new Exception($response['message']);  
      }
    
    public function describe($elementType)
    {
            $response = $this->client->request(
                'GET',
                $this->baseUrl,
                [
                    'query' => [
                    'operation' => 'describe' ,
                    'sessionName' => $this->login(),
                    'elementType' => $elementType
                     ]
                ]
                );
                $response = json_decode($response->getContent(), true);

                if($response['success'])
                {
                     return $response['result']['fields'];
                } else {
                     $response['error']['message'];
                }
        }
        public function convertNameToLabel($elementType, $element)   
        {
            $keys = array_keys($element);
            $result = [];
            $fieldsDesc = $this->describe($elementType);
            // dd($element);
            // dd($fieldsDesc);

            foreach($fieldsDesc as $fieldDesc)
            {
                // dd($fieldDesc);
                $x = array_search($fieldDesc['name'], $keys);
                if($x !== false) 
                {
                    $label = $fieldDesc['label'];
                    $result[$label] = $element[$fieldDesc['name']];
                }
            }
           return $result;
        }

        public function convertLabelToName($obj,$elementType)
        {
            $describes = $this->describe($elementType);
            // dd($describes);

            $keys = array_keys($obj);  
            $result = [];

            foreach ($describes as $describe)
            { 
               $label = $describe['label'];
               $x = array_search($label,$keys);  
               if($x !== false)
               {                       
                $name = $describe['name'];
                $result[$name] = $obj[$label];
               }    
            //  dd($result);
             }
            //  dd($result);
            return $result;
          }

        public function create($elementType, $obj) 
        {
            $response = $this->client->request(
            'POST',
             $this->baseUrl, 
                [
                    'body' => [
                        'operation' => 'create',
                        'sessionName'=> $this->login(),
                        'element' => json_encode($this->convertLabelToName($obj,$elementType), true),
                        'elementType' => $elementType
                        ]
                ]
            );
            $response = json_decode($response->getContent(), true);

            if($response['success']) {
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
                    'query' =>  [
                    'operation' => 'listtypes' ,
                    'sessionName' => $this->login()
                     ]
                ]
                );
                $response = json_decode($response->getContent(), true);
                if($response['success']) {
                    return $response;
                }
                 throw new Exception($response['message']);
        }
        public function edit($elementType, $obj)
        {
            $response = $this->client->request(
               'POST',
                $this->baseUrl, 
                    [
                        'body' => [
                            'operation' => 'update',
                            'sessionName'=> $this->login(),
                            'element' => json_encode($this->convertLabelToName($obj,$elementType), true)],
                    ]
                );

            $response = json_decode($response->getContent(), true);

            if(false === $response["success"]) {
                throw new Exception($response["message"]);
            }
            return $this->convertNameToLabel($elementType, $response['result']);
        }

        public function delete($id)
        {
        $response = $this->client->request(
            'POST',
            $this->baseUrl, 
                [
                    'body' => [
                        'operation' => 'delete',
                        'sessionName'=> $this->login(),
                        'id' =>  $id 
                        ]
                ]
            );
        $response = json_decode($response->getContent(), true);
         
            if($response['success']) {
                return $response; 
            }
             throw new Exception($response['message']);           
     }

     public function getAll($elementType){
        $query = "SELECT * FROM {$elementType} LIMIT 10;";
        // $query = "SELECT * FROM {$elementType} WHERE id = '57x26835' ;";
        $response = $this->client->request(
            'GET',
            $this->baseUrl, 
                [
                    'query' => [
                        'operation' => 'query',
                        'sessionName'=> $this->login(),
                        'query' =>  $query
                        ]
                ]
            );
            // global $elementType;
            // dd($elementType);
            $response = json_decode($response->getContent(), true);
            if($response['success']) {
                $elements = $response['result'];
                // dd($elements);
                // foreach($elements as $element){
                //      $this->convertNameToLabel($elementType, $element);
                // }
                $elements = array_map(function($element) {
                    // global $elementType;
                    // dd($elementType);
                    return $this->convertNameToLabel('Projets', $element);
                }, $elements);
                return $elements;
            }
             throw new Exception($response['message']);   
     }
    // changeLabelToName
      public function labelToName($label){
        $elements = $this->describe('Projets'); 
        // dd($elements);
        foreach($elements as $element){
            if(array_search($label,$element) == "label"){
                // dd($element);
              return $element["name"];
            }

        }
      }
     public function retrieveBy($elementType, $fields, $values){
     
        // $query = "SELECT * FROM {$elementType} LIMIT 10;";
        // $query = "SELECT * FROM {$elementType} WHERE id = '57x1614' AND name ='PR87_ADV_VLG';";
        $arr = [];

        for($i = 0; $i< count($fields); $i++){
            $arr[$fields[$i]] = $values[$i];
        }
        foreach ($arr as $key => $value) {
            $stmt[] = "$key"."="."'$value'";
        }
        $stmt = implode(" And ", $stmt);

        $query = "SELECT * FROM {$elementType} WHERE $stmt LIMIT 10 ;";

        $response = $this->client->request(
            'GET',
            $this->baseUrl, 
                [
                    'query' => [
                        'operation' => 'query',
                        'sessionName'=> $this->login(),
                        'query' =>  $query
                        ]
                ]
            );
            $response = json_decode($response->getContent(), true);
            if($response['success']) {
                $elements = $response['result'];
                return $elements;
            }
             throw new Exception($response['message']);  
     }
    
}