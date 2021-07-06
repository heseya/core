<?php

namespace App\Http\Controllers\External;

use App\Exceptions\Error;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Swagger\FurgonetkaControllerSwagger;
use App\Models\Order;
use App\Models\OrderLog;
use App\Models\PackageTemplate;
use App\Models\Status;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use SoapClient;

class FurgonetkaController extends Controller implements FurgonetkaControllerSwagger
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

        if ($order) {
            $status = new Status();
            $order->update([
                'delivery_status' => $status->furgonetka_status[$request->tracking['state']],
            ]);

            $order->logs()->save(new OrderLog([
                'content' => $request->tracking['description'],
                'user' => 'Furgonetka',
                'created_at' => $request->tracking['datetime'],
            ]));
        }

        // Brak błędów bo furgonetka musi dostać status ok jak hash się zgadza
        return Response::json([
            'status' => 'OK',
        ]);
    }

    public function createPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer'],
            'package_template_id' => ['required', 'integer'],
        ]);

        $order = Order::findOrFail($validated['order_id']);
        $packageTemplate = PackageTemplate::findOrFail($validated['package_template_id']);

        $validator = Validator::make($order->deliveryAddress->toArray(), [
            'country' => ['required', 'in:PL'],
            'phone' => ['required', 'phone:PL'],
        ]);

        if ($validator->fails()) {
            return Error::abort(
                'Order address not in Poland/invalid phone number' .
                    $order->deliveryAddress->phoneSimple,
                502,
            );
        }

        try {
            $client = new SoapClient(config('furgonetka.api_url'), [
                'trace' => true,
                'cache_wsdl' => false,
            ]);
        } catch (Exception $error) {
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
                'partner_reference_number' => $order->code . '_' . date('d-m-Y_H-i-s'),
                'service_id' => $service_id,
                'type' => 'package',
                'regulations_accept' => $regulations_accept,
                'request_collection' => false,
                // Nie ma danych wysyłającego jeszcze na sklepie
                'sender' => [
                    'name' => 'Dawid Sandecki',
                    'email' => 'trafikadawidsandecki@gmail.com',
                    'street' => 'Gdańska 57',
                    'postcode' => '85-006',
                    'city' => 'Bydgoszcz',
                    'phone' => '+48 668 861 592',
                    'company' => 'Trafika',
                ],
                'receiver' => [
                    'email' => $order->email,
                    'name' => $order->deliveryAddress->name,
                    'street' => $order->deliveryAddress->address,
                    'postcode' => $order->deliveryAddress->zip,
                    'city' => $order->deliveryAddress->city,
                    'phone' => $order->deliveryAddress->phoneSimple,
                ],
                'label' => [
                    'file_format' => 'pdf',
                    'page_format' => 'a4',
                ],
                'parcels' => [
                    [
                        'width' => $packageTemplate->width,
                        'height' => $packageTemplate->height,
                        'depth' => $packageTemplate->depth,
                        'weight' => $packageTemplate->weight,
                        'value' => $order->summary,
                        'description' => 'Zamówienie ' . $order->code,
                    ],
                ],
            ],
        ];

        $validate = $client->validatePackage($packageData)->validatePackageResult;

        if ($validate->errors) {
            return Error::abort(
                $validate->errors->item,
                502,
            );
        }

        $package = $client->createPackage($packageData)->createPackageResult;

        if ($package->errors) {
            return Error::abort(
                $package->errors->item,
                502,
            );
        }

        $order->update([
            'shipping_number' => $package->parcels->item->package_no,
        ]);

        return response()->json([
            'shipping_number' => $package->parcels->item->package_no,
        ], 201);
    }

    private function getApiKey($refresh = false) : string
    {
        if (Storage::missing('furgonetka.key') || $refresh) {
            $response = Http::withBasicAuth(
                config('furgonetka.client_id'),
                config('furgonetka.client_secret'),
            )->asForm()->post(config('furgonetka.auth_url') . '/oauth/token', [
                'grant_type' => 'password',
                'scope' => 'api',
                'username' => config('furgonetka.login'),
                'password' => config('furgonetka.password'),
            ]);

            Storage::put('furgonetka.key', $response->json()['access_token'], 'private');
        }

        return Storage::get('furgonetka.key');
    }
}
