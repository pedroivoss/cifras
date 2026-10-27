<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Dados de demonstração para ambiente local. Letras fictícias, criadas só para teste.
 * Vincula ao primeiro usuário (crie-o antes com `php artisan cifra:create-admin`).
 */
class DemoSeeder extends Seeder
{
    private const SONGS = [
        ['Abre a Porta do Templo', 'Ministério Exemplo', 'G', 'Canto de Entrada', "[Intro] G  D/F#  Em  C\n\nG              D/F#\nAbre a porta do templo, ó povo\nEm               C\nVem cantar ao Senhor que nos chama\nG             D\nCom alegria e fé renovada\nC        D        G\nSomos família, somos irmãos\n\n[Refrão]\nC          D\nVem, entra e louva\nBm          Em\nA mesa já está posta\nAm7        D7       G\nO Pai nos espera com amor"],
        ['Piedade de Nós', 'Ministério Exemplo', 'Am', 'Ato Penitencial', "Am           G\nSenhor, tende piedade\nF            E7\nDe nós que caminhamos\nAm          Dm\nCristo, tende piedade\nE7           Am\nPerdoai nosso pecar"],
        ['Glória nas Alturas', 'Coral Exemplo', 'D', 'Glória', "{title: Glória nas Alturas}\n{key: D}\n{soc}\n[D]Glória, glória a [A/C#]Deus nas al[Bm]turas\n[G]E paz na terra a[A]os que Ele a[D]ma\n{eoc}\n[G]Nós vos lou[A]vamos, vos bendi[F#m]zemos\n[Em7]Nós vos ado[A7]ramos, Se[D]nhor"],
        ['Santo é o Senhor', 'Coral Exemplo', 'C', 'Santo', "C          G/B       Am\nSanto, santo, santo é o Senhor\nF            C/E        Dm7   G7\nDeus do universo, o céu e a terra\nC         C7M      F    Fm\nProclamam a vossa glória\nC     G   C\nHosana!"],
        ['Pão da Vida', 'Ministério Exemplo', 'E', 'Comunhão', "[Intro] E  B/D#  C#m7  A9\n\nE              B/D#\nTu és o pão que desce do céu\nC#m7            A9\nAlimento que sacia a alma\nF#m7       B7sus4   B7\nNa tua mesa encontro a paz\n\nE  B  C#m  A  (x2)"],
        ['Vai em Paz', 'Coral Exemplo', 'F', 'Canto Final', "F            C/E\nVai em paz, o Senhor te acompanha\nDm7          Bb\nLeva ao mundo a luz que recebeu\nGm7       C7sus4  C7\nSê testemunha do amor\nF   Bb  F\nAmém"],
    ];

    public function run(): void
    {
        $user = User::orderBy('id')->first();

        if ($user === null) {
            $this->command?->warn('Nenhum usuário encontrado. Rode `php artisan cifra:create-admin` antes.');

            return;
        }

        $user->seedDefaultTags();

        $songs = collect(self::SONGS)->map(function (array $data) use ($user) {
            [$title, $artist, $key, $tagName, $content] = $data;
            $song = $user->songs()->firstOrCreate(['title' => $title], [
                'artist' => $artist,
                'original_key' => $key,
                'content' => $content."\n",
                'source' => 'manual',
            ]);
            $song->tags()->syncWithoutDetaching($user->tags()->where('name', $tagName)->pluck('id'));

            return $song;
        });

        $playlist = $user->playlists()->firstOrCreate(['name' => 'Missa de Domingo (demo)'], ['description' => 'Repertório de exemplo']);

        if ($playlist->items()->doesntExist()) {
            foreach ($songs->values() as $index => $song) {
                $playlist->items()->create(['song_id' => $song->id, 'position' => $index + 1, 'semitones' => $index === 3 ? 2 : 0]);
            }
        }
    }
}
