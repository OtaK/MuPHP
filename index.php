<?php

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

    // Appel de toutes les pages(Views et Controllers) pour construire le site
    include_once 'app/cfg/define.php'; // calling cfg file

    $modules = getModules();
    $acl = new \TakPHPLib\Auth\rightsMan($modules); // rights management object
    session_start();

    $pageName = (!isset($_GET['module']) ? 'home' : addslashes($_GET['module'])); // null check & default page
    if ($auth = $acl->isAuthorized($pageName))
        include __DIR__.'/app/_ctl/'.$modules[$pageName]['fileName'].'.php'; // controller

    include __DIR__.'/app/_tpl/canvas/head.phtml'; // header

    if ($auth)
        include __DIR__.'/app/_tpl/'.$modules[$pageName]['fileName'].'.phtml'; // view
    else // error if hacker detected
        echo "<h2>La page demandée n'existe pas ou vous n'êtes pas autorisé à la voir.</h2>";
    include __DIR__.'/app/_tpl/canvas/foot.phtml'; // footer
