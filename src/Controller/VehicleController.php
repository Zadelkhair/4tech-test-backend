<?php

namespace App\Controller;

use App\Repository\VehiclesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints;

class VehicleController extends AbstractController
{
    #[Route('/vehicles', name: 'vehicles')]
    public function all( VehiclesRepository $vehiclesRepository ): Response
    {

        $vehicles = $vehiclesRepository->findAll();

        // return Response as json
        return $this->json([
            'data' => $vehicles,
            'code' => 200,
            'success' => true
        ]);
        
    }

    // filter 
    #[Route('/vehicles/filter', name: 'vehicle', methods: ['GET'])]
    public function filter( VehiclesRepository $vehiclesRepository ): Response
    {

        //  page is required and is a number
        $validator = Validation::createValidator();
        $violations = $validator->validate(
            $_GET,
            new Constraints\Collection([
                'page' => [
                    new Constraints\NotBlank(),
                    new Constraints\Type('numeric'),
                    new Constraints\Range([
                        'min' => 1
                    ]),
                ],
            ])
        );

        
        // $_GET : array:15 [
        //     "page" => "1"
        //     "limit" => "12"
        //     "between" => array:2 [
        //       0 => "300"
        //       1 => "3200"
        //     ]
        //     "marque" => ""
        //     "modele" => ""
        //     "annee" => ""
        //     "motorisation" => ""
        //     "equipement" => ""
        //   ]

        // database vehicles : 
        // "id": 1,
        // "brand": "Nissan",
        // "model": "JUKE",
        // "version": "1.0 DIG-T 114ch",
        // "year": "2022",
        // "energy": "Essence",
        // "power": 114,
        // "price": 20470,
        // "priceRetail": 26440,
        // "priceMonthly": 500,
        // "pics": [],
        // "gearbox": "manuelle"

        
        // pagination
        $page = $_GET['page'];
        $limit = $_GET['limit'] ?? 12;
        $offset = ($page - 1) * $limit;

        // get vehicles
        $vehicles = $vehiclesRepository;

        $vehicles = $vehicles->createQueryBuilder('v');

        // check if BudgetType is set
        if (isset($_GET['budgetType'])) {
            if ($_GET['budgetType'] == 'Mensuel') {
                $vehicles = $vehicles->andWhere('v.priceMonthly BETWEEN :min AND :max')
                    ->setParameter('min', $_GET['between'][0])
                    ->setParameter('max', $_GET['between'][1]);
            } else {
                $vehicles = $vehicles->andWhere('v.price BETWEEN :min AND :max')
                    ->setParameter('min', $_GET['between'][0])
                    ->setParameter('max', $_GET['between'][1]);
            }
        } else {
            $vehicles = $vehicles->andWhere('v.price BETWEEN :min AND :max')
                ->setParameter('min', $_GET['between'][0])
                ->setParameter('max', $_GET['between'][1]);
        }

        // filter by brand
        if (isset($_GET['marque'])) {
            $vehicles = $vehicles->andWhere('LOWER(v.brand) = :brand')
                ->setParameter('brand', strtolower($_GET['marque']));
        }

        // // filter by model lowercased
        if (isset($_GET['modele'])) {
            $vehicles = $vehicles->andWhere('LOWER(v.model) = :model')
                ->setParameter('model', strtolower($_GET['modele']));
        }

        // // filter by year 
        if (isset($_GET['annee'])) {

            // remove spaces and split by -
            $years = explode('-', str_replace(' ', '', $_GET['annee']));

            // if there is only one year
            if (count($years) == 1) {
                $vehicles = $vehicles->andWhere('v.year = :year')
                    ->setParameter('year', $years[0]);
            } else {
                $vehicles = $vehicles->andWhere('v.year BETWEEN :startyear AND :endyear')
                    ->setParameter('startyear', $years[0])
                    ->setParameter('endyear', $years[1]);
            }
        }

        // filter by motorisation
        if (isset($_GET['motorisation'])) {
            $vehicles = $vehicles->andWhere('LOWER(v.energy) = :energy')
                ->setParameter('energy', strtolower($_GET['motorisation']));
        }

        // sort
        if (isset($_GET['sort'])) {
            // split $_GET['sort'] by ' '
            $sort = explode(' ', $_GET['sort']);


            // if the length ==2
            if (count($sort) == 2) {
                // if the second element is ASC or DESC
                if ($sort[1] == 'asc' || $sort[1] == 'desc') {
                    // if the first element is price or priceMonthly
                    if ($sort[0] == 'price') {
                        if(isset($_GET['budgetType'])){
                            if ($_GET['budgetType'] == 'Mensuel') {
                                $vehicles = $vehicles->orderBy('v.priceMonthly', $sort[1]);
                            } else {
                                $vehicles = $vehicles->orderBy('v.price', $sort[1]);
                            }
                        }
                        else {
                            $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                        }
                    }
                    else if ($sort[0] == 'year') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    else if ($sort[0] == 'power') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    else if ($sort[0] == 'brand') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    else if ($sort[0] == 'model') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    else if ($sort[0] == 'version') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    else if ($sort[0] == 'energy') {
                        $vehicles = $vehicles->orderBy('v.' . $sort[0], $sort[1]);
                    }
                    
                }
            }

        }

        // select count(*) from vehicles
        $count = $vehicles->select('count(v.id)')->getQuery()->getSingleScalarResult();

        // dd $count generated query with params
        // dd($vehicles->select('count(v.id)')->getQuery()->getSQL(), $vehicles->select('count(v.id)')->getQuery()->getParameters());

        // select * from vehicles limit 12 offset 0
        $vehicles = $vehicles->select('v')->setMaxResults($limit)->setFirstResult($offset)->getQuery()->getResult();


        // count pages
        $pages = ceil($count / $limit);


        // return Response as json
        return $this->json([
            'data' => [
                'data' => $vehicles,
                'page' => $page,
                'limit' => $limit,
                'offset' => $offset,
                'count' => $count,
                'pages' => $pages,
            ],
            'code' => 200,
            'success' => true
        ]);
        
    }
    
}
