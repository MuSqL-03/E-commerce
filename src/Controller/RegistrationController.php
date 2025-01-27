<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use App\Repository\UsersRepository;
use App\Security\UsersAuthenticator;
use App\Service\JWTService;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, 
    Security $security, EntityManagerInterface $entityManager, SendMailService $mail, JWTService $jwt): Response
    {
        $user = new Users();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

           $entityManager->persist($user);
           $entityManager->flush();

            // do anything else you need here, like send an email

            // on génère le JWT de l'utilisateurs

            // on crée le Header
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];

           

            // on crée le Payload
            $payload = [
                'user_id' => $user->getId()
            ];

             // on génère le token 
             $token = $jwt->generate($header, $payload, 
             $this->getParameter('app.jwtsecret'));
 
            
            // on envoie un mail
            $mail->send(

                'no-reply@monsite.net',
                $user->getEmail(),
                'Activation de vortre compte sur le site e-commerce',
                'register',
                compact('user', 'token')
               
            );




            return $security->login($user, UsersAuthenticator::class, 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verif/{token}', name: 'verify_user')]
    public function  verifyUser($token, JWTService $jwt, UsersRepository $usersRepository, EntityManagerInterface $em): Response
    {
       // on va vérifie si lo token est valid pas expiré et pas modifier

       if($jwt->isValid($token) && !$jwt->isExpired($token) && $jwt->check($token, $this->getParameter('app.jwtsecret'))) 
       {
          // on récupère le payload
          $payload = $jwt->getPayload($token);

          // on récupère le user du token 
          $user = $usersRepository->find($payload['user_id']);

          // on vérifie que l-utilisateurs exist et n'a pas  encore activé son compte 

          if($user && !$user->getIsVerified()) 
          {
            $user->setIsVerified(true);
            $em->flush($user);

            $this->addFlash('success', 'Utilisateurs activé');
            return $this->redirectToRoute('profile_index');
          }
       }

       // ici un probléme se pose dans le token
       $this->addFlash('danger', 'Le token est invalide ou a expiré');
       return $this->redirectToRoute('app_login');

    }


    #[Route('/renvoiverif', name: 'resend_verif')]
    public function resendVerif(JWTService $jwt, SendMailService $mail, UsersRepository $usersRepository): Response
    {
        $user = $this->getUser();

        if(!$user) {
            $this->addFlash('danger', 'Vous devez étre connecté pour accéder à cette page');
            return $this->redirectToRoute('app_login');
        }

        if($user->getIsVerified()) 
        {

            $this->addFlash('warning', 'Cet utilisateur est déja activé ');
            return $this->redirectToRoute('profile_index');

        }


            // on génère le JWT de l'utilisateurs

            // on crée le Header
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];

           

            // on crée le Payload
            $payload = [
                'user_id' => $user->getId()
            ];

             // on génère le token 
             $token = $jwt->generate($header, $payload, 
             $this->getParameter('app.jwtsecret'));
 
            
            // on envoie un mail
            $mail->send(

                'no-reply@monsite.net',
                $user->getEmail(),
                'Activation de vortre compte sur le site e-commerce',
                'register',
                compact('user', 'token')
               
            );

            $this->addFlash('success', 'Email de vérification envyoé');
            return $this->redirectToRoute('profile_index');
    }

}
