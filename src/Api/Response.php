<?php
/**
 * Copyright 2023 Profileo Group <contact@profileo.com> (https://www.profileo.com/fr/)
 *
 * For questions or comments about this software, contact Maxime Morel-Bailly <security@prestascan.com>
 * List of required attribution notices and acknowledgements for third-party software can be found in the NOTICE file.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @author    Profileo Group - Complete list of authors and contributors to this software can be found in the AUTHORS file.
 * @copyright Since 2023 Profileo Group <contact@profileo.com> (https://www.profileo.com/fr/)
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
namespace PrestaScan\Api;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Response
{
    public static function checkResponse($response)
    {
        if (is_null($response)) {
            throw new \PrestaScan\Exception\UnauthenticatedException('Not logged in');
        }

        if (!is_array($response) || !isset($response['success'])) {
            throw new \Exception('Invalid API Response');
        }

        if ($response['success'] !== true) {
            if (isset($response['error'])
                && isset($response['error']['code'])
                && ((int) $response['error']['code'] === 200 || (int) $response['error']['code'] === 204) ) {
                // Specific case of 'errors' that should not trigger an exception
                // Example for a report that is not yet ready
                /*
                * array(2) {
                *  ['success']=>
                *  bool(false)
                *  ['error']=>
                *  array(2) {
                *    ['code']=>
                *    int(200)
                *    ['message']=>
                *    string(23) 'Report is not ready yet'
                *  }
                *}
                */
                return true;
            }

            // Error
            $message = isset($response['error']) && isset($response['error']['message']) ?
                $response['error']['message'] :
                'Unknown API error';
            throw new \Exception($message);
        }
    }

    public static function getBody($response)
    {
        return isset($response['payload']) ? $response['payload'] : $response;
    }
}
