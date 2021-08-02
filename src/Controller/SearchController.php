<?php

namespace App\Controller;

use App\Form\SearchingType;
use App\Service\GetApiDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SearchController extends AbstractController
{
    private $getApiDataService;

    public function __construct(GetApiDataService $getApiDataService)
    {
        $this->getApiDataService = $getApiDataService;
    }
    /**
     * @Route("/", name="index", methods={"GET","POST"})
     */
    public function index(Request $request): Response
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjlSWVRsc0dzQkJUZ2RTdTdYUG9JM2V5NzB4RXZqa0hGRmNQU1drOGFwWVptRnpqTmRkeWN2ZWpmYVRnQXRmVFUyMDkiLCJzdWIiOjIwOSwiaXNzIjoiaHR0cHM6Ly9wcmVwcm9kLnN0YXJpZi5jcmlzdGFsY3JtLmZyIiwiaWF0IjoxNjAzOTYxMjk0LCJleHAiOjQ3NTc1NjEyOTQsIm5iZiI6MTYwMzk2MTI5NCwianRpIjoiV0M3UzlJMmxkZmZCcEFuVyJ9.2lv_XQZk8PXUEMhpz6mDs-C02FcRRKTjz06ys3zsioU";
        // 
        if ($request->isXmlHttpRequest()) {
            $query = [];

            $search = trim($request->request->get('searching')['recherche']);
            $marque = trim($request->request->get('searching')['marque']);

            if(strlen($search) > 0 && strlen($marque) > 0){
                $query['search'] = $search." ".$marque;
            }elseif(strlen($search) > 0 && strlen($marque) == 0){
                $query['search'] = $search;
            }elseif(strlen($search) == 0 && strlen($marque) > 0){
                $query['search'] = $marque;
            }
            $familles = [];
            if(isset($request->request->get('searching')['familles']) && strlen($request->request->get('searching')['familles']) > 0){
                $familles = explode(" ",$request->request->get('searching')['familles']);
            }
            $sousFamilles = [];
            if(isset($request->request->get('searching')['sousFamilles'])){
                $sousFamilles = explode(" ",implode(" ",$request->request->get('searching')['sousFamilles']));                    
            }
            $types = array_merge($familles,$sousFamilles);
            $types = array_unique($types);
            if(count($types) > 0){
                $query['types'] = $types;
            }
            
            $query['limit'] = 100;
            $query['token'] = $token;
            $materielsResponse = $this->getApiDataService->getMateriels($query);
            $materiels = [];
            for($i = 0;$i < count($materielsResponse);$i++) {
                for($j = 0;$j < count($materielsResponse[$i]);$j++) {
                    $materiels[] = $materielsResponse[$i][$j];
                }
            }

            return new JsonResponse([
                'status' => 'success',
                'materiels' => $materiels]);

        }else{
            
            $familles = [];
            $sousFamilles = [];
            $marques = [];

            $typesResponse = $this->getApiDataService->getTypes($token);
            $query['limit'] = 100;
            $query['token'] = $token;
            $marquesResponse = $this->getApiDataService->getMateriels($query);
            foreach ($typesResponse as $resp) {
                foreach ($resp as $elm) {
                    if(!key_exists($elm['famille'],$familles)){
                        $familles[$elm['famille']] = (string)$elm['type_id'];
                    }else{
                        $familles[$elm['famille']] .= " ".$elm['type_id'];
                    }
                    if (!key_exists($elm["famille"], $sousFamilles)) {
                        $sousFamilles[$elm["famille"]] = [];
                            $sousFamilles[$elm["famille"]][$elm["nom"]] = (string)$elm["type_id"];
                    }else{
                        if(!key_exists($elm["nom"],$sousFamilles[$elm["famille"]])){
                            $sousFamilles[$elm["famille"]][$elm["nom"]] = (string)$elm["type_id"];
                        }else{
                            $sousFamilles[$elm["famille"]][$elm["nom"]] .= " ".$elm["type_id"];
                        }
                    }
                }
            }

            foreach ($marquesResponse as $resp) {
                foreach ($resp as $elm) {
                    if (!in_array($elm["marque"], $marques)) {
                        $marques[] = $elm["marque"];
                    }
                }
            }
            
            $searchForm = $this->createForm(SearchingType::class,null,[
                'sousFamilles'=> $sousFamilles,
                'familles'=> $familles,
                'marques' => $marques,
            ]);

            return $this->render('search/index.html.twig', [
                'searchForm' => $searchForm->createView()
            ]);
        }
    }

    /**
     * @Route("/materiel/{id}", name="materiel_details",methods={"GET"}, requirements={"id": "\d+"})
     */
    public function materielDetails($id)
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjlSWVRsc0dzQkJUZ2RTdTdYUG9JM2V5NzB4RXZqa0hGRmNQU1drOGFwWVptRnpqTmRkeWN2ZWpmYVRnQXRmVFUyMDkiLCJzdWIiOjIwOSwiaXNzIjoiaHR0cHM6Ly9wcmVwcm9kLnN0YXJpZi5jcmlzdGFsY3JtLmZyIiwiaWF0IjoxNjAzOTYxMjk0LCJleHAiOjQ3NTc1NjEyOTQsIm5iZiI6MTYwMzk2MTI5NCwianRpIjoiV0M3UzlJMmxkZmZCcEFuVyJ9.2lv_XQZk8PXUEMhpz6mDs-C02FcRRKTjz06ys3zsioU";

        $materialDetails = $this->getApiDataService->getMaterielDetails($id,$token);
        return $this->render('search/materielDetails.html.twig', [
            'materielDetails' => $materialDetails
        ]);
    }


    /**
     * @Route("/super-materiels/{id}", name="super_materiels",methods={"GET"}, requirements={"id": "\d+"})
     */
    public function superMateriels($id)
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjlSWVRsc0dzQkJUZ2RTdTdYUG9JM2V5NzB4RXZqa0hGRmNQU1drOGFwWVptRnpqTmRkeWN2ZWpmYVRnQXRmVFUyMDkiLCJzdWIiOjIwOSwiaXNzIjoiaHR0cHM6Ly9wcmVwcm9kLnN0YXJpZi5jcmlzdGFsY3JtLmZyIiwiaWF0IjoxNjAzOTYxMjk0LCJleHAiOjQ3NTc1NjEyOTQsIm5iZiI6MTYwMzk2MTI5NCwianRpIjoiV0M3UzlJMmxkZmZCcEFuVyJ9.2lv_XQZk8PXUEMhpz6mDs-C02FcRRKTjz06ys3zsioU";

        $materielDetails = $this->getApiDataService->getMaterielDetails($id,$token);
        $superMateriels = null;
        if(count($materielDetails['liens']) > 0){
            $liens = $materielDetails['liens'];
            $ids = [];
            foreach($liens as $lien){
                $ids[] = $lien['option_id'];
            }
            $query['ids'] = $ids;
            $query['limit'] = 2;
            $query['token'] = $token;
            $superMateriels = $this->getApiDataService->getMateriels($query);
        }
        return $this->render('search/superMateriels.html.twig', [
            'superMateriels' => $superMateriels
        ]);

    }
}