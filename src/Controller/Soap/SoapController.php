<?php

declare(strict_types=1);

namespace App\Controller\Soap;

use App\Service\SoapOrderService;
use Laminas\Soap\AutoDiscover;
use Laminas\Soap\Server;
use Laminas\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SoapController extends AbstractController
{
    public function __construct(
        private readonly SoapOrderService $soapOrderService,
    ) {
    }

    #[Route('/soap/orders', name: 'soap_orders', methods: ['GET', 'POST'])]
    public function orders(Request $request): Response
    {
        $uri = $request->getSchemeAndHttpHost() . '/soap/orders';

        if ($request->query->has('wsdl')) {
            return $this->generateWsdl($uri);
        }

        return $this->handleSoapRequest($request);
    }

    private function generateWsdl(string $uri): Response
    {
        $autoDiscover = new AutoDiscover(new ArrayOfTypeComplex());
        $autoDiscover->setUri($uri);
        $autoDiscover->setServiceName('OrderService');
        $autoDiscover->setClass(SoapOrderService::class);

        $wsdl = $autoDiscover->generate();

        $response = new Response($wsdl->toXml());
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');

        return $response;
    }

    private function handleSoapRequest(Request $request): Response
    {
        $uri = $request->getSchemeAndHttpHost() . '/soap/orders';
        $internalWsdlUrl = 'http://nginx/soap/orders?wsdl';

        $opts = [
            'ssl' => [
                'ciphers' => 'RC4-SHA',
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];

        $server = new Server($internalWsdlUrl, [
            'encoding' => 'UTF-8',
            'verifypeer' => false,
            'verifyhost' => false,
            'trace' => 1,
            'exceptions' => 1,
            'connection_timeout' => 180,
            'soap_version' => SOAP_1_2,
            'uri' => $uri,
            'stream_context' => stream_context_create($opts)
        ]);

        $wrappedService = new class($this->soapOrderService) {
            public function __construct(private readonly SoapOrderService $service) {}

            public function createOrder(
                string $factory,
                string $collection,
                string $article,
                int $quantity,
                string $customerName,
                string $customerEmail
            ): int {
                try {
                    return $this->service->createOrder($factory, $collection, $article, $quantity, $customerName, $customerEmail);
                } catch (\Exception $e) {
                    throw new \SoapFault('Server', 'Error creating order: ' . $e->getMessage());
                }
            }

            public function getOrder(int $orderId): array
            {
                try {
                    return $this->service->getOrder($orderId);
                } catch (\Exception $e) {
                    throw new \SoapFault('Server', 'Error getting order: ' . $e->getMessage());
                }
            }
        };

        $server->setObject($wrappedService);

        ob_start();
        try {
            $server->handle($request->getContent());
        } catch (\Exception $e) {
            ob_end_clean();
            $server->fault('Server', 'Internal error: ' . $e->getMessage());
        }
        $response = ob_get_clean();

        return new Response($response, 200, [
            'Content-Type' => 'application/soap+xml; charset=utf-8',
        ]);
    }
}
