<?php

namespace App\Controller;

//use App\Form\UserType;

use App\Entity\User;
use App\Repository\UserRepository;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;


class UsersController extends AbstractController
{
    /**
     * @Route("users/list", name="admin_user_list", options={"expose"=true})
     * @Method("GET")
     * @return Response
     */
    public function usersListAction(Request $request, UserRepository $repo): Response
    {

//        $statusAbo = array('');
        $statusAbo = '';
//
//        if($statusAbo ==''){
//            $statusAbo = array('');
//        }

        $users = $repo->findAll();

//        if($user ==''){
//            $user = array('');
//        }



//        dump($users);
//        dump($statusAbo);
        dump($users);

        return $this->render('users/list_users.html.twig', [
            'statusAbo' => $statusAbo,
            'user' => $users
        ]);
    }

    /**
     * @Route("users/list/abo", name="admin_user_list_abo", options={"expose"=true})
     * @Method("GET")
     * @Template("@BackOfficeUsers/Users/list_users_abo.html.twig")
     */
    public function usersListaboAction()
    {
        return array();
    }


    /**
     * @Route("ajax/users/list/abo", name="admin_user_list_abo_ajax", options={"expose"=true})
     * @Method("GET")
     * @Template("@BackOfficeUsers/Users/list_users_abo_ajax.html.twig")
     */
    public function usersListaboAjaxAction(Request $request)
    {
        set_time_limit(60000);
        ini_set("memory_limit", -1);

        if ($request->isXmlHttpRequest()) {
            $date1 = $request->query->get('date1');
            $date2 = $request->query->get('date2');

            $date1 = new \DateTime($date1 . '00:00:00');
            $date2 = new \DateTime($date2 . '23:59:00');

            $users = $this->get('UsersManager')->getListAbo($date1, $date2);

            return array(
                'users' => $users,
                'date' => $date2
            );


        }
    }


    /**
     * @Route("ajax/users/listd", name="admin_ajax_users_list_d", options={"expose"=true})
     * @Template("@BackOfficeUsers/Users/template_list_users.html.twig")
     */
    public function ajaxUsersListAction(Request $request)
    {
        set_time_limit(60000);
        ini_set("memory_limit", -1);

        if ($request->isXmlHttpRequest()) {
            $date1 = $request->query->get('date1');
            $date2 = $request->query->get('date2');
            $statusAbo = $request->query->get('statusAbo');
            $campaign = $request->query->get('campaign');
            $mail = $request->query->get('mail');

            if($statusAbo ==''){
                $statusAbo = array('');
            }

            if($campaign ==''){
                $campaign = array('');
            }
            $date1 = \DateTime::createFromFormat('d/m/Y', $date1);
            $date2 = \DateTime::createFromFormat('d/m/Y', $date2);

            $users = $this->get('UsersManager')->getUsersByDateAndData($date1->format('Y-m-d 00:00:00'), $date2->format('Y-m-d 23:59:59'), $campaign);
            $paymentToday = $this->get('PaymentManager')->getPaymentBetweenDate($date1->format('Y-m-d 00:00:00'), $date2->format('Y-m-d 23:59:59'));


            return array(
                'entities' => $users,
                'date1' => $date1,
                'date2' => $date2,
                'paymentToday' => $paymentToday,
                'statusAbo' => $statusAbo,
                'campaign' => $campaign,
                'mail' => $mail,

            );
        }
    }


    /**
     * @Route("ajax/users/pager/list", name="admin_ajax_pager_user_list", options={"expose"=true})
     * @Template("@BackOfficeUsers/Users/template_tab_users.html.twig")
     */
    public function ajaxUsersListPagerAction(Request $request)
    {

        if ($request->isXmlHttpRequest()) {

            $date1 = $request->query->get('date1');
            $date2 = $request->query->get('date2');
            $page = $request->query->get('page');

            $date1 = \DateTime::createFromFormat('d/m/Y', $date1);
            $date2 = \DateTime::createFromFormat('d/m/Y', $date2);


            $pagerfanta = new Pagerfanta($this->get('UsersManager')->getUsersByDatePager($date1->format('Y-m-d 00:00:00'), $date2->format('Y-m-d 23:59:59')));
            $arrayPaged = $pagerfanta
                ->setMaxPerPage(10)
                ->setCurrentPage($page)
                ->getCurrentPageResults();

            return array(
                'arrayPaged' => $arrayPaged,
            );
        }
    }


