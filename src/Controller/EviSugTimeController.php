<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EviSugTimeController extends AbstractController
{
    #[Route('/admin/reclamations/evisugtime', name: 'evisugtime')]
    public function index(): Response
    {
        $resultsFile = $this->getParameter('kernel.project_dir') . '/var/evisugtime_results.json';
        $results = file_exists($resultsFile) ? json_decode(file_get_contents($resultsFile), true) : [];

        return $this->render('evisugtime/index.html.twig', [
            'results' => $results,
        ]);
    }


    #[Route('/admin/reclamations/evisugtime/export-pdf', name: 'evisugtime_export_pdf')]
    public function exportPdf(): BinaryFileResponse
{
    $resultsFile = $this->getParameter('kernel.project_dir') . '/var/evisugtime_results.json';
    $results = file_exists($resultsFile) ? json_decode(file_get_contents($resultsFile), true) : [];

    $html = $this->renderView('evisugtime/pdf.html.twig', ['results' => $results]);
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();

    $output = $dompdf->output();
    $pdfPath = $this->getParameter('kernel.project_dir') . '/var/evisugtime_report.pdf';
    file_put_contents($pdfPath, $output);

    return new BinaryFileResponse($pdfPath);
}
}