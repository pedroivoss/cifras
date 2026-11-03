<?php

return [

    /*
    | Máximo padrão de playlists (e, portanto, de links públicos) por conta comum. O administrador pode
    | definir outro limite em cada conta; administradores não têm limite.
    */
    'playlist_limit' => (int) env('CIFRA_PLAYLIST_LIMIT', 20),

];
