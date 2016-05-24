<?php

namespace IntoTheSource\Powerlogger;

use Config;

class Handle {

    public static function init(\Exception $e)
    {
        $request = request();

        if (env('APP_ENV', 'production') === 'production')
        {

            if($e instanceof NotFoundHttpException)
            {
                $stauts = $e->getStatusCode();
            } else {
                $status = 404;
            }

            $message = [
                'text' => 'Logmelding van ' . config('powerlogger.customer') . ' [ '. config('powerlogger.domain') . ' ]',
                'name' => 'IntoTheLogBot',
                'fields' => [
                    [
                        'title' => 'Foutcode',
                        'value' => $status,
                        'short' => true
                    ],
                    [
                        'title' => 'URL',
                        'value' => $request->url(),
                        'short' => true
                    ],
                    [
                        'title' => 'Method',
                        'value' => $request->method(),
                        'short' => true
                    ]
                ]
            ];

            if ( ! empty($e->getMessage()))
            {
                $message['fields'][] = [
                    'title' => 'Error bericht',
                    'value' => $e->getMessage(),
                    'short' => true
                ];
            }

            if ( ! empty($request->server('HTTP_REFERER')))
            {
                 $message['fields'][] = [
                    'title' => 'Referrer',
                    'value' => $request->server('HTTP_REFERER'),
                    'short' => true
                ];
            }

            if ($request->route() !== null)
            {
                $message['fields'][] = [
                    'title' => 'Route',
                    'value' => $request->route()->getName(),
                    'short' => true
                ];
            }

            if (in_array($request->method(), ['POST', 'PUT', 'PATCH']))
            {

                $inputs = '';
                foreach($request->except(['old_password', 'password_confirmation', '_token', '_method']) as $name => $value) {
                    $inputs .= "[" . $name . "] => " . $value . "\n";
                }

                $message['fields'][] = [
                    'title' => 'Input',
                    'value' => $inputs,
                    'short' => true,
                ];
            }

            switch ($status):
                default:
                case 404:
                    $message['color'] = 'warning';
                break;
                case 403:
                case 500:
                    $message['color'] = 'danger';
                break;
            endswitch;

            $attachment = json_encode($message);
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, 'https://hooks.slack.com/services/' . config('powerlogger.slack'));
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $attachment);
            curl_exec($curl);
        }
    }
}
