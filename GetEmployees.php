<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\CostCenter;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Profession;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class GetEmployees extends Command
{
    protected $signature = 'app:get-employees';

    protected $description = 'Busca os colaboradores no banco de dados';

    public function handle(): void
    {
        $this->info("Comando para buscar colaboradores no banco de dados");

        $cnpj = $this->ask("Insira o CNPJ do cliente");

        $company = $this->getCompany(cnpj: $cnpj);

        $this->info("Empresa encontrada: $company->name");

        $options = [
            'T' => 'Todos colaboradores',
            'D' => 'Colaboradores por Departamento',
            'F' => 'Colaboradores por Função',
            'C' => 'Colaboradores por Centro de Custo',
        ];

        $select = $this->choice("Selecione o filtro de colaboradores", $options);

        $employees = $this->selectOptions($select, $company->id);

        $this->table(['id', 'nome', 'email', 'cpf'], $employees);

    }

    private function getCompany(mixed $cnpj)
    {
        return Client::query()
            ->select('id', 'name')
            ->where('cnpj', $cnpj)
            ->first();
    }

    private function selectOptions(array|string $select, $company): Collection|array
    {
        $employess = [];

        switch ($select) {
            case 'T':
                $employess = $this->getAllEmployees($company);
                break;

            case 'D':
                $text = 'Departamento';
                $model = new Department();
                $employess = $this->getInfoEmployees($company, $text, $model);
                break;

            case 'F':
                $text = 'Função';
                $model = new Profession();
                $employess = $this->getInfoEmployees($company, $text, $model);
                break;

            case 'C':
                $text = 'Centro de Custo';
                $model = new CostCenter();
                $employess = $this->getInfoEmployees($company, $text, $model);
                break;

            default:
                break;
        }

        return $employess;
    }

    private function getAllEmployees($company)
    {
        return Employee::query()
            ->select('id', 'name', 'email', 'cpf')
            ->where('client_id', $company)
            ->get();
    }

    /** Busca as informações dos colaboradores de acordo com os filtros */
    private function getInfoEmployees(int $company, string $text, $model)
    {
        $filter = $this->getCollection($company, $model);

        $filterName = $this->getSearch($filter, $text);

        return $this->getDatasEmployees($filter, $filterName);
    }

    /** Busca os dados da Model no banco */
    public function getCollection($company, $model): Collection|array
    {
        return $model->query()
            ->select('id', 'name')
            ->where('client_id', $company)
            ->get();
    }

    /** Recebe a opção informada pelo usuário */
    public function getSearch(Collection|array $options, string $text): string|array
    {
        return $this->choice("Escolha o $text",
            $options->pluck('name', 'id')
                ->toArray());
    }

    /** Busca os dados do colaborador */
    public function getDatasEmployees(Collection|array $filters, string $filtersName)
    {
        return $filters
            ->firstWhere('name', $filtersName)
            ->employees()
            ->select('id', 'name', 'email', 'cpf')
            ->get();
    }

}

