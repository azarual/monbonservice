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

  /**
   * Interroge l'API pour récupérer tous les types
   */
  public function getTypes($token)
  {
    // Une première requête vers l'API pour récupérer les types
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


    // S'il y a encore plus de types alors on interroge l'Api soit en testant la présence de champs ['next_page_url'] avec la boucle while
    //  soit en utilisant la technique lazy responsese: on interroge l'API x fois sans attendre de recevoir les réponses dans l'ordre d'envoi et à chaque fois qu'une réponse arrive on la garde dans la table $responses sans traitement 
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

  /**
   * Interroge l'API pour récupérer des matériels selon les infos dans le query
   */
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

  /**
   * Interroge l'API pour récupérer les détails d'un matériel identifié par son id
   */
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
