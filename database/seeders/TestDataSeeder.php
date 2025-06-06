<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curso;
use App\Models\Noticia;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Criar 10 cursos variados
        Curso::factory(10)->aberto()->create();
        Curso::factory(7)->finalizado()->create();
        Curso::factory(2)->online()->create();
        Curso::factory(3)->presencial()->create();

        // Criar 15 notícias variadas
        Noticia::factory(5)->destaque()->create();
        Noticia::factory(9)->publicada()->create();
        Noticia::factory(2)->rascunho()->create();
    }
}