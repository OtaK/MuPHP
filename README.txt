Framework MVC PHP by OtaK_ // Mathieu Amiot <m.amiot@otak-arts.com>

    /*
     * Copyright 2012 Mathieu "OtaK_" Amiot <m.amiot@otak-arts.com> http://mathieu-amiot.fr/
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     *      http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     *
     */

http://otak-arts.com

Installation & configuration :
    Extract to the desired directory
    Go to app/cfg/define.php and setup some constants for the DB / Site
    Setup the .htaccess at the root and put the correct RewriteBase

How to add a page :
    Go to app/cfg/modules.php, add a new array in the array in the getModules() function with the following elements :
        Page name in the url as the key => array(
            'fileName' is the filename used in both controller and view
            'pageTitle' is the dynamically displayed page title
            'registeredOnly' = true if you want only logged in users to see this page
            'adminOnly' = true if you want only logged in administrators to see this page
        )
        example :
        'accueil' => array(
            'fileName'          => 'home',
            'pageTitle'         => 'Accueil',
            'registeredOnly'    => false,
            'adminOnly'         => false
        )
        will be available publicly at BASE_URL/accueil and will have SITE_TITLE :: Accueil as a title

    Go to app/_ctl folder and add a new file with the fileName previously added to getModules() with a .php extension
    Go to app/_tpl folder and add a new file with the fileName previously added to getModules() with a .phtml extension

    Then your page will be available at the BASE_URL/module_key_in_getModules as shown in the example above

Feel free to give me any feedback, some things are to change.