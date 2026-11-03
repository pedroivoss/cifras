<?php

return [

    /*
    | Máximo de playlists (e, portanto, de links públicos) por conta comum. Administradores não têm limite.
    */
    'playlist_limit' => (int) env('CIFRA_PLAYLIST_LIMIT', 30),

];