    /**
     * @Route("users/{mail}", name="admin_user_detail", options={"expose"=true})
     * @Method("GET")
     * @Template("@BackOfficeUsers/UserDetail/user_detail.html.twig")
     */
    public function userDetailAction($mail)
    {

        $user = $this->get('UsersManager')->getUsersByMail($mail);

        if (!$user) {
            //redirect error page
            return $this->render('@BackOfficeSite/Error/error.html.twig', array(
                'title' => $this->get('translator')->trans('Erreur user'), 'message' => $this->get('ErrorMessage.service')->errorUser()));
        }

        $form = $this->createForm(UsersType::class, $user);

        $unsubscribe = $this->get('UnsubscribeManager')->getUnsubscribeByUser($user->getId());

        $user = $this->get('UsersManager')->getUsersByMail($mail);
        $lastPaymentSuccess = $this->get('PaymentManager')->getLastPaymentByUser($user);


        $nbrAbo = 0;
        $refund = 0;

        $welcomePackageStatus = $this->get('WelcomePackageStatusManager')->getAllEntities();


        $typeAbo = $this->get('CampaignTypesManager')->getAllEntity();
        // $mailingTemplate = $this->get('MailingTemplateManager')->getAllEntity();

        $countries = $this->get('CountryManager')->getAllEntities();
        $cb = '****';


        $notifs = $this->get('NotifPspManager')->getEntityByUser($user);

        $comments = $this->get('CommentsManager')->getCommentsByUser($user);

        $boxList = $this->get('BoxManager')->getBoxWithStock();
        $packagesList = $this->get('PackagesManager')->getPackageWithStock();


        $boxPackageArray = array();
        $boxUsers = '';
        $boxUserStatus = '';
        $packageUsers = '';
        $packageUserStatus = '';
        $weeklyOffersUsers = '';
        $cashBackUsers = '';
        $cashBackUserStatus = '';
        $weeklyOffersUserStatus = '';
        if ($this->getParameter('BoxBundle')) {
            $boxUsers = $this->get('BoxUserManager')->getBoxUserByUser($user);
            $boxUserStatus = $this->get('BoxUserStatusManager')->getAllEntities();
        }
        if ($this->getParameter('PackagesBundle')) {
            $packageUsers = $this->get('PackageUserManager')->getPackageUserByUser($user);
            $packageUserStatus = $this->get('PackageUserStatusManager')->getAllEntities();
        }
        if ($this->getParameter('WeeklyOffersBundle')) {
            $weeklyOffersUsers = $this->get('WeeklyOffersUserManager')->getWeeklyOffersUserByUser($user);
            $weeklyOffersUserStatus = $this->get('WeeklyOffersUserStatusManager')->getAllEntities();
        }
        if ($this->getParameter('CashBackBundle')) {
            $cashBackUsers = $this->get('CashBackUserManager')->getCashBackUserByUser($user);
            $cashBackUserStatus = $this->get('CashBackUserStatusManager')->getAllEntities();
        }


        return array(
            'boxList' => $boxList,
            'packageList' => $packagesList,
            'cashBackUsers' => $cashBackUsers,
            'cashBackUserStatus' => $cashBackUserStatus,
            'weeklyOffersUsers' => $weeklyOffersUsers,
            'weeklyOffersUserStatus' => $weeklyOffersUserStatus,
            'packageUsers' => $packageUsers,
            'boxUsers' => $boxUsers,
            'packageUserStatus' => $packageUserStatus,
            'boxUserStatus' => $boxUserStatus,
            'countries' => $countries,
            'entity' => $user,
            'notifs' => $notifs,
            'welcomePackageStatus' => $welcomePackageStatus,
            'form' => $form->createView(),
            'unsubscribe' => $unsubscribe,
            'nbrAbo' => $nbrAbo,
            'refund' => $refund,
            'typeAbo' => $typeAbo,
            'comments' => $comments,
            'cb' => $cb,
        );
    }

