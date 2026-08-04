<?php

namespace Database\Seeders;

use App\Models\Brands;
use App\Models\Colors;
use App\Models\People;
use App\Models\Revisions;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = $this->criarUsuario();
            $colors = $this->criarCores();
            $brands = $this->criarMarcas($user);
            $people = $this->criarPessoas($user);
            $vehicles = $this->criarVeiculos($user, $people, $brands, $colors);
            $this->criarRevisoes($user, $vehicles);
        });

        $this->command->info('✅ TestDataSeeder concluído: dados limpos e consistentes gerados.');
    }

    // ------------------------------------------------------------------
    // Usuário de teste
    // ------------------------------------------------------------------
    private function criarUsuario(): User
    {
        return User::updateOrCreate(
            ['email' => 'cesar@example.com'],
            [
                'name' => 'Cesar',
                'password' => Hash::make('password'),
                'status' => true,
            ]
        );
    }

    // ------------------------------------------------------------------
    // Cores (globais, não têm user_id)
    // ------------------------------------------------------------------
    private function criarCores(): array
    {
        $nomes = ['Branco', 'Preto', 'Prata', 'Cinza', 'Vermelho', 'Azul', 'Verde', 'Amarelo', 'Grafite'];

        $cores = [];
        foreach ($nomes as $nome) {
            $cores[$nome] = Colors::firstOrCreate(['name' => $nome]);
        }

        return $cores;
    }

    // ------------------------------------------------------------------
    // Marcas (escopadas por usuário — unique(user_id, name))
    // ------------------------------------------------------------------
    private function criarMarcas(User $user): array
    {
        $nomes = ['Toyota', 'Honda', 'Volkswagen', 'Chevrolet', 'Fiat', 'Hyundai', 'Jeep', 'Ford'];

        $marcas = [];
        foreach ($nomes as $nome) {
            $marcas[$nome] = Brands::firstOrCreate(
                ['user_id' => $user->id, 'name' => $nome]
            );
        }

        return $marcas;
    }

    // ------------------------------------------------------------------
    // Pessoas — 11 PF + 4 PJ, todas com CPF/CNPJ com dígito verificador
    // REAL (mesmo algoritmo que o front valida em person.schema.js), sem
    // nenhum campo em branco fora dos que são legitimamente nulos (PJ não
    // tem gender/birth_date por regra de negócio, não por bug).
    // ------------------------------------------------------------------
    private function criarPessoas(User $user): array
    {
        $definicoes = [
            [
                'name' => 'Ana Beatriz Souza',
                'email' => 'ana.souza@gmail.com',
                'phone' => '11987654321',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '1990-05-14',
            ],
            [
                'name' => 'Carlos Eduardo Lima',
                'email' => 'carlos.lima@outlook.com',
                'phone' => '21998765432',
                'person_type' => 'PF',
                'gender' => 'M',
                'birth_date' => '1985-11-02',
            ],
            [
                'name' => 'Marina Ferreira Costa',
                'email' => 'marina.costa@gmail.com',
                'phone' => '31996543210',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '1995-02-27',
            ],
            [
                'name' => 'João Pedro Almeida',
                'email' => 'joao.almeida@hotmail.com',
                'phone' => '41991234567',
                'person_type' => 'PF',
                'gender' => 'M',
                'birth_date' => '1978-08-19',
            ],
            [
                'name' => 'Fernanda Ribeiro Alves',
                'email' => 'fernanda.alves@gmail.com',
                'phone' => '51992345678',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '2000-01-30',
            ],
            [
                'name' => 'Rafael Henrique Santos',
                'email' => 'rafael.santos@outlook.com',
                'phone' => '61993456789',
                'person_type' => 'PF',
                'gender' => 'O',
                'birth_date' => '1988-06-09',
            ],
            [
                'name' => 'Juliana Martins Rocha',
                'email' => 'juliana.rocha@gmail.com',
                'phone' => '71994567890',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '1992-09-23',
            ],
            [
                'name' => 'Bruno Cardoso Teixeira',
                'email' => 'bruno.teixeira@outlook.com',
                'phone' => '81995678901',
                'person_type' => 'PF',
                'gender' => 'M',
                'birth_date' => '1983-03-11',
            ],
            [
                'name' => 'Larissa Gomes Pereira',
                'email' => 'larissa.pereira@hotmail.com',
                'phone' => '48996789012',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '1998-12-05',
            ],
            [
                'name' => 'Diego Nascimento Barros',
                'email' => 'diego.barros@gmail.com',
                'phone' => '62997890123',
                'person_type' => 'PF',
                'gender' => 'M',
                'birth_date' => '1975-07-16',
            ],
            [
                'name' => 'Camila Duarte Moreira',
                'email' => 'camila.moreira@outlook.com',
                'phone' => '84998901234',
                'person_type' => 'PF',
                'gender' => 'F',
                'birth_date' => '2001-04-08',
            ],
            [
                'name' => 'Comércio de Peças Silva Ltda',
                'email' => 'contato@silvapecas.com.br',
                'phone' => '85994567890',
                'person_type' => 'PJ',
                'gender' => null,
                'birth_date' => null,
            ],
            [
                'name' => 'Transportes Oliveira & Cia Ltda',
                'email' => 'financeiro@oliveiratransportes.com.br',
                'phone' => '47995678901',
                'person_type' => 'PJ',
                'gender' => null,
                'birth_date' => null,
            ],
            [
                'name' => 'Frota Express Logística Ltda',
                'email' => 'operacoes@frotaexpress.com.br',
                'phone' => '19999012345',
                'person_type' => 'PJ',
                'gender' => null,
                'birth_date' => null,
            ],
            [
                'name' => 'Mecânica Central Serviços Ltda',
                'email' => 'contato@mecanicacentral.com.br',
                'phone' => '27990123456',
                'person_type' => 'PJ',
                'gender' => null,
                'birth_date' => null,
            ],
        ];

        $pessoas = [];
        foreach ($definicoes as $def) {
            $documento = $def['person_type'] === 'PJ'
                ? $this->gerarCnpjValido()
                : $this->gerarCpfValido();

            $pessoas[$def['email']] = People::firstOrCreate(
                ['user_id' => $user->id, 'email' => $def['email']],
                [
                    'name' => $def['name'],
                    'document' => $documento,
                    'phone' => $def['phone'],
                    'person_type' => $def['person_type'],
                    'gender' => $def['gender'],
                    'birth_date' => $def['birth_date'],
                ]
            );
        }

        return $pessoas;
    }

    // ------------------------------------------------------------------
    // Veículos — placas em formato antigo (ABC1234) e Mercosul (ABC1D23),
    // ambos aceitos por vehicle.schema.js. Todo brand_id/color_id/people_id
    // aponta pra registros que EXISTEM (é isso que corrige o "Marca" vazio
    // no editar e o "proprietário não localizado" no Kanban).
    // ------------------------------------------------------------------
    private function criarVeiculos(User $user, array $people, array $brands, array $colors): array
    {
        $definicoes = [
            ['dono' => 'ana.souza@gmail.com', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'ano' => 2022, 'cor' => 'Prata', 'placa' => 'ABC1D23'],
            ['dono' => 'ana.souza@gmail.com', 'marca' => 'Toyota', 'modelo' => 'Yaris', 'ano' => 2021, 'cor' => 'Branco', 'placa' => 'ABD2E34'],
            ['dono' => 'carlos.lima@outlook.com', 'marca' => 'Honda', 'modelo' => 'Civic', 'ano' => 2020, 'cor' => 'Preto', 'placa' => 'DEF4567'],
            ['dono' => 'carlos.lima@outlook.com', 'marca' => 'Hyundai', 'modelo' => 'HB20', 'ano' => 2019, 'cor' => 'Branco', 'placa' => 'GHI5J67'],
            ['dono' => 'marina.costa@gmail.com', 'marca' => 'Volkswagen', 'modelo' => 'Polo', 'ano' => 2023, 'cor' => 'Vermelho', 'placa' => 'JKL8901'],
            ['dono' => 'marina.costa@gmail.com', 'marca' => 'Volkswagen', 'modelo' => 'Nivus', 'ano' => 2023, 'cor' => 'Grafite', 'placa' => 'JKM9L02'],
            ['dono' => 'joao.almeida@hotmail.com', 'marca' => 'Chevrolet', 'modelo' => 'Onix', 'ano' => 2018, 'cor' => 'Cinza', 'placa' => 'MNO2P34'],
            ['dono' => 'joao.almeida@hotmail.com', 'marca' => 'Fiat', 'modelo' => 'Argo', 'ano' => 2021, 'cor' => 'Azul', 'placa' => 'PQR6789'],
            ['dono' => 'fernanda.alves@gmail.com', 'marca' => 'Jeep', 'modelo' => 'Renegade', 'ano' => 2022, 'cor' => 'Amarelo', 'placa' => 'STU3V45'],
            ['dono' => 'fernanda.alves@gmail.com', 'marca' => 'Jeep', 'modelo' => 'Compass', 'ano' => 2024, 'cor' => 'Preto', 'placa' => 'STV4W56'],
            ['dono' => 'rafael.santos@outlook.com', 'marca' => 'Ford', 'modelo' => 'Ka', 'ano' => 2017, 'cor' => 'Verde', 'placa' => 'VWX0123'],
            ['dono' => 'juliana.rocha@gmail.com', 'marca' => 'Hyundai', 'modelo' => 'Creta', 'ano' => 2023, 'cor' => 'Branco', 'placa' => 'ROC1A23'],
            ['dono' => 'juliana.rocha@gmail.com', 'marca' => 'Toyota', 'modelo' => 'Corolla', 'ano' => 2020, 'cor' => 'Prata', 'placa' => 'ROC2B34'],
            ['dono' => 'bruno.teixeira@outlook.com', 'marca' => 'Chevrolet', 'modelo' => 'Cruze', 'ano' => 2019, 'cor' => 'Azul', 'placa' => 'TEX3C45'],
            ['dono' => 'larissa.pereira@hotmail.com', 'marca' => 'Fiat', 'modelo' => 'Cronos', 'ano' => 2022, 'cor' => 'Vermelho', 'placa' => 'PER4D56'],
            ['dono' => 'diego.barros@gmail.com', 'marca' => 'Honda', 'modelo' => 'HR-V', 'ano' => 2021, 'cor' => 'Cinza', 'placa' => 'BAR5E67'],
            ['dono' => 'diego.barros@gmail.com', 'marca' => 'Honda', 'modelo' => 'Fit', 'ano' => 2016, 'cor' => 'Preto', 'placa' => 'BAR6F78'],
            ['dono' => 'camila.moreira@outlook.com', 'marca' => 'Volkswagen', 'modelo' => 'Gol', 'ano' => 2018, 'cor' => 'Branco', 'placa' => 'MOR7G89'],
            ['dono' => 'contato@silvapecas.com.br', 'marca' => 'Fiat', 'modelo' => 'Strada', 'ano' => 2020, 'cor' => 'Branco', 'placa' => 'YZA4B56'],
            ['dono' => 'contato@silvapecas.com.br', 'marca' => 'Fiat', 'modelo' => 'Strada', 'ano' => 2021, 'cor' => 'Prata', 'placa' => 'BCD7890'],
            ['dono' => 'contato@silvapecas.com.br', 'marca' => 'Fiat', 'modelo' => 'Toro', 'ano' => 2023, 'cor' => 'Grafite', 'placa' => 'SIL8H90'],
            ['dono' => 'financeiro@oliveiratransportes.com.br', 'marca' => 'Volkswagen', 'modelo' => 'Saveiro', 'ano' => 2019, 'cor' => 'Preto', 'placa' => 'EFG1H23'],
            ['dono' => 'financeiro@oliveiratransportes.com.br', 'marca' => 'Chevrolet', 'modelo' => 'S10', 'ano' => 2022, 'cor' => 'Cinza', 'placa' => 'HIJ4567'],
            ['dono' => 'financeiro@oliveiratransportes.com.br', 'marca' => 'Ford', 'modelo' => 'Ranger', 'ano' => 2023, 'cor' => 'Azul', 'placa' => 'KLM8N90'],
            ['dono' => 'operacoes@frotaexpress.com.br', 'marca' => 'Ford', 'modelo' => 'Ranger', 'ano' => 2022, 'cor' => 'Branco', 'placa' => 'FRO9I01'],
            ['dono' => 'operacoes@frotaexpress.com.br', 'marca' => 'Chevrolet', 'modelo' => 'S10', 'ano' => 2021, 'cor' => 'Prata', 'placa' => 'FRO0J12'],
            ['dono' => 'operacoes@frotaexpress.com.br', 'marca' => 'Volkswagen', 'modelo' => 'Saveiro', 'ano' => 2020, 'cor' => 'Cinza', 'placa' => 'FRO1K23'],
            ['dono' => 'contato@mecanicacentral.com.br', 'marca' => 'Fiat', 'modelo' => 'Uno', 'ano' => 2017, 'cor' => 'Vermelho', 'placa' => 'MEC2L34'],
            ['dono' => 'contato@mecanicacentral.com.br', 'marca' => 'Chevrolet', 'modelo' => 'Onix', 'ano' => 2024, 'cor' => 'Azul', 'placa' => 'MEC3M45'],
        ];

        $veiculos = [];
        foreach ($definicoes as $def) {
            $veiculos[$def['placa']] = Vehicle::firstOrCreate(
                ['user_id' => $user->id, 'license_plate' => $def['placa']],
                [
                    'model' => $def['modelo'],
                    'year' => (string) $def['ano'],
                    'color_id' => $colors[$def['cor']]->id,
                    'brand_id' => $brands[$def['marca']]->id,
                    'people_id' => $people[$def['dono']]->id,
                ]
            );
        }

        return $veiculos;
    }

    // ------------------------------------------------------------------
    // Revisões — 2 por veículo (1 concluída no passado + 1 recente com
    // status variado, cobrindo TODAS as colunas do Kanban). Todos os
    // campos preenchidos, inclusive next_revision_date/km (sem nulos
    // "aleatórios" pra não repetir o comportamento que gerou os prints).
    // ------------------------------------------------------------------
    private function criarRevisoes(User $user, array $vehicles): void
    {
        $statusRotacao = ['concluido', 'em_andamento', 'aguardando_pagamento', 'aberto', 'cancelado'];
        $descricoes = [
            'Troca de óleo e filtro',
            'Revisão completa',
            'Alinhamento e balanceamento',
            'Troca de pastilhas de freio',
            'Troca de filtro de ar e combustível',
            'Diagnóstico eletrônico',
            'Troca de correia dentada',
            'Revisão programada de 10.000 km',
        ];

        $indice = 0;
        foreach ($vehicles as $placa => $vehicle) {
            $kmBase = 12000 + ($indice * 1800);
            $custoBase = 180 + ($indice * 37.5);

            $statusRecente = $statusRotacao[$indice % count($statusRotacao)];
            $pagamentoRecente = $statusRecente === 'concluido' ? 'pago' : 'pendente';

            // Revisão 1 — histórico, sempre concluída e paga
            $dataRevisao1 = now()->subMonths(10)->subDays($indice);
            Revisions::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'revision_date' => $dataRevisao1->toDateString(),
                ],
                [
                    'user_id' => $user->id,
                    'description' => $descricoes[$indice % count($descricoes)],
                    'cost' => round($custoBase, 2),
                    'km' => $kmBase,
                    'next_revision_date' => $dataRevisao1->copy()->addMonths(6)->toDateString(),
                    'next_revision_km' => $kmBase + 6000,
                    'status' => 'concluido',
                    'status_pagamento' => 'pago',
                ]
            );

            // Revisão 2 — recente, status rotacionado (cobre todas as colunas do Kanban)
            $dataRevisao2 = now()->subDays(15 + $indice * 4);
            Revisions::firstOrCreate(
                [
                    'vehicle_id' => $vehicle->id,
                    'revision_date' => $dataRevisao2->toDateString(),
                ],
                [
                    'user_id' => $user->id,
                    'description' => $descricoes[($indice + 3) % count($descricoes)],
                    'cost' => round($custoBase + 95.9, 2),
                    'km' => $kmBase + 6500,
                    'next_revision_date' => $dataRevisao2->copy()->addMonths(6)->toDateString(),
                    'next_revision_km' => $kmBase + 6500 + 6000,
                    'status' => $statusRecente,
                    'status_pagamento' => $pagamentoRecente,
                ]
            );

            $indice++;
        }
    }

    // ------------------------------------------------------------------
    // Geradores de documento com dígito verificador VÁLIDO — mesmo
    // algoritmo (módulo 11) que isValidDocument() do frontend confere.
    // Sem isso, a tela de edição/validação no front rejeitaria os
    // registros seedados ao reabrir o formulário.
    // ------------------------------------------------------------------
    private function gerarCpfValido(): string
    {
        do {
            $n = [];
            for ($i = 0; $i < 9; $i++) {
                $n[] = random_int(0, 9);
            }
            $n[] = $this->digitoVerificadorCpf($n);
            $n[] = $this->digitoVerificadorCpf($n);
            $cpf = implode('', $n);
        } while (People::where('document', $cpf)->exists());

        return $cpf;
    }

    private function digitoVerificadorCpf(array $digitos): int
    {
        $soma = 0;
        $peso = count($digitos) + 1;
        foreach ($digitos as $d) {
            $soma += $d * $peso;
            $peso--;
        }
        $resto = $soma % 11;
        return $resto < 2 ? 0 : 11 - $resto;
    }

    private function gerarCnpjValido(): string
    {
        do {
            $n = [];
            for ($i = 0; $i < 8; $i++) {
                $n[] = random_int(0, 9);
            }
            // filial fixa 0001
            array_push($n, 0, 0, 0, 1);

            $n[] = $this->digitoVerificadorCnpj($n, [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
            $n[] = $this->digitoVerificadorCnpj($n, [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]);
            $cnpj = implode('', $n);
        } while (People::where('document', $cnpj)->exists());

        return $cnpj;
    }

    private function digitoVerificadorCnpj(array $digitos, array $pesos): int
    {
        $soma = 0;
        foreach ($digitos as $i => $d) {
            $soma += $d * $pesos[$i];
        }
        $resto = $soma % 11;
        return $resto < 2 ? 0 : 11 - $resto;
    }
}