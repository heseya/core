<?php

namespace App\Http\Controllers\External;

use App\Models\Order;
use App\Models\Status;
use App\Models\OrderLog;
use App\Exceptions\Error;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use Exception;

class FurgonetkaController extends Controller
{
    /**
     * Odbieranie statusów przesyłek z Furgonetka.pl w formacie JSON
     *
     * https://furgonetka.pl/files/dokumentacja_webhook.pdf
     */
    public function webhook(Request $request): JsonResponse
    {
        $control = md5(
            $request->package_id .
            $request->package_no .
            $request->partner_order_id .
            $request->tracking['state'] .
            $request->tracking['description'] .
            $request->tracking['datetime'] .
            $request->tracking['branch'] .
            config('furgonetka.webhook_salt')
        );

        if ($control !== $request->control) {
            return response()->json([
                'status' => 'ERROR',
                'message' => 'control value not match',
            ], 400);
        }

        $order = Order::where('delivery_tracking', $request->package_no) // numer śledzenia
            ->orWhere('code', $request->partner_order_id) // kod zamówienia
            ->first();

        if (!empty($order)) {

            $status = new Status;
            $order->update([
                'delivery_status' => $status->furgonetka_status[$request->tracking['state']],
            ]);

            $order->logs()->save(new OrderLog([
                'content' => $request->tracking['description'],
                'user' => 'Furgonetka',
                'created_at' => $request->tracking['datetime'],
            ]));
        }

        // Brak błędów bo furgonetka musi dostać status ok jak hash się zgadza
        return response()->json([
            'status' => 'OK',
        ]);
    }

    public function createPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orders' => 'required|array|min:1',
            'orders.*' => 'integer|exists:orders,id',
        ]);

        try {
            $client = new SoapClient('http://biznes-test.furgonetka.pl/api/soap/v2?wsdl', [
                'trace' => true,
                'cache_wsdl' => false,
            ]);
        } catch (Exception $e) {
            return Error::abort(
                'Could not connect to API',
                502,
            );
        }

        $auth = [
            'access_token' => $this->getApiKey(),
        ];
        
        $services = $client->getServices([
            'data' => [
                'auth' => $auth,
            ],
        ])->getServicesResult;

        if (
            $services->errors && 
            $services->errors->item->message === 'Error authorization'
        ) {
            $auth = [
                'access_token' => $this->getApiKey(true),
            ];

            $services = $client->getServices([
                'data' => [
                    'auth' => $auth,
                ],
            ])->getServicesResult;
        }
        
        if (!$services->services) {
            return Error::abort(
                'Could not get carriers list',
                502,
            );
        }

        $services = $services->services->item;

        foreach ($validated['orders'] as $orderId) {
            $order = Order::findOrFail($orderId);

            $service_type = Str::slug($order->shippingMethod->name);
        
            foreach ($services as $service) {
                if ($service->type === $service_type) {
                    $service_id = $service->id;
                    break;
                }
            }
        
            if (!isset($service_id)) {
                return Error::abort(
                    'Could not get carriers matching shipping method',
                    502,
                );
            }
            
            $regulations = $client->getRegulations([
                'data' => [
                    'auth' => $auth,
                ],
            ])->getRegulationsResult;
        
            if (!$regulations->services) {
                return Error::abort(
                    'Could not get regulations list',
                    502,
                );
            }
            
            $regulations = $regulations->services->item;
            
            foreach ($regulations as $service) {
                if ($service->service_type === $service_type) {
                    $regulations_accept = $service->version;
                    break;
                }
            }
        
            if (!isset($regulations_accept)) {
                return Error::abort(
                    'Could not get regulation for specified carrier',
                    502,
                );
            }
        
            $packageData = [
                'data' => [
                    'auth' => $auth,
                    'partner_reference_number' => $order->code,
                    'service_id' => $service_id,
                    'type' => 'package',
                    'regulations_accept' => $regulations_accept,
                    'request_collection' => false,
                    // Nie ma danych wysyłającego jeszcze na sklepie
                    'sender' => [
                        'name' => 'Jan Kowalski',
                        'email' => 'jan@kowalski.pl',
                        'street' => 'Polna 1/2',
                        'postcode' => '82-300',
                        'city' => 'Elbląg',
                        'phone' => '+48 500 999 888',
                    ],
                    // Seedowane zamówienia nie mają adresu więc lipa
                    'receiver' => [
                        'name' => $order->deliveryAddress->name,
                        'street' => $order->deliveryAddress->address,
                        'postcode' => $order->deliveryAddress->zip,
                        'city' => $order->deliveryAddress->city,
                        'phone' => $order->deliveryAddress->phone,
                    ],
                    'label' => [
                        'file_format' => 'pdf',
                        'page_format' => 'a4',
                    ],
                    'parcels' => [
                        // Zamówienie wymaga rozmiarów i wagi RIP
                        [
                            'width' => 10,
                            'height' => 20,
                            'depth' => 30,
                            'value' => 555,
                            'weight' => 11,
                            'value' => $order->summary,
                            'description' => 'Opis zamówienia',
                        ],
                    ],
                ],
            ];
        
            $validate = $client->validatePackage($packageData)->validatePackageResult;
        
            if (!empty($validate->errors)) {
                print_r($validate);
                return Error::abort(
                    'Invalid package',
                    502,
                );
            }
        
            $package = $client->createPackage($packageData)->createPackageResult;
        
            if (!empty($package->errors)) {
                return Error::abort(
                    'Couldnt create package',
                    502,
                );
            }
        }

        return response()->json([
            'status' => 'OK',
        ], 201);
    }

    private function getApiKey($refresh = false) : string {
        if (Storage::missing('furgonetka.key') || $refresh) {
            $response = Http::withBasicAuth(
                'heseyashop-bae48caf4ee3b764010e7dea5de79e95',
                '1760a14fbc361ad93c823a5a2f46911a3cd19939ded5abeced1990d1e306f7f5',
            )->asForm()->post('https://konto-test.furgonetka.pl/oauth/token', [
                'grant_type' => 'password',
                'scope' => 'api',
                'username' => 'bartek@heseya.com',
                'password' => '3yXtFWHKCKJjXz6geJuTGpvAscGBnGgR',
            ]);

            Storage::put('furgonetka.key', $response->json()['access_token'], 'private');
        }

        return Storage::get('furgonetka.key');
    }
}
