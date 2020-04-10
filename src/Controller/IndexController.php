<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;


class IndexController extends AbstractController
{

    /**
     * @Route("/index", name="index")
     */
    public function index(UserRepository $repo)
    {

        $users = $repo->findAll();

        $status = false;

        $phrase1 = array('');

        $phrase2 = array('');

//        dump($users);

        return $this->render('index/index.html.twig', [
            'controller_name' => 'IndexController',
            'user' => $users,
            'phrase1' => $phrase1,
            'phrase2' => $phrase2,
            'status' => $status


        ]);
    }


    /**
     * @Route("/anagram", name="admin_ajax_anagram")
     * @return Response
     */
    function is_anagram(Request $request, UserRepository $repo){

        $users = $repo->findAll();

        $status = false;
        if ($request->isXmlHttpRequest()) {
//            $date1 = $request->query->get('date1');
//            $date2 = $request->query->get('date2');
            $status = $request->query->get('status');
            $users = $request->query->get('user');
            $phrase1 = $request->query->get('phrase1');
            $phrase2 = $request->query->get('phrase2');
//
            dump($status);
            dump($phrase1);
            dump($phrase2);
            die;
//        $phrase1 = 'Balle de mon chien';
//
//        $phrase2 = 'Lable monde niche';

            $phrase1->$request->request->get('phrase1');

            $phrase2->$request->request->get('phrase2');

            if ($phrase1 && $phrase2) {
                $phrase1 = strtolower(str_replace(" ", "", $phrase1));
                $phrase2 = strtolower(str_replace(" ", "", $phrase2));
                $phrase1 = str_split($phrase1);
                $phrase2 = str_split($phrase2);
                sort($phrase1);
                sort($phrase2);
                if ($phrase1 === $phrase2) {
                    $status = true;
                }
            }
            dump($status);
            dump($phrase1);
            dump($phrase2);
        die;

//        return $status;

            return $this->render('users/template_anagram.html.twig', [
                'controller_name' => 'IndexController',
                'user' => $users,
                'phrase1' => $phrase1,
                'phrase2' => $phrase2,
                'status' => $status

            ]);
        }
    }

    /**
     * @Route("ajax/anagramOld", name="admin_ajax_anagramOld", options={"expose"=true})
     */
    public function ajaxUsersListAction(Request $request)
    {
        set_time_limit(60000);
        ini_set("memory_limit", -1);

        if ($request->isXmlHttpRequest()) {
//            $date1 = $request->query->get('date1');
//            $date2 = $request->query->get('date2');
            $status = $request->query->get('status');
            $users = $request->query->get('user');
            $phrase1 = $request->query->get('phrase1');
            $phrase2 = $request->query->get('phrase2');
//
//            if($statusAbo ==''){
//                $statusAbo = array('');
//            }
//
//            if($campaign ==''){
//                $campaign = array('');
//            }
//            $date1 = \DateTime::createFromFormat('d/m/Y', $date1);
//            $date2 = \DateTime::createFromFormat('d/m/Y', $date2);

            $users = $this->get('UsersManager')->getUsersByDateAndData($date1->format('Y-m-d 00:00:00'), $date2->format('Y-m-d 23:59:59'), $campaign);
            $paymentToday = $this->get('PaymentManager')->getPaymentBetweenDate($date1->format('Y-m-d 00:00:00'), $date2->format('Y-m-d 23:59:59'));


            return array(
                'entities' => $users,
                'user' => $users,
                'phrase1' => $phrase1,
                'phrase2' => $phrase2,
                'status' => $status

            );
        }
    }

    /**
     * @Route("/about", name="about")
     * @return Response
     */
    public function about(): Response
    {

        return $this->render('pages/about.html.twig');
      //return new Response('about');
    }


    /**
     * @Route("/infos", name="infos")
     * @return Response
     */
    public function infos(): Response
    {
        return $this->render('pages/infos.html.twig');
        //return new Response('infos');
    }


     /**
      * @Route("/contact", name="contact")
      * @return Response
      */
    public function contact(): Response
    {
        return $this->render('pages/contact.html.twig');
        //return new Response('contact');
    }


    /**
     * @Route("/louer", name="louer")
     * @return Response
     */
    public function louer(): Response
    {
        return $this->render('pages/louer.html.twig');
        //return new Response('louer');
    }


    /**
     * @Route("/privacy", name="privacy")
     * @return Response
     */
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
        //return new Response('privacy');
    }


    /**
     * @Route("/account", name="account")
     * @return Response
     */
    public function account(): Response
    {
        return $this->render('pages/account.html.twig');
        //return new Response('privacy');
    }






}
