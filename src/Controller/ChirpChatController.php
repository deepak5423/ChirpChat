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
     * This constructor is used to initializing the object and also provides the
     * access to EntityManagerInterface
     * 
     * @param object $em
     *   Request object handles parameter from query parameter.
     * 
     * @return void
     */
    public function __construct(EntityManagerInterface $em){
        $this->em = $em;
    }

    /**
     * This routes opens the main page if user logged in and if not logged in 
     * it will open the login page.
     *
     * @Route("/", name="main")
     *   This routes opens the main page.
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
     * @Route("/dataLoad", name="dataLoad")
     *   This routes gives us the require data.
     *
     * @param object $session
     *   Session object store session variable.
     *
     * @return Response
     *   Returns array of data from login table.
     */
    #[Route('/dataLoad', name: 'dataLoad')]
    public function dataLoad(SessionInterface $session) {
        $Data = $this->em->getRepository(login::class);
        $email = $session->get('email');
        $showDatas = $Data->findOneBy(['email' => $email],[]);
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
     * @Route("/onlineUser", name="onlineUser")
     *   This routes give us list of online and ofline user.
     *
     * @return Response
     *   Returns array of online and offlineuser data from login table.
     */
    
    #[Route('/onlineUser', name: 'onlineUser')]
    public function onlineUser()
    {
        $userOnline = $this->em->getRepository(login::class);
        $users = $userOnline->findAll();
        $arr = [];

        forEach($users as $user) {
            $arr[] = [
                'id' => $user->getId(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'status' => $user->getStatus(),
                'img' => $user->getImg(),
            ];
        }
        return new JsonResponse(['userOnline' => $arr]);
    }

    /**
     * This routes returns the current posts and post information.
     *
     * @Route("/post", name="post")
     *   This routes returns the current posts and post information.
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
            $PostDetails = new Posts();
            $PostDetails->setPostDetails($post);
            $PostDetails->setDetails($this->em->getRepository(login::class)->findOneBy(['email'=> $emailId],[]));
            $this->em->persist($PostDetails);
            $this->em->flush();
        }
        $allPosts = $this->em->getRepository(Posts::class);
        $allPostsDetails = $allPosts->findBy([],['id' => 'DESC']);
        $arr = [];

        forEach($allPostsDetails as $singlePost) {
            $arr2 = [];
            forEach($singlePost->getLikeDetails() as $likeDislikeDel) {
                $arr2[] = [
                    'likeColor' => $likeDislikeDel->getThUp(),
                    'dislikeColor' => $likeDislikeDel->getThDown(),
                ];
            }
            $arr[] = [
                'id' => $singlePost->getId(),
                'title' => $singlePost->getPostDetails(),
                'firstname' => $singlePost->getDetails()->getFirstName(),
                'lastname' => $singlePost->getDetails()->getLastName(),
                'img' => $singlePost->getDetails()->getImg(),
                'email' => $singlePost->getDetails()->getEmail(),
                'thumsUp' => $singlePost->getThumsUp(),
                'thumsDown' => $singlePost->getThumsDown(),
                'loginEmail' => $emailId,
                'likeDislikeColor' => $arr2,
            ];
            break;
        }
        return new JsonResponse(['posts' => $arr]);
    }

    /**
     * This routes returns all the posts and post information.
     *
     * @Route("/showpost", name="showpost")
     *   This routes returns all the posts and post information.
     * 
     * @param object $session
     *   Session object store session variable.
     *  
     * @return Response
     *   Returns array of post and post information.
     */
    #[Route('/showpost', name: 'showpost')]
    public function showpost(SessionInterface $session) {
        $allPostsD = $this->em->getRepository(Posts::class);
        $showAllPostsDetails = $allPostsD->findAll();

        $emailId = $session->get('email');
        $arr = [];

        forEach($showAllPostsDetails as $singlePost) {

            $arr2 = [];
            forEach($singlePost->getLikeDetails() as $likeDislikeDel) {
                $arr2[] = [
                    'likeColor' => $likeDislikeDel->getThUp(),
                    'dislikeColor' => $likeDislikeDel->getThDown(),
                ];
            }
            $arr[] = [
                'id' => $singlePost->getId(),
                'title' => $singlePost->getPostDetails(),
                'firstname' => $singlePost->getDetails()->getFirstName(),
                'lastname' => $singlePost->getDetails()->getLastName(),
                'img' => $singlePost->getDetails()->getImg(),
                'email' => $singlePost->getDetails()->getEmail(),
                'thumsUp' => $singlePost->getThumsUp(),
                'thumsDown' => $singlePost->getThumsDown(),
                'loginEmail' => $emailId,
                'likeDislikeColor' => $arr2,
            ];
        }
        return new JsonResponse(['posts' => $arr]);
    }
    /**
     * This route Delete the post.
     *
     * @Route("/deletePost", name="deletePost")
     *   This route Delete the post.
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
            $deleteRec = $this->em->getRepository(Posts::class);
            $deleteCommentsRec = $this->em->getRepository(Comments::class);
            $deletedCommentsRec = $deleteCommentsRec->findOneBy(['commentsDetails' => $id], []);
            if ($deletedCommentsRec) {
                $this->em->remove($deletedCommentsRec);
            }
            $deletedRec = $deleteRec->findOneBy(['id' => $id],[]);
            $this->em->remove($deletedRec);
            $this->em->flush();
            return new Response('done');
        }
            return new Response('Failed');
        
    }

    /**
     * This route Edit the post.
     *
     * @Route("/editPost", name="editPost")
     *   This route Edit the post.
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
            $editRec = $this->em->getRepository(Posts::class);
            $editedRec = $editRec->findOneBy(['id' => $id],[]);
            $editedRec->setPostDetails($editVal);
           
            $this->em->flush();
            return new Response('done');
        }
        else {
            return new Response('Failed');
        }
    }
    
    /**
     * This routes returns the current comment and comment information.
     *
     * @Route("/addComment", name="addComment")
     *   This routes returns the current comment and comment information.
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
            $commentsDetails = new Comments();
            $commentsDetails->setComments($addcomments);
            $commentsDetails->setCommentsDetails($this->em->getRepository(Posts::class)->find($id));
            $commentsDetails->setLoginComments($this->em->getRepository(Login::class)->findOneBy(['email' => $emailId], []));
            $this->em->persist($commentsDetails);
            $this->em->flush();
            
            $allCommentsD = $this->em->getRepository(Posts::class);
            $showAllCommentsDetails = $allCommentsD->find($id);
            $arr = [];

            forEach($showAllCommentsDetails->getComments() as $singleComment) {
                $arr[] = [
                    "id" => $singleComment->getId(),
                    "title" => $singleComment->getComments(),
                    "firstname" => $singleComment->getLoginComments()->getFirstName(),
                    "lastname" => $singleComment->getLoginComments()->getLastName(),
                    "img" => $singleComment->getLoginComments()->getImg(),
                    "email" => $singleComment->getLoginComments()->getEmail(),
                    "loginEmail" => $emailId,
                ];
            }
            array_reverse($arr);
            return new JsonResponse(['comm' => $arr]);
        }
        else {
            return new Response('Failed');
        }
    }

    /**
     * This routes returns the all comments and comments information.
     *
     * @Route("/showComments", name="showComments")
     *   This routes returns all the comments and comments information.
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
        
            $allCommentsD = $this->em->getRepository(Posts::class);
            $showAllCommentsDetails = $allCommentsD->find($id);

            $emailId = $session->get('email');
            $arr = [];

            forEach($showAllCommentsDetails->getComments() as $singleComment) {
                $arr[] = [
                    "id" => $singleComment->getId(),
                    "title" => $singleComment->getComments(),
                    "firstname" => $singleComment->getLoginComments()->getFirstName(),
                    "lastname" => $singleComment->getLoginComments()->getLastName(),
                    "img" => $singleComment->getLoginComments()->getImg(),
                    "email" => $singleComment->getLoginComments()->getEmail(),
                    "loginEmail" => $emailId,
                ];
            }
        }
        return new JsonResponse(['comm' => $arr]);
    
    }

    /**
     * This route Delete the comment.
     *
     * @Route("/deletePost", name="deletePost")
     *   This route Delete the comment.
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
            $deleteCommentsRec = $this->em->getRepository(Comments::class);
            $deletedCommentsRec = $deleteCommentsRec->find($id);
            $this->em->remove($deletedCommentsRec);
            $this->em->flush();
            return new Response('done');
        }
        else {
            return new Response('Failed');
        }
    }

    /**
     * This route Edit the comment.
     *
     * @Route("/editComm", name="editComm")
     *   This route Edit the comment.
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
            $editRec = $this->em->getRepository(Comments::class);
            $editedRec = $editRec->findOneBy(['id' => $id],[]);
            $editedRec->setComments($editVal);
           
            $this->em->flush();
            return new Response('done');
        }
        else {
            return new Response('Failed');
        }
    }

    /**
     * This route dislike the post.
     *
     * @Route("/dislike", name="dislike")
     *   This route dislike the post.
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
            $editRec = $this->em->getRepository(Posts::class);
            $editedRec = $editRec->find($id);
            $editedRec->setThumsDown($editedRec->getThumsDown() + $dis);
            $thumpsDown = $editedRec->getThumsDown();
            //$LikeDislike = $this->em->getRepository(LikeDislike::class);
            $loginId = $this->em->getRepository(Login::class)->findOneBy(['email' => $emailId])->getId();
            $LikeDislikecheck = $this->em->getRepository(LikeDislike::class)->findOneBy(['likeDislike' => $loginId, 'postDel' => $id],[]);
            if ($LikeDislikecheck) {
                $LikeDislikecheck->setThDown("blue");
                $LikeDislikecheck->setThUp("black");
            }
            else {
                $LikeDislike = new LikeDislike();
                $LikeDislike->setThDown("blue");
                $LikeDislike->setThUp("black");
                $LikeDislike->setPostDel($this->em->getRepository(Posts::class)->find($id));
                $LikeDislike->setLikeDislike($this->em->getRepository(Login::class)->findOneBy(['email' => $emailId], []));
                $this->em->persist($LikeDislike);
            }
            $this->em->flush();
            return new Response($thumpsDown);
        }
            return new Response('Failed');
    }

    /**
     * This route remove dislike from the post.
     *
     * @Route("/dislikeRemove", name="dislikeRemove")
     *   This route remove dislike from the post.
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
            $editRec = $this->em->getRepository(Posts::class);
            $editedRec = $editRec->find($id);
            $editedRec->setThumsDown($editedRec->getThumsDown() - $dis);
            $thumpsDown = $editedRec->getThumsDown();
            $loginId = $this->em->getRepository(Login::class)->findOneBy(['email' => $emailId])->getId();
            $LikeDislike = $this->em->getRepository(LikeDislike::class)->findOneBy(['likeDislike' => $loginId, 'postDel' => $id],[]);
            $LikeDislike->setThDown("black");
            $LikeDislike->setThUp("black");
            $LikeDislike->setPostDel($this->em->getRepository(Posts::class)->find($id));           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        else {
            return new Response('Failed');
        }
    }

    /**
     * This route like the post.
     *
     * @Route("/like", name="like")
     *   This route dislike the post.
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
            $editRec = $this->em->getRepository(Posts::class);
            $editedRec = $editRec->find($id);
            $editedRec->setThumsUp($editedRec->getThumsUp() + $dis);
            $thumpsDown = $editedRec->getThumsUp();
            //$LikeDislike = $this->em->getRepository(LikeDislike::class);
            $loginId = $this->em->getRepository(Login::class)->findOneBy(['email' => $emailId])->getId();
            $LikeDislikecheck = $this->em->getRepository(LikeDislike::class)->findOneBy(['likeDislike' => $loginId, 'postDel' => $id],[]);
            if ($LikeDislikecheck) {
                $LikeDislikecheck->setThDown("black");
                $LikeDislikecheck->setThUp("blue");
            }
            else {
                $LikeDislike = new LikeDislike();
                $LikeDislike->setThDown("black");
                $LikeDislike->setThUp("blue");
                $LikeDislike->setPostDel($this->em->getRepository(Posts::class)->find($id));
                $LikeDislike->setLikeDislike($this->em->getRepository(Login::class)->findOneBy(['email' => $emailId], []));
                $this->em->persist($LikeDislike);
            }           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        else {
            return new Response('Failed');
        }
    }

    /**
     * This route remove like from the post.
     *
     * @Route("/likeRemove", name="likeRemove")
     *   This route remove like from the post.
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
            $editRec = $this->em->getRepository(Posts::class);
            $editedRec = $editRec->find($id);
            $editedRec->setThumsUp($editedRec->getThumsUp() - $dis);
            $thumpsDown = $editedRec->getThumsUp();
            $loginId = $this->em->getRepository(Login::class)->findOneBy(['email' => $emailId])->getId();
            $LikeDislike = $this->em->getRepository(LikeDislike::class)->findOneBy(['likeDislike' => $loginId, 'postDel' => $id],[]);
            $LikeDislike->setThDown("black");
            $LikeDislike->setThUp("black");
            $LikeDislike->setPostDel($this->em->getRepository(Posts::class)->find($id));           
            $this->em->flush();
            return new Response($thumpsDown);
        }
        else {
            return new Response('Failed');
        }
    }
}