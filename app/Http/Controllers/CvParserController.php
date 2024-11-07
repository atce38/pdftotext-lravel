<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\PdfToText\Pdf;

class CvParserController extends Controller
{
    public function extractTextFromPdf(Request $request)
    {
        // Validate that a file was uploaded and is a PDF
        $request->validate([
            'cv' => 'required|mimes:pdf|max:2048',
        ]);

        // Store the uploaded file in the 'temp' directory
        $path = $request->file('cv')->store('temp');

        // Get the full path to the stored PDF file
        $fullPath = storage_path('app/' . $path);

        try {
            // Manually set the path to the pdftotext binary (Poppler)
            $pdfText = (new Pdf('C:/poppler/poppler-24.08.0/Library/bin/pdftotext.exe'))
                        ->setPdf($fullPath)   // Set the full path of the PDF
                        ->text();             // Extract the text from the PDF

            // Extract Name (Assuming it's the first line of the PDF)
            $lines = explode("\n", $pdfText);
            $name = isset($lines[0]) ? trim($lines[0]) : '';

            // Extract Email (e.g., using a regular expression)
            $email = '';
            if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $pdfText, $matches)) {
                $email = $matches[0];
            }


        // Extract Phone Number
            $phone = '';
            if (preg_match('/\+?\d{1,3}[\s.-]?\(?\d{1,4}\)?[\s.-]?\d{1,4}[\s.-]?\d{1,4}/', $pdfText, $matches)) {

                $phone = $matches[0];

            }

            // Extract Skills (Combine lines if they seem to be part of the same description)
$skills = [];
if (preg_match('/SKILLS\r?\n(.*?)(EXPERIENCE|EDUCATION|$)/is', $pdfText, $matches)) {
    // Join lines that seem to belong together and clean up extra spaces
    $skillsText = preg_replace('/\r?\n/', ' ', $matches[1]); // Join lines
    $skills = array_map('trim', explode('.,', $skillsText)); // Split by commas to form individual skills
}

// Extract Experience (Combine lines where necessary)
$experience = [];
if (preg_match('/EXPERIENCE\r?\n(.*?)(EDUCATION|$)/is', $pdfText, $matches)) {
    // Join lines that seem to belong together and clean up extra spaces
    $experienceText = preg_replace('/\r?\n/', ' ', $matches[1]); // Join lines
    $experience = array_map('trim', explode('.,', $experienceText)); // Split by commas to form individual experience points
}

            // Return the extracted data as a JSON response
            return response()->json([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'skills' => $skills,
                'experience' => $experience,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not extract text from PDF: ' . $e->getMessage()], 500);
        } finally {
            // Delete the temporary file after processing
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
