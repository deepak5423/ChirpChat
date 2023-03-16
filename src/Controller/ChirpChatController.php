<?php
namespace App\Controller;

session_start();

use App\Entity\Comments;
use App\Entity\LikeDislike;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Entity\Posts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Login;
use App\Services\UserOperations;
use Symfony\Component\HttpFoundation\Request;

/**
 * The ChirpChat controller manages all the nessary things required for main
 * page like data loading, adding post, adding comments, showing comments etc.
 * 
 * @package ORM
 * @subpackage Doctrine
 * 
 * @author Deepak Pandey <deepak.pandey@innoraft.com>
 */
class ChirpChatController extends AbstractController
{
    /**
     * @var object
     *    Request object handles parameter from query parameter.
     */
    private $em;

    /**
     * @var object
     *    Instance of login Repository.
     */
    private $login;

    /**
     * @var object
     *    Instance of Posts Repository.
     */
    private $allPosts;

    /**
     * @var object
     *    Instance of Comments Repository.
     */
    private $comments;

    /**
     * @var object
     *    Instance of LikeDislike Repository.
     */
    private $likeDislike;

    /**
     * @var object
     *    Object of Posts Repository.
     */
    private $postDetails;

    /**
     * @var object
     *    Object of Comments Repository.
     */
    private $commentsDetails;

    /**
     * @var object
     *    Object of LikeDislike Repository.
     */
    private $likeDis;

    /**
     * This constructor is used to initializing the object and also provides the
     * access to EntityManagerInterface
     * 
     * @param object $em
     *   Request object handles parameter from query parameter.
     * @param object $login
     *   Instance of login Repository.
     * @param object $allPosts
     *   Instance of Posts Repository.
     * @param object $comments
     *   Instance of Comments Repository.
     * @param object $likeDislike
     *   Instance of LikeDislike Repository.
     * @param object $postDetails
     *   Object of Posts Repository.
     * @param object $commentsDetails
     *   Object of Comments Repository.
     * @param object $likeDis
     *   Object of LikeDislike Repository.
     * 
     * @return void
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
        $this->login = $this->em->getRepository(login::class);
        $this->allPosts = $this->em->getRepository(Posts::class);
        $this->comments = $this->em->getRepository(Comments::class);
        $this->likeDislike = $this->em->getRepository(LikeDislike::class);
        $this->postDetails = new Posts();
        $this->commentsDetails = new Comments();
        $this->likeDis = new LikeDislike();
    }

    /**
     * This routes opens the main page if user logged in and if not logged in 
     * it will open the login page.
     *
     * @param object $session
     *   Session object store session variable.
     *
     * @return Response
     *   Returns main or login page.
     */
    #[Route('/', name: 'main')]
    public function main(SessionInterface $session)
    {
        $email = $session->get('email');
        if (isset($email)) {
            return $this->render(view:'main/index.html.twig');
        }
        return $this->render(view:'login/login.html.twig');
    }

    /**
     * This routes returns the all the required data from login table.
     *
     * @param object $session
     *   Session object store session variable.
     *
     * @return Response
     *   Returns array of data from login table.
     */
    #[Route('/dataLoad', name: 'dataLoad')]
    public function dataLoad(SessionInterface $session) {
        $email = $session->get('email');
        $showDatas = $this->login->findOneBy(['email' => $email]);
        $dataArr = [];
        $dataArr[] = [
            'id' => $showDatas->getId(),
            'firstname' => $showDatas->getFirstName(),
            'lastname' => $showDatas->getLastName(),
            'img' => $showDatas->getImg(),
            'about' => $showDatas->getAbout(),
            'email' => $showDatas->getEmail(),
        ];
        return new JsonResponse(['dataArr' => $dataArr]);
    }

    /**
     * This routes returns the list of online and ofline user.
     *
     * @return Response
     *   Returns array of online and offlineuser data from login table.
     */
    
    #[Route('/onlineUser', name: 'onlineUser')]
    public function onlineUser()
    {
        $userOnline = $this->login;
        $users = $userOnline->findAll();

        $user = new UserOperations();
        $onlineUser = $user->getOnlineUser($users);
        return new JsonResponse(['userOnline' => $onlineUser]);
    }

    /**
     * This routes returns the current posts and post information.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns array of current post and post information.
     */
    #[Route('/post', name: 'post')]
    public function post(Request $request , SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $post = $request->request->get('post');
            $emailId = $session->get('email');
            $this->postDetails->setPostDetails($post);
            $this->postDetails->setDetails($this->login->findOneBy(['email'=> $emailId],[]));
            $this->em->persist($this->postDetails);
            $this->em->flush();
        }
        $allPostsDetails = $this->allPosts->findAll();

