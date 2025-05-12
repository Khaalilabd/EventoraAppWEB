<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Dompdf\Dompdf;

class EviSugTimeController extends AbstractController
{
    #[Route('/admin/reclamations/evisugtime', name: 'evisugtime')]
    public function index(): Response
    {
        $resultsFile = $this->getParameter('kernel.project_dir') . '/var/evisugtime_results.json';
        
        // Initialize default empty structure
        $defaultResults = [
            'clusters' => [],
            'suggestions' => [],
            'chart' => [
                'labels' => [],
                'data' => []
            ],
            'type_distribution' => [
                'labels' => [],
                'data' => []
            ],
            'priority_distribution' => [
                'labels' => [],
                'data' => []
            ]
        ];

        if (!file_exists($resultsFile)) {
            $this->addFlash('warning', 'Le fichier d\'analyse n\'est pas encore disponible.');
            return $this->render('evisugtime/index.html.twig', ['results' => $defaultResults]);
        }

        $results = json_decode(file_get_contents($resultsFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addFlash('error', 'Erreur lors de la lecture du fichier d\'analyse.');
            return $this->render('evisugtime/index.html.twig', ['results' => $defaultResults]);
        }

        // Ensure all required keys exist
        $results = array_merge($defaultResults, $results);

        return $this->render('evisugtime/index.html.twig', [
            'results' => $results,
        ]);
    }

    #[Route('/admin/reclamations/evisugtime/export-pdf', name: 'evisugtime_export_pdf')]
    public function exportPdf(): Response
    {
        $resultsFile = $this->getParameter('kernel.project_dir') . '/var/evisugtime_results.json';
        
        if (!file_exists($resultsFile)) {
            throw $this->createNotFoundException('Le fichier d\'analyse n\'est pas disponible.');
        }

        $results = json_decode(file_get_contents($resultsFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw $this->createNotFoundException('Erreur lors de la lecture du fichier d\'analyse.');
        }

        $html = $this->renderView('evisugtime/pdf.html.twig', ['results' => $results]);
        
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $output = $dompdf->output();
        $pdfPath = $this->getParameter('kernel.project_dir') . '/var/evisugtime_report.pdf';
        file_put_contents($pdfPath, $output);

        return new BinaryFileResponse($pdfPath);
    }
}