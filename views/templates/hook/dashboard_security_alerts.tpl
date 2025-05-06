{*
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
 *}
{assign var='criticity' value=$alert_modules_vulnerability[0].criticity}
<p class="title-alert">
    <strong>{l s='SECURITY ALERT - POTENTIAL RISK' mod='prestascansecurity'}</strong>
</p>
{if $alert_modules_vulnerability[0].is_core}
    <p>{l s='New core vulnerability detected' mod='prestascansecurity'} <strong></strong></p>
{else}
    <p>{l s='A new vulnerability has been added to the module\'s database. Run a manual scan to check if you are affected.' mod='prestascansecurity'} <strong>{if isset($alert_modules_vulnerability[0].name)} {$alert_modules_vulnerability[0].name} {/if}</strong></p>
{/if}
{if (!$alert_modules_vulnerability[0].is_core && isset($alert_modules_vulnerability[0].module_name) && $alert_modules_vulnerability[0].module_name != 'alert_module_no_detail')
    || ($alert_modules_vulnerability[0].is_core && isset($alert_modules_vulnerability[0].module_name) && $alert_modules_vulnerability[0].module_name != 'alert_core_no_detail')
}
<ul>
    <li>{l s='Criticity' mod='prestascansecurity'} : {$criticity|ucfirst}</li>
    <li>{l s='Type' mod='prestascansecurity'} : {$alert_modules_vulnerability[0].vulnerability_type}</li>
    <li>{l s='Description' mod='prestascansecurity'} : {$alert_modules_vulnerability[0].description}</li>
</ul>
{/if}
<p class='button-center'><a class="btn-generate-report btn btn-default btn-action-module-dash" data-action="generateModulesReport" href="{$module_link}"><span>{l s='Run a scan' mod='prestascansecurity'}</span></a></p>
