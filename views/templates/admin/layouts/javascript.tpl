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

<script type="text/javascript">
    function decodeHTMLEntities(text) {
        var textarea = document.createElement('textarea');
        textarea.innerHTML = text;
        return textarea.value;
    }
    {if isset($mediaJsDef)}
        {foreach from=$mediaJsDef key=key item=value} 
            var {$key} = "{$value|escape:"html":'UTF-8'}";
        {/foreach}
    {/if}
    {if isset($prestascansecurity_isLoggedIn) && $prestascansecurity_isLoggedIn}
        var prestascansecurity_isLoggedIn = {$prestascansecurity_isLoggedIn|var_export:true};
    {else}
        var prestascansecurity_isLoggedIn = false;
        var prestascansecurity_tokenfc = "{$prestascansecurity_tokenfc|escape:"html":'UTF-8'}";
        var prestascansecurity_shopurl = "{$prestascansecurity_shopurl|escape:"html":'UTF-8'}";
        var prestascansecurity_e_firstname = "{$prestascansecurity_e_firstname|escape:"html":'UTF-8'}";
        var prestascansecurity_e_lastname = "{$prestascansecurity_e_lastname|escape:"html":'UTF-8'}";
        var prestascansecurity_e_email = "{$prestascansecurity_e_email|escape:"html":'UTF-8'}";
        var webcron_token = "{$webcron_token|escape:"html":'UTF-8'}";
        var ps_shop_urls = "{$ps_shop_urls|escape:"html":'UTF-8'}";
        var prestascansecurity_devdomainurl = "{$prestascansecurity_devdomainurl|escape:"html":'UTF-8'}";
        var prestascansecurity_devredirecturl = "{$prestascansecurity_devredirecturl|escape:"html":'UTF-8'}";
        var psscan_urlconfigbo = "{$psscan_urlconfigbo|escape:"html":'UTF-8'}";
    {/if}
    $(document).ready(function(){
        var fullHeightLi = document.querySelector('.full-height');
        fullHeightLi.addEventListener('mouseenter', function() {
            this.style.borderRadius  = '10px';
        });
        fullHeightLi.addEventListener('mouseleave', function() {
            this.style.borderRadius  = '20px';
        });
    });    
</script>
