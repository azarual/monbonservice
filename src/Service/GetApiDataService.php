<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;


class GetApiDataService
{

  private $httpClient;

  public function __construct(HttpClientInterface $httpClient)
  {
      $this->httpClient = $httpClient;
  }

  public function getTypes($token)
  {
    $url = "https://preprod.starif.cristalcrm.fr/api/types";
    $response = $this->httpClient->request('GET',$url,[
        'query' => [
          'limit' => 100,
          'token' => $token
        ]
      ]
    );
    $responses[] = $response->toArray()['data'];
    // while ($response->toArray()['next_page_url']) {
    //     $response = $this->httpClient->request('GET', $response->toArray()['next_page_url'], [
    //       'query' => [
    //         'limit' =>100,
    //         'token' => $token
    //       ]
    //     ]);
    //     $responses[] =  $response;
    //   }
    if($response->toArray()['next_page_url']){
      for($i= 2;$i<= $response->toArray()['last_page'];$i++){
        $response = $this->httpClient->request('GET', $url, [
          'query' => [
            'limit' =>100,
            'token' => $token,
            'page' =>$i
          ]
        ]);
      $responses[] =  $response->toArray()['data'];
      }
    }

    return $responses;
  }

  public function getMateriels(array $query)
  {
        
    $response = $this->httpClient->request('GET',"https://preprod.starif.cristalcrm.fr/api/materiels",[
        'query' => $query
      ]
    );
    
    $responses[] = $response->toArray()['data'];

    // while ($response->toArray()['next_page_url']) {
    //   $response = $this->httpClient->request('GET', $response->toArray()['next_page_url']);
    //   $responses[] =  $response->toArray()['data'];
    // }
    if($response->toArray()['next_page_url']){
      for($i = 2; $i <= $response->toArray()['last_page'];$i++){
        $query['page'] = $i;
        $response = $this->httpClient->request('GET', "https://preprod.starif.cristalcrm.fr/api/materiels",[
            'query' => $query
        ]);
        $responses[] = $response->toArray()['data'];
      }
    }
    
    return $responses;
  }

  public function getMaterielDetails($id, $token)
  {
    $response = $this->httpClient->request('GET',"https://preprod.starif.cristalcrm.fr/api/materiel/".$id,[
      'query' =>[
        'token' => $token
      ]
    ]
    );
    return $response->toArray();
  }
  
}