    /**
     * @Route("ajax/users/edit/{mail}",  name="admin_edit_user_save", options={"expose"=true})
     * @Template("BackOfficeUsersBundle:UserDetail:user_edit_form.html.twig")
     */
    public function userEditSaveAction(Request $request, $mail)
    {

        $entity = $this->get('UsersManager')->getUsersByMail($mail);

        $country = $this->get('CountryManager')->getEntityById($request->request->get('country'));


        $entity->getUserAddress()->setStreet($request->request->get('street'));
        $entity->getUserAddress()->setCountry($country);
        $entity->getUserAddress()->setCp($request->request->get('cp'));
        $entity->getUserAddress()->setCity($request->request->get('city'));
        $entity->getUserAddress()->setFirstname($request->request->get('firstname'));
        $entity->getUserAddress()->setLastname($request->request->get('lastname'));
        $entity->getUserInfos()->setPhone($request->request->get('phone'));

        $oldMail = $entity->getMail();


        if ($this->get('UsersManager')->getUsersByMail($request->request->get('mail'))) {
            $entity->setMail($oldMail);
            $entity->setUsername($oldMail);
        } else {
            $entity->setMail($request->request->get('mail'));
            $entity->setUsername($request->request->get('mail'));
        }

        $this->get('UsersManager')->flush();

        // insert in log
        $this->get('Logs.service')->insertLog($this->getUser(), "Edition d'un utilisateur par " . $this->getUser()->getUsername() . ": " . $entity->getMail());

        $result = $this->get('ApiBillingService')->updateUser($mail, $entity);
        $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));

        $valid = false;
        if ($result) {
            if ($result->valid) {
                $valid = true;
            }
        }
        if (!$valid) {
            //sendMailAlert
        }

        if ($this->getParameter("BoxBundle")) {
            foreach ($entity->getBoxUser() as $boxUser) {
                if ($boxUser->getLastStatus() == 10 or $boxUser->getLastStatus() == 9) {
                    $boxStatus = $this->get('BoxUserStatusManager')->getEntityById($this->verifStatusBox($entity, $boxUser));
                    if ($boxUser->getLastStatus() != $boxStatus->getId()) {
                        $boxUser->setLastStatus($boxStatus->getId());

                        $boxAction = new BoxAction();
                        $boxAction->setBoxUser($boxUser);
                        $boxAction->setBoxUserStatus($boxStatus);
                        $boxAction->setDate(new \DateTime("now"));
                        $this->get('BoxActionManager')->persistAndFlush($boxAction);
                    }
                    foreach ($boxUser->getPackageUser() as $packageUser) {
                        $packageStatus = $this->get('PackageUserStatusManager')->getEntityById($this->verifStatusPackage($entity, $packageUser));
                        if ($packageUser->getLastStatus() != $packageStatus->getId()) {
                            $packageUser->setLastStatus($packageStatus->getId());
                            $packageAction = new PackageAction();
                            $packageAction->setPackageUser($packageUser);
                            $packageAction->setPackageUserStatus($packageStatus);
                            $packageAction->setDate(new \DateTime("now"));
                            $this->get('PackageActionManager')->persistAndFlush($packageAction);
                        }
                    }
                }
            }
        }


        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $entity->getMail())));
    }

    protected function verifStatusBox($user, $boxUser)
    {
        $status = 2;
        $error = 0;
        foreach ($boxUser->getPackageUser() as $packageUser) {
            if (!$packageUser->getPackage()) {
                $error++;
            }
        }
        if ($error != 0) {
            $status = 16;
        }

        if (!$boxUser->getBox()) {
            $status = 15;
        }

        if ($user->getUserAddress()->getStreet() == '' or $user->getUserAddress()->getCp() == '' or $user->getUserAddress()->getCity() == '' or $user->getUserAddress()->getFirstname() == '' or $user->getUserAddress()->getLastname() == '') {
            $status = 10;
        }
        if ($user->getUserAddress()->getStreet() == '' and $user->getUserAddress()->getCp() == '' and $user->getUserAddress()->getCity() == '' and $user->getUserAddress()->getFirstname() == '' and $user->getUserAddress()->getLastname() == '') {
            $status = 9;
        }


        return $status;
    }

    protected function verifStatusPackage($user, $packageUser)
    {

        $status = 2;

        if (!$packageUser->getPackage()) {
            $status = 15;
        }

        if ($user->getUserAddress()->getStreet() == '' or $user->getUserAddress()->getCp() == '' or $user->getUserAddress()->getCity() == '' or $user->getUserAddress()->getFirstname() == '' or $user->getUserAddress()->getLastname() == '') {
            $status = 10;
        }
        if ($user->getUserAddress()->getStreet() == '' and $user->getUserAddress()->getCp() == '' and $user->getUserAddress()->getCity() == '' and $user->getUserAddress()->getFirstname() == '' and $user->getUserAddress()->getLastname() == '') {
            $status = 9;
        }

        return $status;
    }

