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
        // Le token doit être dynamique c'est à dire qu'on donne à l'utilisateur la possibilité de le changer pour des raisons de sécurité, de permissions ... etc 
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjlSWVRsc0dzQkJUZ2RTdTdYUG9JM2V5NzB4RXZqa0hGRmNQU1drOGFwWVptRnpqTmRkeWN2ZWpmYVRnQXRmVFUyMDkiLCJzdWIiOjIwOSwiaXNzIjoiaHR0cHM6Ly9wcmVwcm9kLnN0YXJpZi5jcmlzdGFsY3JtLmZyIiwiaWF0IjoxNjAzOTYxMjk0LCJleHAiOjQ3NTc1NjEyOTQsIm5iZiI6MTYwMzk2MTI5NCwianRpIjoiV0M3UzlJMmxkZmZCcEFuVyJ9.2lv_XQZk8PXUEMhpz6mDs-C02FcRRKTjz06ys3zsioU";

        // Quand le formulaire de recherche est envoyé on le traite en Ajax pour éviter d'interroger l'api à chaque fois sans raison.
        // Sinon on change l'action du formulaire pour qu'il soit traité par un autre controller ou une autre function 
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
            
            // La première fois qu'on arrive sur le formulaire on doit interroger l'api pour récupérer tous les types et les marques afin de peupler les champs select. Pour récupérer les marques, on est obligé de récuperer tous les matériels pour récupérer la marque de chacun d'eux parce que dans la base de données il n'y a pas une entité marque (table) mais un champs marque dans la table matériel(entité matériel) ce qui est très couteux au niveau des performances (je n'imagine pas Amazon faire ça)
            $familles = [];
            $sousFamilles = [];
            $marques = [];

            // Notre GitApiDataService service nous retourne tous les types
            $typesResponse = $this->getApiDataService->getTypes($token);
            $query['limit'] = 100;
            $query['token'] = $token;
            // Notre GitApiDataService service nous retourne tous les matériels afin de récupérer leur marque
            $marquesResponse = $this->getApiDataService->getMateriels($query);

            foreach ($typesResponse as $resp) {
                foreach ($resp as $elm) {
                    // On récupère la famille de chaque type et son id
                    if(!key_exists($elm['famille'],$familles)){
                        $familles[$elm['famille']] = (string)$elm['type_id'];
                    }else{
                        //  On concatine les ids pour former une chaine de caractères(string)
                        //  On aurait pu utilise la function json_encode() ça marche aussi 
                        $familles[$elm['famille']] .= " ".$elm['type_id'];
                    }
                    // J'ai remarqué que les types ont un champ famille mais aussi un champ nom.
                    // J'ai alors décidé d'en profiter et de créer un autre select avec des options groupées
                    // Explications : dans le formulaire de recherche on peu soit selectionner une famille exemple "autres"
                    // soit une partie de la famille "autres" comme "Ajustement, copieur, Cadeau" séparrément pour des résultats plus précis
                    //  et ce n'est pas tout, on peut même combiner avec une partie de la famille autres exemple "Ajustement" avec une autre partie d'une autre famille comme "finitions" de la famille option 
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
            // On récupère toutes les marques des produits pour construire un tableau
            foreach ($marquesResponse as $resp) {
                foreach ($resp as $elm) {
                    if (!in_array($elm["marque"], $marques)) {
                        $marques[] = $elm["marque"];
                    }
                }
            }
            
            // On envoie les types et les marques que l'on vient de récupérer pour les injecter dans les options des champs selects.
            // Une autre façon de faire est de générer le formulaire avec des champs select mais sans options.
            // Avec javascript on interroge l'API pour récupérer les types et les marques pour les injecter dans les selects côté front. 
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
     *  Interroge l'API pour récupérer les détails d'un matériel avec son id
     * Return les infos détaillées d'un materiel
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
     * Interroge l'API pour récupérer les détails d'un materiel avec un id
     * Puis s'il y a des liens on récupère des matériels avec leur ids
     * return une liste de matériels | null
     * @Route("/super-materiels/{id}", name="super_materiels",methods={"GET"}, requirements={"id": "\d+"})
     */
    public function superMateriels($id)
    {
        $token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0b2tlbiI6IjlSWVRsc0dzQkJUZ2RTdTdYUG9JM2V5NzB4RXZqa0hGRmNQU1drOGFwWVptRnpqTmRkeWN2ZWpmYVRnQXRmVFUyMDkiLCJzdWIiOjIwOSwiaXNzIjoiaHR0cHM6Ly9wcmVwcm9kLnN0YXJpZi5jcmlzdGFsY3JtLmZyIiwiaWF0IjoxNjAzOTYxMjk0LCJleHAiOjQ3NTc1NjEyOTQsIm5iZiI6MTYwMzk2MTI5NCwianRpIjoiV0M3UzlJMmxkZmZCcEFuVyJ9.2lv_XQZk8PXUEMhpz6mDs-C02FcRRKTjz06ys3zsioU";
        // On récupère les détails d'un matériel 
        $materielDetails = $this->getApiDataService->getMaterielDetails($id,$token);
        $superMateriels = null;
        // On vérifie l'existence des liens, si oui on récupère leur ids puis on interroge l'API à nouveau 
        // pour récupérer une liste de matériels définie par leur ids
        if(count($materielDetails['liens']) > 0){
            $liens = $materielDetails['liens'];
            $ids = [];
            foreach($liens as $lien){
                $ids[] = $lien['option_id'];
            }
            $query['ids'] = $ids;
            $query['limit'] = 2;
            $query['token'] = $token;
            // On récupère une liste de matériels définie par leur ids
            $superMateriels = $this->getApiDataService->getMateriels($query);
        }
        return $this->render('search/superMateriels.html.twig', [
            'superMateriels' => $superMateriels
        ]);

    }
}