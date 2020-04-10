<?php

namespace App\Controller;

use App\Entity\User;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpFoundation\Request;


class RegisterController extends AbstractController
{
    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passEncoder)
    {

        $form = $this->createFormBuilder()
                ->add('username')
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Confirm Password'],
                ])
                ->add('register', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-success float-right'
                    ]
                ])
                ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $data = $form->getData();
            $user = new User();
            $user->setUsername($data['username']);
            $user->setPassword(
                $passEncoder->encodePassword($user, $data['password'])
            );

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_login');

            //return $this->redirect($this->generateUrl('app_login'));
        }

        return $this->render('register/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/create_user", name="create_user")
     */
    public function createUsers(Request $request, UserPasswordEncoderInterface $passEncoder)
    {

//        $faker = Factory::create();
//
        $form = $this->createFormBuilder()
                ->add('username')
                ->add('password', RepeatedType::class, [
                    'type' => PasswordType::class,
                    'required' => true,
                    'first_options' => ['label' => 'Password'],
                    'second_options' => ['label' => 'Confirm Password'],
                ])
                ->add('register', SubmitType::class, [
                    'attr' => [
                        'class' => 'btn btn-success float-right'
                    ]
                ])
                ->getForm();

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {

            $data = $form->getData();
            $numberUser = 1;
            for($i = 0; $i < 20; $i++) {

                $user = new User();
//                $user->setUsername($data['username']);
                $user->setUsername($data['username'].$numberUser);
                $user->setPassword(
                    $passEncoder->encodePassword($user, $data['password'])
//                    $passEncoder->encodePassword($user, $data['password'].$numberUser)
//                    $passEncoder->encodePassword($user, $data['password'])
                );
                $numberUser++;

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
//                $user = $user.;
//                $numberUser++;
                dump($user);
//                die;
                $em->flush();
            }
            dump($user);
            die;
            return $this->redirectToRoute('app_login');

            //return $this->redirect($this->generateUrl('app_login'));
        }

        return $this->render('admin/users/create_users.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
