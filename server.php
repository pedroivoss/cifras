<?php

declare(strict_types=1);

/*
 | Front controller do SHIM DE DESENVOLVIMENTO (ver .htaccess): mantém SCRIPT_NAME na raiz do projeto para que o
 | caminho-base (/src/cifras) seja detectado corretamente. Não usado em produção (Nginx aponta para public/).
 */
require_once __DIR__.'/public/index.php';
