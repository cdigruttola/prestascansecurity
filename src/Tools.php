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

namespace PrestaScan;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tools
{
    public static function saveReport($reportFile, $results = null, $filters = null, $error = false)
    {
        $report['date_report'] = time();
        $report['error'] = $error;
        $report['report'] = [];
        $report['report']['filters'] = empty($filters) ? null : $filters;
        $report['report']['results'] = empty($results) ? null : $results;
        file_put_contents($reportFile, serialize($report));
    }

    public static function getModuleList()
    {
        // Do not use SQL request on ps_module as it will not list modules that are not installed

        // Will return a list of modules on disk, but ... not only.
        // Despite the fonction name, PrestaShop also returns a list of modules retrived from the webservice for marketing purpose
        $prestaShopModules = \Module::getModulesOnDisk(true);
        // The function has known issues to identify the state of a module (enable or disabled AND installed or not installed)
        // We do not trust this function and we will rely directly of the ps_module table to check the state.
        // We will also not filter by shop ID and will check if the module is at least enable in one shop.
        $sql = 'SELECT module.id_module, module_shop.id_module module_shop_id, module.name' .
            ' FROM `' . _DB_PREFIX_ . 'module` module' .
            ' LEFT JOIN `' . _DB_PREFIX_ . 'module_shop` module_shop ON module_shop.id_module = module.id_module';
        $listEnabledOrInstalledModules = \Db::getInstance()->executeS($sql);

        foreach ($prestaShopModules as $key => $aModule) {
            if (isset($aModule->not_on_disk) && (int) $aModule->not_on_disk === 1) {
                unset($prestaShopModules[$key]);
                continue;
            }
            $active = false;
            $installed = false;
            foreach ($listEnabledOrInstalledModules as $moduleArray) {
                if ((int) $moduleArray['id_module'] === (int) $aModule->id) {
                    $installed = true;
                    $active = $moduleArray['module_shop_id'] === NULL ? false : true;
                    break;
                }
            }
            $list[] = array(
                'name' => $aModule->name,
                'active' => $active,
                'version' => $aModule->version,
                'installed' => $installed,
            );
            $prestaShopModules[$key]->active = $active;
        }
        return array(
            'allModules' => $prestaShopModules,
            'modulesOnDisk' => $list,
        );
    }

    public static function getHashByName($hashName, $key)
    {
        return md5(_COOKIE_KEY_ . $hashName . $key);
    }

    public static function displayErrorAndDie($code, $message = null)
    {
        http_response_code($code);
        if ($message) {
            die($message);
        }
        exit();
    }

    public static function getShopUrl()
    {
        return \Context::getContext()->shop ->getBaseUrl(true, true);
    }

    public static function enforeHttpsIfAvailable($url)
    {
        // We check if the $url is in http
        if (strpos($url, 'https://') === 0) {
            // The url is already in https, we return it directly
            return $url;
        }

        // Check if HTTPS is available
        // Why ? Because this might be mistakenly in http depending of the shop configuration and PS version.
        // For exemple, before PS 1.6.1, https usage with Shop::getShopUrl() was missing checks for https
        // https://github.com/PrestaShop/PrestaShop-1.6/commit/d3fc2ca219df5db8efd021c8b39ade6270cb656c
        // Also, before PS 1.6.0.12, https usage with Tools::usingSecureMode() was missing checks for `HTTP_X_FORWARDED_PROTO`
        // https://github.com/PrestaShop/PrestaShop-1.6/commit/2fec9c0c99f4cd0c2c416bd13d29dc1b8bf3ceb1
        $https = \Tools::usingSecureMode();
        if (version_compare(_PS_VERSION_, '1.6.0.12', '<=') && !$https && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $https = Tools::strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https';
        }

        // If HTTPS is available, replace 'http://' with 'https://'
        if ($https) {
            $url = str_replace('http://', 'https://', $url);
        }

        // Return the (possibly updated) URL
        return $url;
    }