//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("/users/edit/comment/save",  name="admin_comment_user_save", options={"expose"=true})
//     */
//    public function userCommentSaveAction(Request $request)
//    {
//        $comment = '<u>' . $this->getUser()->getUsername() . '</u> - ' . $request->request->get('comment');
//        $mail = $request->request->get('mail');
//        $entity = $this->get('UsersManager')->getUsersByMail($mail);
//
//        $newComment = new Comments();
//        $newComment->setUser($entity);
//        $newComment->setByUser($this->getUser());
//        $newComment->setComment($comment);
//        $this->get('CommentsManager')->persistAndFlush($newComment);
//
//
//
//        $result = $this->get('ApiBillingService')->addCommentForUser($comment, $mail);
//        $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//
//        if ($result) {
//            if ($result->valid) {
//
//            }
//        }
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $entity->getMail())));
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/edit/password/save",  name="admin_generate_password", options={"expose"=true})
//     */
//    public function userEditPasswordSaveAction(Request $request)
//    {
//
//        if ($request->isXmlHttpRequest()) {
//            $mail = $request->query->get('mail');
//
//            $entity = $this->get('UsersManager')->getUsersByMail($mail);
//
//            $mdp = md5(time());
//            $entity->setPasswordRecover($mdp);
//            $this->get('UsersManager')->flush();
//            $error = '';
//            $this->get('SendMail')->SendTemplateMail($this->get('MailingTemplateManager')->getMailingTemplateByName('mailLostPassword'), $entity, $mdp);
//
//            $response = 'success';
//            return new JsonResponse($response);
//        }
//
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/refund/save",  name="admin_refund_payment_save", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function userRefundLastPaymentSaveAction(Request $request)
//    {
//        $paymentId = $request->request->get('payment');
//        $priceRefund = number_format($request->request->get('refund'), 2, '.', '');
//        $payment = $this->get('PaymentManager')->getEntityById($paymentId);
//        $user = $payment->getUser();
//
//
//        if ($this->getUser()->getRoles()[0] == 'ROLE_MODERATOR') {
//            $refundRequest = new RefundRequest();
//            $refundRequest->setAmount($priceRefund);
//            $refundRequest->setPayment($payment);
//            $refundRequest->setUser($this->getUser());
//            $payment->setIsRefundRequest(true);
//            $this->get('RefundRequestManager')->persistAndFlush($refundRequest);
//            $this->get('session')->getFlashBag()->add(
//                'success',
//                '<div class="alert alert-success">Demande de remboursement enregistrée<br></div>'
//            );
//            $newComment = new Comments();
//            $newComment->setUser($user);
//            $newComment->setByUser($this->getUser());
//            $newComment->setComment('Ajout demande remboursement');
//            $this->get('CommentsManager')->persistAndFlush($newComment);
//        } else {
//            if ($payment->getRefund() == '') {
//
//                $result = $this->get('ApiBillingService')->refundPayment($payment, $priceRefund);
//                $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//
//                if ($result) {
//                    if ($result->valid) {
//                        $user->setRoles($this->get('RolesManager')->getRolesByName('ROLE_FREEMIUM'));
//                        if ($this->getParameter("envTest") == 1) {
//                            $this->get($payment->getPaymentTypes()->getNameConfig())->refundTest($payment, json_decode($result->payment), $priceRefund);
//                        } else {
//                            $this->get($payment->getPaymentTypes()->getNameConfig())->refund($payment, json_decode($result->payment), $priceRefund);
//                        }
//                        $this->get('session')->getFlashBag()->add(
//                            'success',
//                            '<div class="alert alert-success">Remboursement effectué<br></div>'
//                        );
//                        $newComment = new Comments();
//                        $newComment->setUser($user);
//                        $newComment->setByUser($this->getUser());
//                        $newComment->setComment('Remboursement effectué');
//                        $this->get('CommentsManager')->persistAndFlush($newComment);
//                    } else {
//                        $this->get('session')->getFlashBag()->add(
//                            'success',
//                            '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                        );
//                    }
//                } else {
//                    $this->get('session')->getFlashBag()->add(
//                        'success',
//                        '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                    );
//                }
//
//
//            } else {
//                $this->get('session')->getFlashBag()->add(
//                    'success',
//                    '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                );
//            }
//        }
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $payment->getUser()->getMail())));
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/refund/cheque/save",  name="admin_refund_payment_cheque_save", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function userRefundLastPaymentChequeSaveAction(Request $request)
//    {
//        $paymentId = $request->request->get('payment');
//        $priceRefund = number_format($request->request->get('refund'), 2, '.', '');
//        $payment = $this->get('PaymentManager')->getEntityById($paymentId);
//        $user = $payment->getUser();
//
//
//        if ($this->getUser()->getRoles()[0] == 'ROLE_MODERATOR') {
//            $refundRequest = new RefundRequest();
//            $refundRequest->setAmount($priceRefund);
//            $refundRequest->setPayment($payment);
//            $refundRequest->setUser($this->getUser());
//            $payment->setIsRefundRequest(true);
//            $this->get('RefundRequestManager')->persistAndFlush($refundRequest);
//            $this->get('session')->getFlashBag()->add(
//                'success',
//                '<div class="alert alert-success">Demande de remboursement enregistrée<br></div>'
//            );
//            $newComment = new Comments();
//            $newComment->setUser($user);
//            $newComment->setByUser($this->getUser());
//            $newComment->setComment('Ajout demande remboursement');
//            $this->get('CommentsManager')->persistAndFlush($newComment);
//        } else {
//            if ($payment->getRefund() == '') {
//
//                $result = $this->get('ApiBillingService')->refundCheque($payment, $priceRefund);
//
//                $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//
//                if ($result) {
//
//                    if ($result->valid) {
//                        $user->setRoles($this->get('RolesManager')->getRolesByName('ROLE_FREEMIUM'));
//                        if ($this->getParameter("envTest") == 1) {
//                            $this->get($payment->getPaymentTypes()->getNameConfig())->refundTest($payment, json_decode($result->payment), $priceRefund);
//                        } else {
//                            $this->get($payment->getPaymentTypes()->getNameConfig())->refundCheque($payment, json_decode($result->payment), $priceRefund);
//                        }
//                        $this->get('session')->getFlashBag()->add(
//                            'success',
//                            '<div class="alert alert-success">Remboursement effectué<br></div>'
//                        );
//                        $newComment = new Comments();
//                        $newComment->setUser($user);
//                        $newComment->setByUser($this->getUser());
//                        $newComment->setComment('Remboursement cheque/virement effectué');
//                        $this->get('CommentsManager')->persistAndFlush($newComment);
//                    } else {
//                        $this->get('session')->getFlashBag()->add(
//                            'success',
//                            '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                        );
//                    }
//                } else {
//                    $this->get('session')->getFlashBag()->add(
//                        'success',
//                        '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                    );
//                }
//
//
//            } else {
//                $this->get('session')->getFlashBag()->add(
//                    'success',
//                    '<div class="alert alert-danger">Remboursement impossible<br></div>'
//                );
//            }
//        }
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $payment->getUser()->getMail())));
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/resiliation/save",  name="admin_resiliation_save", options={"expose"=true})
//     * @Template("@BackOfficeUsers/UserDetail/user_payment.html.twig")
//     */
//    public function userResiliationSaveAction(Request $request)
//    {
//
//        if ($request->isXmlHttpRequest()) {
//            $mail = $request->query->get('mail');
//            $entity = $this->get('UsersManager')->getUsersByMail($mail);
//
//            $result = $this->get('ApiBillingService')->resiliationUser($mail);
//            $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//
//            if ($result) {
//                if ($result->valid) {
//                    $lastPayment = $this->get('PaymentManager')->getLastPaymentAnnulation($entity->getId());
//                    $realLast = $this->get('PaymentManager')->getRealLastPaymentByUser($entity->getId());
//
//                    if ($realLast->getPaymentStatus()->getId() != 9) {
//                        if ($lastPayment != '') {
//                            $dateNow = new \DateTime('NOW');
//
//                            $interval = $entity->getUserInfos()->getDateInscription()->diff($dateNow);
//                            $nbrDays = $interval->format('%a');
//                            if ($nbrDays < 3) {
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(5);
//                                //SEND MAIL < 72h
//                            } else {
//                                //SEND MAIL > 72h
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(7);
//                            }
//                            $this->get('SendMail')->SendTemplateMail($this->get('MailingTemplateManager')->getMailingTemplateByName('mailResiliationUser'), $entity);
//                            $unsubscribe = new Unsubscribe();
//                            $unsubscribe->setUser($entity);
//                            $unsubscribe->setComment('Résiliation admin (' . $this->getUser()->getUsername() . ')');
//                            $this->get('UnsubscribeManager')->persist($unsubscribe);
//
//                            $entity->setRoles($this->get('RolesManager')->getEntityById(9));
//
//
//                            $annulation = $this->get('PaymentManager')->getForAnnulation($entity);
//                            foreach ($annulation as $val) {
//                                $val->setPaymentStatus($paymentStatus);
//                            }
//                            $this->get('UsersManager')->flush();
//
//
//                            $newComment = new Comments();
//                            $newComment->setUser($entity);
//                            $newComment->setByUser($this->getUser());
//                            $newComment->setComment("Résiliation membre");
//                            $this->get('CommentsManager')->persistAndFlush($newComment);
//
//
//                            if($this->container->getParameter('Expertsender')){
//                                $this->get('ExpertSender')->addUserToList($entity->getMail(), $this->getParameter('Expertsender')['listDesabo'], array());
//                                 $this->container->get('ExpertSender')->deleteUser($entity->getMail(), $this->container->getParameter('Expertsender')['listAbo'], array());
//                            }
//
//
//                            return array('entity' => $entity, 'message' => 'La résiliation a bien été effectué.');
//                        }
//                    }
//                }
//            } else {
//                $response = '2';
//                return new JsonResponse($response);
//            }
//            $response = '2';
//            return new JsonResponse($response);
//        }
//
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("manual/users/resiliation/{mail}",  name="admin_resiliation_manual", options={"expose"=true})
//     */
//    public function userResiliationManualAction(Request $request, $mail)
//    {
//
//            $entity = $this->get('UsersManager')->getUsersByMail($mail);
//
//                    $lastPayment = $this->get('PaymentManager')->getLastPaymentAnnulation($entity->getId());
//                    $realLast = $this->get('PaymentManager')->getRealLastPaymentByUser($entity->getId());
//
//                    if ($realLast->getPaymentStatus()->getId() != 9) {
//                        if ($lastPayment != '') {
//                            $dateNow = new \DateTime('NOW');
//
//                            $interval = $entity->getUserInfos()->getDateInscription()->diff($dateNow);
//                            $nbrDays = $interval->format('%a');
//                            if ($nbrDays < 3) {
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(5);
//                                //SEND MAIL < 72h
//                            } else {
//                                //SEND MAIL > 72h
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(7);
//                            }
//                            $this->get('SendMail')->SendTemplateMail($this->get('MailingTemplateManager')->getMailingTemplateByName('mailResiliationUser'), $entity);
//                            $unsubscribe = new Unsubscribe();
//                            $unsubscribe->setUser($entity);
//                            $unsubscribe->setComment('Résiliation admin (' . $this->getUser()->getUsername() . ')');
//                            $this->get('UnsubscribeManager')->persist($unsubscribe);
//
//                            $entity->setRoles($this->get('RolesManager')->getEntityById(9));
//
//
//                            $annulation = $this->get('PaymentManager')->getForAnnulation($entity);
//                            foreach ($annulation as $val) {
//                                $val->setPaymentStatus($paymentStatus);
//                            }
//                            $this->get('UsersManager')->flush();
//
//
//                            $newComment = new Comments();
//                            $newComment->setUser($entity);
//                            $newComment->setByUser($this->getUser());
//                            $newComment->setComment("Résiliation membre");
//                            $this->get('CommentsManager')->persistAndFlush($newComment);
//
//
//                            if($this->container->getParameter('Expertsender')){
//                                $this->get('ExpertSender')->addUserToList($entity->getMail(), $this->getParameter('Expertsender')['listDesabo'], array());
//                                $this->container->get('ExpertSender')->deleteUser($entity->getMail(), $this->container->getParameter('Expertsender')['listAbo'], array());
//                            }
//
//                        }
//                    }
//
//          die;
//
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/resiliation/delete/save",  name="admin_resiliation_delete_save", options={"expose"=true})
//     * @Template("@BackOfficeUsers/UserDetail/user_payment.html.twig")
//     */
//    public function userResiliationDeleteSaveAction(Request $request)
//    {
//
//        if ($request->isXmlHttpRequest()) {
//            $mail = $request->query->get('mail');
//            $entity = $this->get('UsersManager')->getUsersByMail($mail);
//
//            $result = $this->get('ApiBillingService')->resiliationUser($mail);
//            $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//
//            if ($result) {
//                if ($result->valid) {
//                    $lastPayment = $this->get('PaymentManager')->getLastPaymentAnnulation($entity->getId());
//                    $realLast = $this->get('PaymentManager')->getRealLastPaymentByUser($entity->getId());
//
//                    if ($realLast->getPaymentStatus()->getId() != 9) {
//                        if ($lastPayment != '') {
//                            $dateNow = new \DateTime('NOW');
//
//                            $interval = $entity->getUserInfos()->getDateInscription()->diff($dateNow);
//                            $nbrDays = $interval->format('%a');
//                            if ($nbrDays < 3) {
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(5);
//                                //SEND MAIL < 72h
//                            } else {
//                                //SEND MAIL > 72h
//                                $paymentStatus = $this->get('PaymentStatusManager')->getPaymentStatusById(7);
//                            }
//                            $this->get('SendMail')->SendTemplateMail($this->get('MailingTemplateManager')->getMailingTemplateByName('mailResiliationUser'), $entity);
//                            $unsubscribe = new Unsubscribe();
//                            $unsubscribe->setUser($entity);
//                            $unsubscribe->setComment('Résiliation admin (' . $this->getUser()->getUsername() . ')');
//                            $this->get('UnsubscribeManager')->persist($unsubscribe);
//
//                            $entity->setRoles($this->get('RolesManager')->getEntityById(10));
//
//
//                            $annulation = $this->get('PaymentManager')->getForAnnulation($entity);
//                            foreach ($annulation as $val) {
//                                $val->setPaymentStatus($paymentStatus);
//                            }
//                            $this->get('UsersManager')->flush();
//
//
//                            $newComment = new Comments();
//                            $newComment->setUser($entity);
//                            $newComment->setByUser($this->getUser());
//                            $newComment->setComment("Résiliation et suppresion membre");
//                            $this->get('CommentsManager')->persistAndFlush($newComment);
//
//                            if($this->container->getParameter('Expertsender')){
//                                $this->get('ExpertSender')->addUserToList($entity->getMail(), $this->getParameter('Expertsender')['listDesabo'], array());
//                                $this->container->get('ExpertSender')->deleteUser($entity->getMail(), $this->container->getParameter('Expertsender')['listAbo'], array());
//                            }
//
//                            return array('entity' => $entity, 'message' => 'La résiliation a bien été effectué.');
//                        }
//                    }
//                }
//            } else {
//                $response = '2';
//                return new JsonResponse($response);
//            }
//            $response = '2';
//            return new JsonResponse($response);
//        }
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/list", name="admin_ajax_users_list")
//     * @Method("GET")
//     * @return ajax
//     */
//    public function getAjaxUsersAction()
//    {
//        $request = $this->container->get('request');
//
//        if ($request->isXmlHttpRequest()) {
//            // get title sent ($_GET)
//            $search = $request->query->get('search');
//
//            $usersAjax = $this->get('UsersManager')->getUserAjaxAutocompleteName($search);
//
//            $list = array();
//            foreach ($usersAjax as $key => $value) {
//                $list[$key]['id'] = $value->getId();
//                $list[$key]['value'] = $value->getUserAddress()->getFirstname() . ' - ' . $value->getUserAddress()->getLastname();
//            }
//
//            return new JsonResponse($list);
//        }
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/mail/list", name="admin_ajax_mail_list")
//     * @Method("GET")
//     * @return ajax
//     */
//    public function getAjaxMailAction(Request $request)
//    {
//        if ($request->isXmlHttpRequest()) {
//            // get title sent ($_GET)
//            $search = $request->query->get('search');
//
//            $usersAjax = $this->get('UsersManager')->getUserAjaxAutocompleteMail($search);
//
//            $list = array();
//            foreach ($usersAjax as $key => $value) {
//                $list[$key]['id'] = $value->getId();
//                $list[$key]['value'] = $value->getMail();
//            }
//
//            return new JsonResponse($list);
//        }
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("search/users", name="admin_user_search", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function usersSearchAction(Request $request)
//    {
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $request->request->get('user'))));
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("users/search/list", name="admin_user_search_list", options={"expose"=true})
//     * @Method("GET")
//     * @Template("@BackOfficeUsers/Users/users_search.html.twig")
//     */
//    public function usersSearch2Action()
//    {
//        return array();
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/users/search/list", name="admin_user_search_list_ajax", options={"expose"=true})
//     * @Template("@BackOfficeUsers/Users/users_search_tab.html.twig")
//     */
//    public function usersSearchAjaxction(Request $request)
//    {
//
//        if ($request->isXmlHttpRequest()) {
//            $name = $request->query->get('name');
//            $prenom = $request->query->get('prenom');
//            $mail = $request->query->get('mail');
//            $id = $request->query->get('id');
//            $address = $request->query->get('address');
//            $cp = $request->query->get('cp');
//            $userTemp = $request->query->get('userTemp');
//            $transaction = $request->query->get('transaction');
//
//            if ($transaction != '') {
//                $data = $this->get('UsersManager')->getByTransactionId($transaction);
//                return array('entities' => $data);
//            }
//            if ($userTemp == '1') {
//                $data = $this->get('UserTempManager')->getSearchUsers($name, $prenom, $mail, $id, $address, $cp);
//            } else {
//                $data = $this->get('UsersManager')->getSearchUsers($name, $prenom, $mail, $id, $address, $cp);
//            }
//
//
//            return array('entities' => $data);
//
//        }
//
//    }
//
//
//    /**
//     * @Route("invoice/generate/{id}", name="invoice_generate_admin", options={"expose"=true})
//     * @Method("GET")
//     */
//    public function InvoiceAction($id)
//    {
//        set_time_limit(60000);
//        ini_set("memory_limit", -1);
//        $payment = $this->get('PaymentManager')->getPaymentById($id);
//
//
//        $filename = base64_encode($this->getUser()->getId() . $payment->getNextPaymentDate()->format('d-m-Y') . '_admin');
//        // Check si le fichier et le dossier existe, sinon les generes
//        if (!file_exists(__DIR__ . '/../../../../web/uploads/factures')) mkdir(__DIR__ . '/../../../../web/uploads/factures', 0755);
//
//        if (!file_exists("__DIR__.'/../../../../web/uploads/factures/facture-" . $filename . ".pdf")) {
//            $html = $this->renderView('FrontSiteBundle:Invoice:invoice.html.twig', array('payment' => $payment, 'user' => $payment->getUser()));
//
//            $html2pdf = $this->get('html2pdf_factory')->create('P', 'A4', 'en', true, 'UTF-8', array(10, 15, 10, 15));
//            $html2pdf->pdf->SetDisplayMode('real');
//            $html2pdf->writeHTML($html, isset($_GET['vuehtml']));
//
//
//            $fichier = $html2pdf->Output(__DIR__ . '/../../../../web/uploads/factures/facture-' . $filename . '.pdf', 'F');
//        }
//
//        return $this->redirect("/uploads/factures/facture-" . $filename . ".pdf");
//
//
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("user/recap/{mail}",  name="admin_recap_user", options={"expose"=true})
//     * @Template("@BackOfficeUsers/pdf/recap.html.twig")
//     */
//    public function userRecapAction($mail)
//    {
//
//        $entity = $this->get('UsersManager')->getUsersByMail($mail);
//        $unsubscribe = $this->get('UnsubscribeManager')->getUnsubscribeByUser($entity->getId());
//
//
//        $filename = base64_encode($this->getUser()->getId() . time() . '_admin');
//        // Check si le fichier et le dossier existe, sinon les generes
//        if (!file_exists(__DIR__ . '/../../../../web/uploads/factures')) mkdir(__DIR__ . '/../../../../web/uploads/factures', 0755);
//
//        if (!file_exists("__DIR__.'/../../../../web/uploads/factures/recap-" . $filename . ".pdf")) {
//            $html = $this->renderView('BackOfficeUsersBundle:pdf:recap.html.twig', array('unsubscribe' => $unsubscribe, 'user' => $entity));
//
//            $html2pdf = $this->get('html2pdf_factory')->create('P', 'A4', 'en', true, 'UTF-8', array(10, 15, 10, 15));
//            $html2pdf->pdf->SetDisplayMode('real');
//            $html2pdf->writeHTML($html, isset($_GET['vuehtml']));
//
//
//            $fichier = $html2pdf->Output(__DIR__ . '/../../../../web/uploads/factures/recap-' . $filename . '.pdf', 'F');
//        }
//
//        return $this->redirect("/uploads/factures/recap-" . $filename . ".pdf");
//
//
//        return array(
//            'user' => $entity,
//            'unsubscribe' => $unsubscribe,
//        );
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/user/welcomepackages/status/update/one", name="admin_user_edit_welcomePackage_one", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function ajaxWelcomePackagesUpdateOneAction(Request $request)
//    {
//
//        $id = $request->request->get('id');
//        $package_status = $request->request->get('package_status');
//
//        $status = $this->get('WelcomePackageStatusManager')->getWelcomePackageStatusById($package_status);
//        $welcomePackageUser = $this->get('WelcomePackageUserManager')->getEntityById($id);
//
//        $welcomePackageAction = new WelcomePackageAction();
//        $welcomePackageAction->setWelcomePackageUser($welcomePackageUser);
//        $welcomePackageAction->setWelcomePackageStatus($status);
//        $this->get('WelcomePackageActionManager')->persistAndFlush($welcomePackageAction);
//
//        if ($package_status == 5 or $package_status == 6 or $package_status == 9) {
//            $welcomePackageUser->setIsDelete(true);
//        }
//
//        $this->get('WelcomePackageActionManager')->flush();
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $welcomePackageUser->getUser()->getMail())));
//
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/user/box/status/update/one", name="admin_user_edit_box_status", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function updateBoxStatusAction(Request $request)
//    {
//
//        $id = $request->request->get('id');
//        $package_status = $request->request->get('box_status');
//
//        $status = $this->get('BoxUserStatusManager')->getBoxUserStatusById($package_status);
//        $boxUser = $this->get('BoxUserManager')->getEntityById($id);
//        $boxUser->setLastStatus($package_status);
//        $boxAction = new BoxAction();
//        $boxAction->setBoxUser($boxUser);
//        $boxAction->setBoxUserStatus($status);
//        if ($package_status == 14) {
//            if ($boxUser->getBox()) {
//                $boxUser->getBox()->setQte($boxUser->getBox()->getQte() + 1);
//            }
//        }
//        $this->get('BoxActionManager')->persistAndFlush($boxAction);
//
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $boxUser->getUser()->getMail())));
//
//    }
//
//
//
//
//
//
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("edit/tracking", name="admin_edit_tracking", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function editTrackingAction(Request $request)
//    {
//
//        $id = $request->request->get('id');
//        $tracking = $request->request->get('tracking');
//        $type = $request->request->get('type');
//
//        if($type == 1){
//            $boxUser = $this->get('BoxUserManager')->getEntityById($id);
//            $boxUser->setTracking($tracking);
//            $this->get('BoxUserManager')->flush();
//            $mail = $boxUser->getUser()->getMail();
//        }
//
//        if($type == 2){
//            $packageUser = $this->get('PackageUserManager')->getEntityById($id);
//            $packageUser->setTracking($tracking);
//            $this->get('PackageUserManager')->flush();
//            $mail = $packageUser->getUser()->getMail();
//        }
//
//        $this->get('session')->getFlashBag()->add(
//            'success',
//            '<div class="alert alert-success">Tracking mis à jour<br></div>'
//        );
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $mail)));
//
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("ajax/user/package/status/update/one", name="admin_user_edit_package_status", options={"expose"=true})
//     * @Method("POST")
//     */
//    public function updatePackageStatusAction(Request $request)
//    {
//
//        $id = $request->request->get('id');
//        $package_status = $request->request->get('package_status');
//
//        $status = $this->get('PackageUserStatusManager')->getPackageUserStatusById($package_status);
//        $packageUser = $this->get('PackageUserManager')->getEntityById($id);
//        $packageUser->setLastStatus($package_status);
//
//        $packageAction = new PackageAction();
//        $packageAction->setPackageUser($packageUser);
//        $packageAction->setPackageUserStatus($status);
//
//        if ($package_status == 14) {
//            if ($packageUser->getPackage()) {
//                $packageUser->getPackage()->setQte($packageUser->getPackage()->getQte() + 1);
//            }
//        }
//        $this->get('PackageActionManager')->persistAndFlush($packageAction);
//
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $packageUser->getUser()->getMail())));
//
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_ADMIN")
//     * @Route("view/coupons/{name}", name="admin_view_coupons", options={"expose"=true})
//     */
//    public function readPdfCouvAdminAction($name)
//    {
//        $doc = __DIR__ . '/../../ConfigurationBundle/CSV/Coupons/' . $name;
//
//        header("Content-Disposition:attachment;filename=" . $name);
//
//        readfile($doc);
//        return new Response();
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("edit/paypal",  name="admin_edit_paypal", options={"expose"=true})
//     */
//    public function editPaypalAction(Request $request)
//    {
//        $user = $this->get('UserInfosManager')->getEntityById($request->request->get('id'));
//        $paypal = $request->request->get('paypal');
//
//
//
//        $user->setMailPaypal($paypal);
//
//        $this->get('UsersManager')->flush();
//        return $this->redirect($this->generateUrl('admin_user_detail', array('mail' => $user->getUser()->getMail())));
//    }
//
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("/delete/user_error_acquisition", name="admin_delete_user_error_acquisition", options={"expose"=true})
//     * @Method("POST")
//     * @Template("@BackOfficeUsers/UsersErrorAcquisition/delete_user_error_acquisition.html.twig")
//     */
//    public function user_error_acquisitionAction(Request $request)
//    {
//        return array();
//    }
//
//    /**
//     * @Secure(roles="ROLE_MODERATOR")
//     * @Route("/delete/user_error_acquisition/save", name="admin_delete_user_error_acquisition_save", options={"expose"=true})
//     * @Method("POST")
//     * @Template("@BackOfficeUsers/UsersErrorAcquisition/delete_user_error_acquisition.html.twig")
//     */
//    public function user_error_acquisitionSaveAction(Request $request)
//    {
//        $mail = $request->request->get('mail');
//
//        $result = $this->get('ApiBillingService')->deleteUserErrorAcquisition($request->request->get('mail'));
//        $result = json_decode($this->get("CryptService")->decrypt($result, $this->container->getParameter("keyBilling")));
//        $valid = false;
//
//        if ($result) {
//            if ($result->valid) {
//                if($result->exist == 1){
//                    $this->get('session')->getFlashBag()->add(
//                        'success',
//                        '<div class="alert alert-success">'.$this->get('translator')->trans('Suppression réussi').'<br></div>'
//                    );
//                }
//                else if($result->exist == 2){
//                    $this->get('session')->getFlashBag()->add(
//                        'success',
//                        '<div class="alert alert-danger">'.$this->get('translator')->trans('Mail déjà supprimé').'<br></div>'
//                    );
//                }else{
//                    $this->get('session')->getFlashBag()->add(
//                        'success',
//                        '<div class="alert alert-danger">Se mail n\'existe pas pour ce site.<br></div>'
//                    );
//                }
//            }
//        }else{
//            $this->get('session')->getFlashBag()->add(
//                'success',
//                '<div class="alert alert-danger">'.$this->get('translator')->trans('Suppression échoué, erreur serveur ! veuillez contacter Maxime').'<br></div>'
//            );
//        }
//
//
//        return array();
//    }

}
