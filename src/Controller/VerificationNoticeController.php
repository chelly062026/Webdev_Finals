<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VerificationNoticeController extends AbstractController
{
    #[Route('/verify-notice', name: 'app_verify_notice')]
    public function index(): Response
    {
        return $this->render('404.html.twig', [
            'status_code' => 403, // Technically a Forbidden, but using your 404 template
            'status_text' => 'Account Not Verified',
            'verificationUrl' => $this->generateUrl('app_verify_email') // Assuming you have a route for resending verification,
        ]);
    }
    
}