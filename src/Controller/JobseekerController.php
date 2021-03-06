<?php

namespace App\Controller;

use App\Entity\CVManagement;
use App\Entity\MasterCategory;
use App\Form\EditProfileType;
use App\Repository\CVManagementRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Jobseeker;
use App\Entity\JobseekerEducation;
use App\Entity\JobseekerResume;
use App\Entity\MasterEducation;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;


/**
 * @Route("/jobseeker")
 */
class JobseekerController extends Controller
{

    /**
     * @Route("/signup", name="_jobseeker_signup")
     */
    public function signup(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $jobseeker = $this->getUser();
        if ($jobseeker != null)
            return $this->render('login.html.twig');
        if ($request->getMethod() == "POST") {
            $jobseeker = new Jobseeker();
            $password = $request->request->get('pass');
            $firstname = $request->request->get('firstname');
            $surname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $login = $request->request->get('login');
            $res = $this->getDoctrine()->getRepository(Jobseeker::class)->validateLoginandEmail($login, $email);
            if ($res != "true") {
                $sent = $res;
                return $this->redirectToRoute('_home', array('sent' => $sent));
            } else {
                $password = $passwordEncoder->encodePassword($jobseeker, $password);
                $entityManager = $this->getDoctrine()->getManager();
                $jobseeker->setVcPassword($password);
                $jobseeker->setVcLogin($login);
                $jobseeker->setVcFirstname($firstname);
                $jobseeker->setVcSurname($surname);
                $jobseeker->setVcEmail($email);
                $jobseeker->setItJobseekerstatus(1);
                $jobseeker->setBoSubscriptionletter(1);
                $entityManager->persist($jobseeker);
                $entityManager->flush($jobseeker);
                $sent = "success";
                return $this->redirectToRoute('_home', array('sent' => $sent));
            }
        }
    }


    /**
     * @Route("/home", name="_jobseeker_home")
     */
    public function index(Request $request)
    {
        $jobseeker = $this->getUser();
        $category = $this->getDoctrine()->getRepository(MasterCategory::class)->findAll();
        return $this->render('jobseeker/index.html.twig', [
            'category' => $category
        ]);
    }

    /**
     * @Route("/profile/edit", name="_jobseeker_profile_edit")
     */
    public function profileedit(Request $request)
    {
        $sent = 0;
        $jobseeker = $this->getUser();
        $entityManager = $this->getDoctrine()->getManager();
        $editjobseeker = $this->createForm(EditProfileType::class, $jobseeker);
        $editjobseeker->handleRequest($request);
        if ($request->getMethod() == 'POST') {
            $entityManager->persist($jobseeker);
            $entityManager->flush();
            $sent = 1;
        }
        return $this->render('jobseeker/editProfile.html.twig', [
            'form' => $editjobseeker->createView(),
            'sent' => $sent
        ]);
    }

    /**
     * @Route("/profile/edit/education", name="_jobseeker_profile_edit_education")
     */
    public function profileeditEducation(Request $request)
    {
        $sent = 0;
        $jobseeker = $this->getUser();
        if ($request->getMethod() == 'POST') {
            $request = $request->request->all();
            $oldID = $request['id'];
            $masterEducation = $this->getDoctrine()->getRepository(MasterEducation::class)->addintoMasterEducation($request);
            $jobseekerEducation = $this->getDoctrine()->getRepository(JobseekerEducation::class)->addintoEducation($request, $masterEducation, $jobseeker, $oldID);
            $sent = 1;
        }
        $education = $this->getDoctrine()->getRepository(JobseekerEducation::class)->findBy(['jobseeker' => $jobseeker->getId()]);
        return $this->render('jobseeker/editEducation.html.twig', array(
            'education' => $education,
            'sent' => $sent
        ));
    }

    /**
     * @Route("/logout", name="_jobseeker_logout")
     */
    public function logout(Request $request)
    {
    }

    /**
     * @Route("/profile/resume", name="_jobseeker_profile_resume")
     */
    public function profileeresume(Request $request)
    {
        $jobseeker = $this->getUser();
        $jobseekerresume = $this->getDoctrine()->getRepository(JobseekerResume::class)->findBy(['jobseeker' => $jobseeker->getId()]);
        if ($request->getMethod() == 'POST') {
            $year = date("Y");
            $rand = rand(0, 1000);
            $month = date('m');
            $requestpost = $request->request->all();
            $projectRoot = $this->get('kernel')->getProjectDir();
            $uploadsDirectory = $projectRoot . "/public/uploads/CVs/$year/$month/" . $requestpost['category'] . "/";
            $file = $request->files->get('cvfile');
            $fileName = $rand . $file->getClientOriginalName();
            $file->move($uploadsDirectory, $fileName);
            $category = $this->getDoctrine()->getRepository(MasterCategory::class)->find($requestpost['category']);
            $jobseekerresume = $this->getDoctrine()->getRepository(JobseekerResume::class)->updateJobseekerResume($requestpost, $jobseeker, $fileName, $uploadsDirectory);
            $addintoCVmanagenemnt = $this->getDoctrine()->getRepository(CVManagement::class)->addIntoTabledata($jobseeker, $fileName, $uploadsDirectory, $requestpost, $category);
        }
        $category = $this->getDoctrine()->getRepository(MasterCategory::class)->findAll();
        return $this->redirectToRoute('_jobseeker_home');
    }


    /**
     * @Route("/resume/download/{id}", name="_resume_download")
     */
    public function resumedownload(Request $request, $id)
    {
        $resume = $this->getDoctrine()->getRepository(JobseekerResume::class)->find($id);
        return $this->file($resume->getVccvpath(), $resume->getVccvname());
    }

    /**
     * @Route("/resume/inactive/{id}", name="_resume_inactivate")
     */
    public function inactivatestatus(Request $request, $id)
    {
        $resume = $this->getDoctrine()->getRepository(JobseekerResume::class)->find($id);
        $resume = $this->getDoctrine()->getRepository(JobseekerResume::class)->inactivateResume($resume);
        $category = $this->getDoctrine()->getRepository(MasterCategory::class)->findAll();
        return $this->redirectToRoute('_jobseeker_home');
    }
}
