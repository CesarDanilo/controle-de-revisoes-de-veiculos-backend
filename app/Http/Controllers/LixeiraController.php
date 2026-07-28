<?php

namespace App\Http\Controllers;

use App\Models\Lixeira;
use App\Models\People;
use App\Models\Vehicle;
use App\Models\Revisions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LixeiraController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $porPagina = $request->integer('per_page', 15);

        // 🔴 FIX — a coluna que registra o dono do item é "excluido_por"
        // (preenchida pelo trait PodeSerMovidoParaLixeira em todo
        // moverParaLixeira()), não "user_id".
        $query = Lixeira::where('excluido_por', Auth::id())
            ->where('excluido_em', '>', now()->subDays(7));

        if ($tipo = $request->query('tipo')) {
            $query->where('tabela_origem', $tipo);
        }

        if ($busca = $request->query('busca')) {
            $termo = "%{$busca}%";
            $query->where(function ($q) use ($termo) {
                $q->whereRaw("dados->>'name' ILIKE ?", [$termo])
                  ->orWhereRaw("dados->>'description' ILIKE ?", [$termo])
                  ->orWhereRaw("dados->>'license_plate' ILIKE ?", [$termo])
                  ->orWhereRaw("dados->>'model' ILIKE ?", [$termo]);
            });
        }

        $itens = $query
            ->orderByDesc('excluido_em')
            ->paginate($porPagina)
            ->through(fn($item) => [
                'id' => $item->id,
                'tabela_origem' => $item->tabela_origem,
                'dados' => $item->dados,
                'excluido_em' => $item->excluido_em,
                'dias_restantes' => $item->diasRestantes(),
            ]);

        return response()->json($itens);
    }

    public function restaurar(string $id): JsonResponse
    {
        $item = Lixeira::where('excluido_por', Auth::id())
            ->findOrFail($id);

        if ($item->expirado()) {
            return response()->json(['erro' => 'Este item expirou e não pode mais ser restaurado.'], 410);
        }

        $modelClass = $this->resolverModel($item->tabela_origem);

        if (! $modelClass) {
            return response()->json(['erro' => 'Tipo de registro desconhecido na lixeira.'], 422);
        }

        DB::transaction(function () use ($modelClass, $item) {
            $modelClass::create($item->dados);
            $item->delete();
        });

        return response()->json(['mensagem' => 'Item restaurado com sucesso.']);
    }

    public function destruirPermanente(string $id): JsonResponse
    {
        $item = Lixeira::where('excluido_por', Auth::id())
            ->findOrFail($id);

        $item->delete();

        return response()->json(['mensagem' => 'Item excluído permanentemente.']);
    }

    private function resolverModel(string $tabelaOrigem): ?string
    {
        return match ($tabelaOrigem) {
            'people' => People::class,
            'vehicle' => Vehicle::class,
            'revisions' => Revisions::class,
            default => null,
        };
    }
}