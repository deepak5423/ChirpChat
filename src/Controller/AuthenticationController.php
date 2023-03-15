<?php

namespace App\Controller;

use App\Entity\Login;
use App\Entity\OTP;
use App\Entity\Posts;
use App\Services\DisplayImg;
use App\Services\EmailChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * The Authentication controller manages all the Authentication related 
 * operations like login, register, reset password etc. Otp verification is 
 * added.
 * 
 * @package ORM
 * @subpackage Doctrine
 * 
 * @author Deepak Pandey <deepak.pandey@innoraft.com>
 */
class AuthenticationController extends AbstractController
{
    /**
     * @var object
     *    Request object handles parameter from query parameter.
     */
    private $em;

    /**
     * This constructor is used to initializing the object and also provides the
     * access to EntityManagerInterface
     * 
     * @param object $em
     *   Request object handles parameter from query parameter.
     * 
     * @return void
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * This routes opens the login page.
     *
     * @Route("/login", name="login")
     *   This routes opens the login page.
     *
     * @return Response
     *   Returns login page.
     */
    #[Route('/login', name: 'login')]
    public function login()
    {
        return $this->render(view: 'login/login.html.twig');
    }

    /**
     * This routes opens the Password Reset page.
     *
     * @Route("/PasswordReset{token}", name="PasswordReset")
     *   This routes opens the Password Reset page.
     * 
     * @param $token
     *   Encripted userId for reseting password.
     * 
     * @return Response
     *   Returns Password Reset page with encoded password.
     */
    #[Route('/PasswordReset/{token}', name: 'PasswordReset')]
    public function passwordReset($token)
    {
        return $this->render('login/PasswordReset.html.twig', [
            "token" => $token
        ]);
    }