        $user = new UserOperations();
        $currentPosts = $user->getCurrentPost($emailId, $allPostsDetails);
        return new JsonResponse(['posts' => $currentPosts]);
    }

    /**
     * This routes returns all the posts and post information.
     * 
     * @param object $session
     *   Session object store session variable.
     *  
     * @return Response
     *   Returns array of post and post information.
     */
    #[Route('/showpost', name: 'showpost')]
    public function showpost(SessionInterface $session) {
        $allPostsD = $this->allPosts;
        $showAllPostsDetails = $allPostsD->findAll();

        $emailId = $session->get('email');

        $user = new UserOperations();
        $allPosts = $user->getAllPosts($emailId, $showAllPostsDetails);
        return new JsonResponse(['posts' => $allPosts]);
    }
    /**
     * This route Delete the post.
     * 
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a message that the post is deleted or not.
     */
    #[Route('/deletePost', name: 'deletePost')]
    public function deletePost(Request $request) {
        if ($request->isXmlHttpRequest()) {
            $id = $request->request->get('i');
            $deleteRec = $this->allPosts;
            $deletedCommentsRec = $this->comments->findOneBy(['commentsDetails' => $id]);
            if ($deletedCommentsRec) {
                $this->em->remove($deletedCommentsRec);
            }
            $deletedRec = $deleteRec->findOneBy(['id' => $id]);
            $this->em->remove($deletedRec);
            $this->em->flush();
            return new Response('done');
        }
        return new Response('Failed');        
    }

    /**
     * This route Edit the post.
     * 
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a message that the post is edited or not.
     */
    #[Route('/editPost', name: 'editPost')]
    public function editPost(Request $request) {
        if ($request->isXmlHttpRequest()) {
            $editVal = $request->request->get('afterEdit');
            $id = $request->request->get('i');
            $editRec = $this->allPosts;
            $editedRec = $editRec->findOneBy(['id' => $id]);
            $editedRec->setPostDetails($editVal);
           
            $this->em->flush();
            return new Response('done');
        }
        return new Response('Failed');
    }
    
    /**
     * This routes returns the current comment and comment information.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns array of current comment and comment information.
     */
    #[Route('/addComment', name: 'addComment')]
    public function addComment(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $addcomments = $request->request->get('addComment');
            $id = $request->request->get('i');
            $emailId = $session->get('email');
            $this->commentsDetails->setComments($addcomments);
            $this->commentsDetails->setCommentsDetails($this->allPosts->find($id));
            $this->commentsDetails->setLoginComments($this->login->findOneBy(['email' => $emailId]));
            $this->em->persist($this->commentsDetails);
            $this->em->flush();
            
            $allCommentsD = $this->allPosts;
            $showAllCommentsDetails = $allCommentsD->find($id);
            $user = new UserOperations();
            $currentComments = $user->getComments($emailId, $showAllCommentsDetails);
            return new JsonResponse(['comm' => $currentComments]);
        }
        return new Response('Failed');
    }

    /**
     * This routes returns the all comments and comments information.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns array of all comments and comments information.
     */

    #[Route('/showComments', name: 'showComments')]
    public function showComments(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $id = $request->request->get('i');
        
            $allCommentsD = $this->allPosts;
            $showAllCommentsDetails = $allCommentsD->find($id);

            $emailId = $session->get('email');
            $user = new UserOperations();
            $allComments = $user->getComments($emailId, $showAllCommentsDetails);
        }
        return new JsonResponse(['comm' => $allComments]);
    }

    /**
     * This route Delete the comment.
     * 
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a message that the comment is deleted or not.
     */
    #[Route('/deleteComm', name: 'deleteComm')]
    public function deleteComm(Request $request) {
        if ($request->isXmlHttpRequest()) {
            $id = $request->request->get('i');
            $deletedCommentsRec = $this->comments->find($id);
            $this->em->remove($deletedCommentsRec);
            $this->em->flush();
            return new Response('done');
        }
        return new Response('Failed');
    }

    /**
     * This route Edit the comment.
     * 
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a message that the comment is edited or not.
     */
    #[Route('/editComm', name: 'editComm')]
    public function editComm(Request $request) {
        if ($request->isXmlHttpRequest()) {
            $editVal = $request->request->get('afterEdit');
            $id = $request->request->get('i');
            $editedRec = $this->comments->findOneBy(['id' => $id]);
            $editedRec->setComments($editVal);
           
            $this->em->flush();
            return new Response('done');
        }
        return new Response('Failed');
    }

    /**
     * This route dislike the post.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a count of dislike.
     */
    #[Route('/dislike', name: 'dislike')]
    public function dislike(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $dis = $request->request->get('dis');
            $id = $request->request->get('id');
            $emailId = $session->get('email');
            $editRec = $this->allPosts;
            $editedRec = $editRec->find($id);
            $editedRec->setThumsDown($editedRec->getThumsDown() + $dis);
            $thumpsDown = $editedRec->getThumsDown();
            $loginId = $this->login->findOneBy(['email' => $emailId])->getId();
            $likeDislikecheck = $this->likeDislike->findOneBy(['likeDislike' => $loginId, 'postDel' => $id]);
            if ($likeDislikecheck) {
                $likeDislikecheck->setThDown("blue");
                $likeDislikecheck->setThUp("black");
            }
            else {
                $this->likeDis->setThDown("blue");
                $this->likeDis->setThUp("black");
                $this->likeDis->setPostDel($this->allPosts->find($id));
                $this->likeDis->setLikeDislike($this->login->findOneBy(['email' => $emailId]));
                $this->em->persist($this->likeDis);
            }
            $this->em->flush();
            return new Response($thumpsDown);
        }
        return new Response('Failed');
    }

    /**
     * This route remove dislike from the post.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a count of dislike after dislike.
     */
    #[Route('/dislikeRemove', name: 'dislikeRemove')]
    public function dislikeRemove(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $dis = $request->request->get('dis');
            $id = $request->request->get('id');
            $emailId = $session->get('email');
            $editRec = $this->allPosts;
            $editedRec = $editRec->find($id);
            $editedRec->setThumsDown($editedRec->getThumsDown() - $dis);
            $thumpsDown = $editedRec->getThumsDown();
            $loginId = $this->login->findOneBy(['email' => $emailId])->getId();
            $likeDislike = $this->likeDislike->findOneBy(['likeDislike' => $loginId, 'postDel' => $id]);
            $likeDislike->setThDown("black");
            $likeDislike->setThUp("black");
            $likeDislike->setPostDel($this->allPosts->find($id));           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        return new Response('Failed');
    }

    /**
     * This route like the post.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a count of like.
     */
    #[Route('/like', name: 'like')]
    public function like(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $dis = $request->request->get('dis');
            $id = $request->request->get('id');
            $emailId = $session->get('email');
            $editRec = $this->allPosts;
            $editedRec = $editRec->find($id);
            $editedRec->setThumsUp($editedRec->getThumsUp() + $dis);
            $thumpsDown = $editedRec->getThumsUp();
            $loginId = $this->login->findOneBy(['email' => $emailId])->getId();
            $likeDislikecheck = $this->likeDislike->findOneBy(['likeDislike' => $loginId, 'postDel' => $id]);
            if ($likeDislikecheck) {
                $likeDislikecheck->setThDown("black");
                $likeDislikecheck->setThUp("blue");
            }
            else {
                $this->likeDis->setThDown("black");
                $this->likeDis->setThUp("blue");
                $this->likeDis->setPostDel($this->allPosts->find($id));
                $this->likeDis->setLikeDislike($this->login->findOneBy(['email' => $emailId]));
                $this->em->persist($this->likeDis);
            }           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        return new Response('Failed');
    }

    /**
     * This route remove like from the post.
     * 
     * @param object $session
     *   Session object store session variable.
     * @param object $request
     *   Request object handles parameter from query parameter.
     *  
     * @return Response
     *   Returns a count of like after like.
     */
    #[Route('/likeRemove', name: 'likeRemove')]
    public function likeRemove(Request $request, SessionInterface $session) {
        if ($request->isXmlHttpRequest()) {
            $dis = $request->request->get('dis');
            $id = $request->request->get('id');
            $emailId = $session->get('email');
            $editRec = $this->allPosts;
            $editedRec = $editRec->find($id);
            $editedRec->setThumsUp($editedRec->getThumsUp() - $dis);
            $thumpsDown = $editedRec->getThumsUp();
            $loginId = $this->login->findOneBy(['email' => $emailId])->getId();
            $likeDislike = $this->likeDislike->findOneBy(['likeDislike' => $loginId, 'postDel' => $id]);
            $likeDislike->setThDown("black");
            $likeDislike->setThUp("black");
            $likeDislike->setPostDel($this->allPosts->find($id));           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        return new Response('Failed');
    }
}