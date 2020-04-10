<?php
/**
 * Created by PhpStorm.
 * User: chane
 * Date: 09/04/2020
 * Time: 15:10
 */

namespace App\Controller;
// src/Controller/LuckyController.php

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Annotation\Route;

class LuckyController extends AbstractController
{
    /**
    * @Route("/lucky/number")
    */
    public function number()
    {
        // this looks exactly the same

        $number = random_int(0, 100);

        return $this->render('lucky/number.html.twig', [
            'number' => $number,
        ]);
    }
}