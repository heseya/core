<?php

namespace App\Http\Controllers\External;

use App\Exceptions\Error;
use App\Exceptions\PackageAuthException;
use App\Exceptions\PackageException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PackageTemplate;
use App\Services\Contracts\SettingsServiceContract;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use SoapClient;

class FurgonetkaController extends Controller
{
    public function __construct(
        private SettingsServiceContract $settingsService
    ) {
    }

    public function createPackage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'uuid'],
            'package_template_id' => ['required', 'uuid'],
            'provider' => ['required', 'string'],
        ]);

        $order = Order::where('id', $validated['order_id'])->firstOrFail();
        $packageTemplate = PackageTemplate::where('id', $validated['package_template_id'])->firstOrFail();
        $service_type = $validated['provider'];

        $validator = Validator::make($order->deliveryAddress?->toArray(), [
            'country' => ['required', 'in:PL'],
            'phone' => ['required', 'phone:PL'],
        ]);

        if ($validator->fails()) {
            return Error::abort(
                'Order address not in Poland/invalid phone number',
                502,
            );
        }

        try {
            $client = new SoapClient(Config::get('furgonetka.api_url'), [
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
                'sender' => [
                    'name' => $this->settingsService->getSetting('sender_name')->value,
                    'email' => $this->settingsService->getSetting('sender_email')->value,
                    'street' => $this->settingsService->getSetting('sender_street')->value,
                    'postcode' => $this->settingsService->getSetting('sender_postcode')->value,
                    'city' => $this->settingsService->getSetting('sender_city')->value,
                    'phone' => $this->settingsService->getSetting('sender_phone')->value,
                    'company' => $this->settingsService->getSetting('sender_company')->value,
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
            if (!is_array($validate->errors->item) && $validate->errors->item->message === 'Error authorization') {
                throw new PackageAuthException('Error authorization with API');
            }
            throw new PackageException('Invalid package data', 502, $validate->errors->item);
        }

        $package = $client->createPackage($packageData)->createPackageResult;

        if ($package->errors) {
            throw new PackageException('The package cannot be shipped.', 502, $package->errors->item);
        }

        $order->update([
            'shipping_number' => $package->parcels->item->package_no,
        ]);

        return Response::json([
            'shipping_number' => $package->parcels->item->package_no,
        ], 201);
    }

    private function getApiKey(bool $refresh = false): ?string
    {
        if (Storage::missing('furgonetka.key') || $refresh) {
            $response = Http::withBasicAuth(
                Config::get('furgonetka.client_id'),
                Config::get('furgonetka.client_secret'),
            )->asForm()->post(Config::get('furgonetka.auth_url') . '/oauth/token', [
                'grant_type' => 'password',
                'scope' => 'api',
                'username' => Config::get('furgonetka.login'),
                'password' => Config::get('furgonetka.password'),
            ]);

            Storage::put('furgonetka.key', $response->json()['access_token'], 'private');
        }

        return Storage::get('furgonetka.key');
    }
}
