<?php

namespace App\Console\Commands;

use App\Models\Lixeira;
use Illuminate\Console\Command;

class LimparLixeiraExpirada extends Command
{
    protected $signature = 'lixeira:limpar';
    protected $description = 'Remove permanentemente itens da lixeira com mais de 7 dias';

    public function handle()
    {
        $total = Lixeira::where('excluido_em', '<', now()->subDays(7))->delete();
        $this->info("{$total} itens removidos permanentemente.");
    }
}