    /**
     * This routes Reset the Password.
     *
     * @Route("/PasswordR", name="PasswordR")
     *   This routes Reset the Password.
     *
     * @param object $request
     *   Request object handles parameter from query parameter.
     * 
     * @return Response
     *   Returns message that the password is reset or not.
     */
    #[Route('/PasswordR', name: 'PasswordR')]
    public function passwordR(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $pass = $request->request->get('pass');
            $token = $request->request->get('token');
            $id = base64_decode($token);
            $login = $this->em->getRepository(login::class)->find($id);
            if ($login) {
                $ePass = base64_encode($pass);
                $login->setPassword($ePass);
                $this->em->persist($login);
                $this->em->flush();
                return new JsonResponse("Password Reset Successful");
            }
            return new JsonResponse("Data Not Found");
        }
        return new JsonResponse("Failed");
    }

    /**
     * This routes opens the ResetPass Page.
     *
     * @Route("/Reset", name="Reset")
     *   This routes opens the ResetPass Page.
     *
     * @return Response
     *   Returns ResetPass Page.
     */
    #[Route('/Reset', name: 'Reset')]
    public function reset()
    {
        return $this->render(view: 'login/ResetPass.html.twig');
    }

    /**
     * This routes sends the resend password link to user emailId.
     *
     * @Route("/ResetPass", name="ResetPass")
     *   This routes sends the resend password link to user emailId.
     *
     * @return Response
     *   Returns message that the email is send or not.
     */
    #[Route('/ResetPass', name: 'ResetPass')]
    public function resetPass(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $email = $request->request->get('email');

            $login = $this->em->getRepository(login::class)->findOneBy(['email' => $email], []);
            if (!$login) {
                return new JsonResponse('User Not Exists !!');
            }
            $userId = $login->getId();

            $emailSend = new EmailChecker($email, 'Welcome to innoraft Your PasswordReset Link is here : ' . "deepak.com/PasswordReset/" . base64_encode($userId), 'Password Reset Link ');
            $emailSendStatus = $emailSend->emailSend();
            if ($emailSendStatus) {
                return new JsonResponse("Reset Password Link send to your email id");
            }
            return new JsonResponse("Failed");
        }
        return new JsonResponse("Failed");
    }

    /**
     * This routes checks the emailid and password is correct or not.
     *
     * @Route("/login-match", name="login_match")
     *   This routes checks the emailid and password is correct or not.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns message that the passwoard is correct or not.
     */
    #[Route('/login-match', name: 'login_match')]
    public function loginCheck(Request $request, SessionInterface $session)
    {
        if ($request->isXmlHttpRequest()) {
            $email = $request->request->get('email');
            $pass = $request->request->get('pass');
            $dPass = base64_encode($pass);
            $login = $this->em->getRepository(login::class);
            $check = $login->findOneBy(['email' => $email, 'password' => $dPass], []);
            if ($check) {
                $session->set('email', $email);
                $check->setStatus(1);
                $this->em->persist($check);
                $this->em->flush();
                return new JsonResponse('done');
            }
            return new JsonResponse('Worng email-id OR password !!');
        }
        return new JsonResponse('Failed');
    }

    /**
     * This routes opens the SignUp page.
     *
     * @Route("/signup", name="signup")
     *   This routes opens the signup page.
     *
     * @return Response
     *   Returns signup page.
     */
    #[Route('/signup', name: 'signUp')]
    public function signUp()
    {
        return $this->render(view: 'login/signup.html.twig');
    }

    /**
     * This route create a new user account and then redirect the it to 
     * home page.
     *
     * @Route("/newAccount", name="new_Account")
     *   This route create a new user account and then redirect the it to 
     *   home page.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns error message if some errors occurs while creating account 
     *   or else return to home page.
     */
    #[Route('/newAccount', name: 'new_Account')]
    public function newAccount(Request $request, SessionInterface $session)
    {
        if ($request->isXmlHttpRequest()) {
            $firstName = $request->request->get('fname');
            $lastName = $request->request->get('lname');
            $gender = $request->request->get('gender');
            $image = $request->files->get('image');
            $about = $request->request->get('abotYou');
            $otpUser = $request->request->get('otp');
            $email = $request->request->get('email');
            $pass = $request->request->get('pass');
            $conPass = $request->request->get('confirmPass');

            $login = $this->em->getRepository(login::class);
            $check = $login->findOneBy(['email' => $email], []);
            $otp = $this->em->getRepository(OTP::class);
            $otpCheck = $otp->findOneBy(['emailId' => $email, 'Otp' => $otpUser], []);
            if (!$image) {
                $image = "";
            }
            $imageLocation = new DisplayImg($image, $email, $gender);
            $imagePath = $imageLocation->checkingImg();

            if ($pass !== $conPass) {
                return new JsonResponse('Wrong Password !!');
            } elseif (!$otpCheck) {
                return new JsonResponse('Wrong OTP Enter.');
            } elseif (!$check) {
                $ePass = base64_encode($pass);
                $Login = new Login();
                $Login->setFirstName($firstName);
                $Login->setLastName($lastName);
                $Login->setGender($gender);
                $Login->setImg($imagePath);
                $Login->setAbout($about);
                $Login->setEmail($email);
                $Login->setPassword($ePass);
                $Login->setStatus('1');
                $otpCheck->setValidity('1');
                $this->em->persist($otpCheck);
                $this->em->persist($Login);
                $this->em->flush();
                $session->set('email', $email);
                return new JsonResponse("done");

            }
                return new JsonResponse('Email-Id Already Exist !!');
            
        }
            return new JsonResponse('Failed');
        
    }

    /**
     * This route sends otp to user email id and also stores it to database.
     *
     * @Route("/otpSend", name="otpSend")
     *   This route sends otp to user email id and also stores it to database.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns message of otp status.
     */
    #[Route('/otpSend', name: 'otpSend')]
    public function sendOtp(Request $request)
    {
        if ($request->isXmlHttpRequest()) {

            $email = $request->request->get('email');
            $otp = rand(100000, 999999);

            $emailSend = new EmailChecker($email, 'Your one time password (OTP) is ' . $otp, 'Welcome to innoraft Your OTP');

            $emailSendStatus = $emailSend->emailSend();
            $otpClass = $this->em->getRepository(OTP::class);
            $otpCheck = $otpClass->findOneBy(['emailId' => $email], []);

            if (!$emailSendStatus) {
                return new JsonResponse('OTP not send try again');

            } elseif ($otpCheck) {
                $otpCheck->setOtp($otp);
                $this->em->persist($otpCheck);
                $this->em->flush();
                return new JsonResponse('OTP Send to your email address');
            }

            $OTP = new OTP();
            $OTP->setEmailId($email);
            $OTP->setOtp($otp);
            $OTP->setValidity('0');
            $this->em->persist($OTP);
            $this->em->flush();
            return new JsonResponse('OTP Send to your email address');
        } 
            return new JsonResponse('Failed');
        
    }

    /**
     * This route destroys the session and redirect the page to login page.
     *
     * @Route("/logout", name="logout")
     *   This route destroys the session.
     *
     * @return Response
     *   Returns login page.
     */
    #[Route('/logout', name: 'logout')]
    public function logout(SessionInterface $session)
    {
        $login = $this->em->getRepository(login::class);
        $emailId = $session->get('email');

        $check = $login->findOneBy(['email' => $emailId], []);
        $check->setStatus('0');

        $this->em->persist($check);
        $this->em->flush();
        $session->clear();

        return $this->render(view: 'login/login.html.twig');
    }
}