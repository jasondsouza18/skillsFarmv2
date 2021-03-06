<?php

namespace App\Repository;

use App\Entity\JobseekerResume;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method JobseekerResume|null find($id, $lockMode = null, $lockVersion = null)
 * @method JobseekerResume|null findOneBy(array $criteria, array $orderBy = null)
 * @method JobseekerResume[]    findAll()
 * @method JobseekerResume[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class JobseekerResumeRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, JobseekerResume::class);
    }

    public function updateJobseekerResume($request, $jobseeker,$fileName,$uploadsdrirectory)
    {
        $dm = $this->getEntityManager();
        $jobseekerResume = self::find($request['cvid']);
        if (!($jobseekerResume instanceof JobseekerResume))
            $jobseekerResume = new JobseekerResume();
        $jobseekerResume->setItPriority('1');
        $jobseekerResume->setJobseeker($jobseeker);
        $jobseekerResume->setVcCoverletter($request['coverletter']);
        $jobseekerResume->setVcCvpath($uploadsdrirectory.$fileName);
        $jobseekerResume->setItCvstatus('1');
        $jobseekerResume->setVcCvname($request['cvname'].$fileName);
        $dm->persist($jobseekerResume);
        $dm->flush($jobseekerResume);
        return $jobseekerResume;
    }

    public function inactivateResume($resume)
    {
        $dm = $this->getEntityManager();
        $resume->setItCvstatus('0');
        $dm->persist($resume);
        $dm->flush($resume);
        return $resume;
    }
}
