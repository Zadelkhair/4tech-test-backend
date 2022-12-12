<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/{param}', name: 'test',defaults : ['param'=>'no param'] , methods: ['GET'])]
    public function index($param): Response
    {
        // return json response
        return $this->json([
            // return the param
            'param' => $param,
        ]);
    }
}