    public static function deleteReport($filename)
    {
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    public static function getPrestashopVersion()
    {
        return _PS_VERSION_;
    }

    public static function resetModuleConfigurationAndCache($uninstall = false)
    {
        \Configuration::deleteByName('PRESTASCAN_REFRESH_TOKEN');
        \Configuration::deleteByName('PRESTASCAN_ACCESS_TOKEN_EXPIRE');
        \Configuration::deleteByName('PRESTASCAN_ACCESS_TOKEN');
        \Configuration::deleteByName('PRESTASCAN_ACCESS_CLIENT_ID');
        \Configuration::deleteByName('PRESTASCAN_ACCESS_CLIENT_SECRET');
        \Configuration::deleteByName('PRESTASCAN_DEV_OAUTH_DOMAIN_URL');
        \Configuration::deleteByName('PRESTASCAN_DEV_OAUTH_REDIRECT_URL');
        \Configuration::deleteByName('PRESTASCAN_SCAN_PROGRESS');
        \Configuration::deleteByName('PRESTASCAN_UPDATE_VERSION_AVAILABLE');
        \Configuration::deleteByName('PRESTASCAN_HAS_UPDATE_VERSION');
        \Configuration::deleteByName('PRESTASCAN_LAST_VERSION_CHECK');
        \Configuration::deleteByName('PRESTASCAN_SCAN_MAX_RUN_TIME');
        \Configuration::deleteByName('PRESTASCAN_BANNER_RESPONSE');
        \Configuration::deleteByName('PRESTASCAN_BANNER_LAST_CHECK');
        \Configuration::deleteByName('PRESTASCAN_API_EMAIL');
        \Configuration::deleteByName('PRESTASCAN_SUBS_STATE');
        \Configuration::deleteByName('PRESTASCAN_SUBS_LAST_CHECK');

        if ($uninstall) {
            \Configuration::deleteByName('PRESTASCAN_SEC_HASH');
            \Configuration::deleteByName('PRESTASCAN_WEBCRON_TOKEN');
            \Configuration::deleteByName('PRESTASCAN_FIX_1_0_4');
        }

        self::deleteCacheFiles();
    }

    public static function printAjaxResponse($success, $error, $statusText = '', $data = false)
    {
        // In PS 8.X, `\Tools::jsonEncode` has been removed
        die(json_encode(
            array(
                'success' => $success,
                'error' => $error,
                'statusText' => $statusText,
                'data' => $data,
            )
        ));
    }

    public static function deleteCacheFiles()
    {
        $fullPath = self::getCachePath();
        array_map('unlink', glob("$fullPath*.cache"));
        return true;
    }

    public static function getCachePath()
    {
        return _PS_MODULE_DIR_ . 'prestascansecurity/cache/';
    }

    public static function getModuleRawCacheFile()
    {
        $cacheDirectory = self::getCachePath();
        $cacheHash = \Configuration::get('PRESTASCAN_SEC_HASH');
        $tokenCache = self::getHashByName('cacheHash', $cacheHash);
        return $cacheDirectory . 'modules_raw_' . $tokenCache . '.cache';
    }

    public static function getFormattedModuleOnDiskList()
    {
        $listOfModules = self::getModuleList();
        // All modules retrieved by PrestaShop API (includes modules on disk and not on disk (not in /modules))
        $listOfallModules = $listOfModules['allModules'];
        // Only modules on list, but with missing data
        $modulesOnDisk = $listOfModules['modulesOnDisk'];

        $formattedList = [];
        foreach ($listOfallModules as $aModule) {
            foreach ($modulesOnDisk as $key => $aModuleOnDisk) {
                if ($aModule->name !== $aModuleOnDisk['name']) {
                    continue;
                }

                $formattedList[$aModuleOnDisk['name']] = array(
                    'name' => $aModuleOnDisk['name'],
                    'version' => $aModuleOnDisk['version'],
                    'displayName' => $aModule->displayName,
                    'description' => $aModule->description,
                    'author' => $aModule->author,
                    'active' => $aModuleOnDisk['active'] ? true : false,
                    'installed' => $aModuleOnDisk['installed'],
                );

                // Unfortunately the key is not defined with `\Module::getModulesOnDisk`
                // Does it depends of the version of PrestaShop ?
                // So we need to create an instance to retrive the key
                try {
                    $instance = \Module::getInstanceByName($aModuleOnDisk['name']);
                    if (isset($instance->module_key) && !empty($instance->module_key)) {
                        // We add the module key for the module that have one (for addons requests)
                        $formattedList[$aModuleOnDisk['name']]['module_key'] = $instance->module_key;
                    }
                } catch (\Exception $exp) {
                    // Do nothing
                }

                unset($modulesOnDisk[$key]);
            }
        }

        return $formattedList;
    }

    public static function isContainingPerformedScan($scans)
    {
        foreach ($scans as $scan) {
            if ($scan !== false) {
                return true;
            }
        }
        return false;
    }

    public static function isContainingOutdatedScan($scans, $month = 1)
    {
        foreach ($scans as $aScan) {
            if (!$aScan) {
                // Scan not performed
                continue;
            }
            if (self::isScanOutDated($aScan['summary']['date'], $month)) {
                return true;
            }
        }
        return false;
    }

    public static function isScanOutDated($date, $month = 1)
    {
        $outdated = false;
        if (!empty($date)) {
            $date_scan = strtotime($date);
            if ($date_scan <= strtotime('-' . (int) $month . ' month')) {
                $outdated = true;
            }
        }
        return $outdated;
    }

    public static function formatDateString($date)
    {
        $return = '';
        $languageCode = \Context::getContext()->language->iso_code;
        $mois = array(
            'fr' => array(
                'January' => 'Janvier',
                'February' => 'Février',
                'March' => 'Mars',
                'April' => 'Avril',
                'May' => 'Mai',
                'June' => 'Juin',
                'July' => 'Juillet',
                'August' => 'Août',
                'September' => 'Septembre',
                'October' => 'Octobre',
                'November' => 'Novembre',
                'December' => 'Décembre'
            ),
            'es' => array(
                'January' => 'Enero',
                'February' => 'Febrero',
                'March' => 'Marzo',
                'April' => 'Abril',
                'May' => 'Mayo',
                'June' => 'Junio',
                'July' => 'Julio',
                'August' => 'Agosto',
                'September' => 'Septiembre',
                'October' => 'Octubre',
                'November' => 'Noviembre',
                'December' => 'Diciembre'
            )
        );
        $at = array(
            'fr' => ' à ',
            'es' => ' a las ',
        );
        if (!empty($date)) {
            $return = date('j F Y ', strtotime($date));
            $return .= in_array($languageCode, array('fr','es')) ? $at[$languageCode] : ' at ';
            $return .= date('h\hi', strtotime($date));
            if (in_array($languageCode, array('fr','es'))) {
                $return = str_replace(array_keys($mois[$languageCode]), array_values($mois[$languageCode]), $return);
            }
        }
        return $return;
    }

    public static function getOldestScan($scans)
    {
        if (count($scans) === 1) {
            return $scans[0];
        }

        // Set the initial value of the oldest scan and date
        $oldestScan = null;
        $oldestDate = null;

        // Loop through the results array
        foreach ($scans as $aScan) {
            if (!$aScan) {
                // Scan not performed
                continue;
            }

            // Check if the oldest date is null or the aScan's date is older
            $date = $aScan['summary']['date'];
            if ($oldestDate === null || $date < $oldestDate) {
                // Update the oldest scan and date
                $oldestScan = $aScan;
                $oldestDate = $date;
            }
        }

        // Return the oldest scan (or the first one if none are found)
        return is_null($oldestScan) ? $scans[0] : $oldestScan;
    }

    public static function getScanWithHighestCriticity($scans)
    {
        $highestCriticityScan = null;
        $highestCriticityLevel = -1;

        $criticityLevels = array(
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
        );

        foreach ($scans as $scan) {
            if (!$scan) {
                continue;
            }
            if (!isset($scan['summary']['scan_result_criticity'])) {
                // Fallback. Should not happen
                $scan['summary']['scan_result_criticity'] = 'low';
            }
            $scanCriticity = $scan['summary']['scan_result_criticity'];

            if (isset($criticityLevels[$scanCriticity]) && $criticityLevels[$scanCriticity] > $highestCriticityLevel) {
                $highestCriticityScan = $scan;
                $highestCriticityLevel = $criticityLevels[$scanCriticity];
            }
        }

        return is_null($highestCriticityScan) ? $scans[0] : $highestCriticityScan;
    }

    public static function fixMissingUpgrade()
    {
        // In versions < 1.0.4, the upgrade system is not loading /upgrade/update-XX scripts
        // To avoid broken install, we will manually run this upgrade when the auto upgrade didn't run properly
        if (\Configuration::get('PRESTASCAN_FIX_1_0_4')) {
            return true;
        }

        // Add "suggest_cancel" in the enum
        $query = 'ALTER TABLE `' . _DB_PREFIX_ . 'prestascan_queue`
            CHANGE `state` `state` ENUM(\'progress\',\'cancel\',\'completed\',\'error\',\'toretrieve\',\'suggest_cancel\')
            CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL';

        if (!\Db::getInstance()->execute($query)) {
            return false;
        }

        \Configuration::updateGlobalValue('PRESTASCAN_FIX_1_0_4', true);

        return true;
    }

    public static function getModulePath()
    {
        $sep = DIRECTORY_SEPARATOR;
        return _PS_MODULE_DIR_ . 'prestascansecurity' . $sep;
    }

    public static function createLockFile($lockFileName)
    {
        $sep = DIRECTORY_SEPARATOR;
        $lockFilePath = self::getModulePath() . 'cache' . $sep . $lockFileName;
        $fp = fopen($lockFilePath, 'w+');
        return flock($fp, LOCK_EX | LOCK_NB);
    }

    public static function getTimeTooLongParameter()
    {
        // If no time is supplied, get the defaut scan run time from the database
        if ($time = \Configuration::get('PRESTASCAN_SCAN_MAX_RUN_TIME')) {
            return $time;
        }

        // If the configuration key does not exist, default to 5 mins
        $time = 5;
        \Configuration::updateGlobalValue('PRESTASCAN_SCAN_MAX_RUN_TIME', $time);
        return $time;
    }

    /**
    * Normalize versions for version_compare
    * Eg: 1.0 is different of 1.0.0 based on version_compare
    */
    public static function versionCompareExtended($ver1, $ver2, $operator = null)
    {
        $normalizedVer1 = self::normalizeVersion($ver1, max(substr_count($ver1, '.'), substr_count($ver2, '.')));
        $normalizedVer2 = self::normalizeVersion($ver2, max(substr_count($ver1, '.'), substr_count($ver2, '.')));

        if ($operator !== null) {
            return version_compare($normalizedVer1, $normalizedVer2, $operator);
        }

        return version_compare($normalizedVer1, $normalizedVer2);
    }

    private static function normalizeVersion($version, $maxDots)
    {
        $parts = explode('.', $version);
        while (count($parts) <= $maxDots) {
            $parts[] = '0';
        }
        return implode('.', $parts);
    }

    public static function getCustomConfigValue($configName)
    {
        $sep = DIRECTORY_SEPARATOR;
        $customConfigFilePath = self::getModulePath() . 'install' . $sep . 'custom_config.php';
        include($customConfigFilePath);

        if (isset($customConfig) && isset($customConfig[$configName])) {
            return $customConfig[$configName];
        }

        return false;
    }

    public static function updateDismissedEntitiesList($reportFile, $actionReport, $value, $type = '', $vulnerabilitiesCount = '')
    {
        $data = unserialize(file_get_contents($reportFile));
        if (strpos($reportFile, 'modules_vulnerabilities') !== false) {
            $matchingTypeReport = ['modules_vulnerables' => 'vulnerable', 'modules_to_update' => 'module_to_update'];
            $type = $matchingTypeReport[$type];
            foreach ($data['report']['results'][$type] as $k => $dismiss) {
                if ($dismiss['name'] == $value) {
                    $data['report']['results'][$type][$k]['is_dismissed'] = 0;
                    if ($actionReport == 'dismissed') {
                        $data['report']['results'][$type][$k]['is_dismissed'] = 1;
                    }
                }
            }
        } elseif(strpos($reportFile, 'modules_unused') !== false) {
            $matchingTypeReport = [ 'modules_uninstalled' => 'not_installed', 'modules_disabled' => 'disabled'];
            $type = $matchingTypeReport[$type];
            foreach($data['report']['results']['result'][$type] as $k => $module) {
                if($module["name"] == $value) {
                    $data['report']['results']['result'][$type][$k]['is_dismissed'] = 0;
                    if ($actionReport == 'dismissed') {
                        $data['report']['results']['result'][$type][$k]['is_dismissed'] = 1;
                    }
                }
            }
        } elseif(strpos($reportFile, 'core_vulnerabilities') !== false) {
            foreach($data['report']['results']['result'] as $k => $vulnerable) {
                if($vulnerable["cve"]["value"] == substr($value, strrpos($value, '/CVE-') + 5 )) {
                    $data['report']['results']['result'][$k]['is_dismissed'] = 0;
                    if ($actionReport == 'dismissed') {
                        $data['report']['results']['result'][$k]['is_dismissed'] = 1;
                    }
                }
            }
        } elseif (strpos($reportFile, 'directories_listing') !== false) {
            foreach($data['report']['results']['result'] as $k => $vulnerable) {
                if($vulnerable[0]["directory"] == $value) {
                    $data['report']['results']['result'][$k][0]['is_dismissed'] = 0;
                    if ($actionReport == 'dismissed') {
                        $data['report']['results']['result'][$k][0]['is_dismissed'] = 1;
                    }
                }
            }
        }

        file_put_contents($reportFile, serialize($data));
        return true;
    }

    public static function detectBrowserLanguage()
    {
        $preferredLanguages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        $languages = explode(',', $preferredLanguages);
        if (!empty($languages)) {
            $primaryLanguage = explode(';', $languages[0]);
            $languageCode = explode('-', $primaryLanguage[0]);
            return strtolower(trim($languageCode[0]));
        }
        $defaultLanguage = new Language(Configuration::get('PS_LANG_DEFAULT'));
        return $defaultLanguage->iso_code;
    }
}
