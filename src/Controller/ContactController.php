<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ContactController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    #[Route('/brevo-contact', name: 'brevo_contact', methods: ['POST'])]
    public function brevoContact(Request $request): JsonResponse
    {
        // 1. Parse the JSON data from the frontend form
        $data = json_decode($request->getContent(), true);

        // 2. Validate that all required fields are present
        if (!is_array($data) || empty($data['name']) || empty($data['email']) || empty($data['message'])) {
            return $this->json(['error' => 'Please fill in all required fields.'], 422);
        }

        // 3. Fetch the API Key from your .env file
        $apiKey = $_ENV['BREVO_API_KEY'] ?? null;
        if (!$apiKey) {
            return $this->json(['error' => 'Server Configuration Error: API Key missing.'], 500);
        }

        /**
         * 4. Prepare the Email Payload
         * SENDER: Must be an email verified in your Brevo Dashboard (Senders & IP).
         * TO: Sends the email to the address typed into your website form.
         * BCC: Sends a silent copy to your admin email so you can see the message.
         */
        $emailData = [
            'sender' => [
                'name' => 'Chelswear Team', 
                'email' => 'richelpaculanang06@gmail.com' // Verified Brevo Sender
            ],
            'to' => [
                [
                    'email' => $data['email'], // Sends to the visitor's Gmail
                    'name' => $data['name']
                ]
            ],
            'bcc' => [
                [
                    'email' => 'paculanangrichel24@gmail.com', // Your admin email for notification
                    'name' => 'Chelswear Admin'
                ]
            ],
            'replyTo' => [
                'email' => 'richelpaculanang06@gmail.com', 
                'name' => 'Chelswear Support'
            ],
            'subject' => 'Thank you for contacting Chelswear, ' . $data['name'],
            'htmlContent' => "
                <html>
                <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <div style='max-width: 600px; margin: auto; border: 1px solid #eee; padding: 20px;'>
                        <h2 style='color: #e91e63;'>Hello " . htmlspecialchars($data['name']) . ",</h2>
                        <p>Thank you for reaching out! We have received your inquiry regarding <strong>" . htmlspecialchars($data['website'] ?? 'your project') . "</strong>.</p>
                        <p>Our team will review your message and get back to you as soon as possible.</p>
                        <hr style='border: 0; border-top: 1px solid #eee;'>
                        <p><strong>Your Message:</strong></p>
                        <p style='background: #f9f9f9; padding: 15px; border-radius: 5px; font-style: italic;'>
                            " . nl2br(htmlspecialchars($data['message'])) . "
                        </p>
                        <br>
                        <p>Best regards,<br><strong>The Chelswear Team</strong></p>
                    </div>
                </body>
                </html>
            "
        ];

        try {
            // 5. Execute the POST request to Brevo's Transactional Email API
            $response = $this->httpClient->request('POST', 'https://api.brevo.com/v3/smtp/email', [
                'headers' => [
                    'api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => $emailData,
                'timeout' => 10,
            ]);

            $status = $response->getStatusCode();
            $result = $response->toArray(false); 

            if ($status >= 200 && $status < 300) {
                return $this->json(['success' => true]);
            }

            // Return specific Brevo error if the API rejects it
            return $this->json([
                'error' => 'Brevo rejected the request: ' . ($result['message'] ?? 'Unknown Error'),
                'details' => $result
            ], $status);

        } catch (\Throwable $e) {
            // Catch connection errors (DNS, Timeout, etc.)
            return $this->json(['error' => 'Connection to Brevo failed: ' . $e->getMessage()], 500);
        }
    }
}