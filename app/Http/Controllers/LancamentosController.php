<?php

namespace App\Http\Controllers;

use App\Models\Lancamentos;
use App\Models\Moedas;
use App\Models\Status;
use App\Models\Tipo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LancamentosExport;
use App\Models\User;
use GuzzleHttp\Client;

class LancamentosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lancamento = Lancamentos::orderBy('id', 'desc')->get();
        $moeda = Moedas::all();
        $tipo = Tipo::all();
        $status = Status::where('id', '!=', 3)->get();
        $users = User::all();
    
        return view('lancamento.criar', compact(['lancamento', 'moeda', 'tipo', 'status', 'users']));
    }

    public function indexLista(Request $request)
    {
        $lancamento = Lancamentos::orderBy('id', 'desc')->paginate(15);
        $moeda = Moedas::all();
        $tipo = Tipo::all();
        $status = Status::where('id', '!=', 3)->get();
        $users = User::all();

        // Retorna a view completa com a estrutura de página, incluindo a paginação
        return view('lancamento.lista_lancamento', compact('lancamento', 'moeda', 'tipo', 'status', 'users'));
    }

    



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $messages = [
            'codigo.*.required' => 'O campo código é obrigatório.',
            'moeda_id.*.required' => 'O campo moeda é obrigatório.',
            'moeda_id.*.integer' => 'O campo moeda deve ser selecionado.',
            'moeda_id.*.not_in' => 'Por favor, selecione uma moeda válida.',
            'valor.*.required' => 'O campo valor é obrigatório.',
            'valor.*.numeric' => 'O campo valor deve ser numérico.',
            'tipo_id.*.required' => 'O campo tipo é obrigatório.',
            'tipo_id.*.integer' => 'O campo tipo deve ser um número inteiro.',
            'tipo_id.*.not_in' => 'Por favor, selecione um tipo válido.',
            'user_id.*.integer' => 'O campo usuário deve ser um número inteiro.',
        ];

        $validatedData = $request->validate([
            'codigo.*' => 'required|string',
            'moeda_id.*' => 'required|integer|not_in:0',
            'valor.*' => 'required|string',
            'tipo_id.*' => 'required|integer|not_in:0',
            'user_id.*' => 'nullable|integer',
        ], $messages);
        $codigos = $validatedData['codigo'];
        $moedas = $validatedData['moeda_id'];
        $valores = $validatedData['valor'];
        $tipos = $validatedData['tipo_id'];
        $users = $validatedData['user_id'];

        foreach ($codigos as $index => $codigo) {
            $moeda = $moedas[$index];
            $valor = $valores[$index];
            $tipo = $tipos[$index];
            $user = isset($users[$index]) ? $users[$index] : null; // Verificar se o usuário está definido

            // Check if the lancamento already exists
            $existeLancamento = Lancamentos::where('codigo', $codigo)->first();

            if ($existeLancamento) {
                return redirect()->back()->with('error', 'Lançamento já existe!');
            }

            // Define o status_id com base na presença de user_id
            $status_id = $user ? 2 : 1;

            // Create a new lancamento
            Lancamentos::create([
                'codigo' => $codigo,
                'moeda_id' => $moeda,
                'valor' => $valor,
                'tipo_id' => $tipo,
                'user_id' => $user,
                'status_id' => $status_id,
                'valido' => 'S',
            ]);
        }

        return redirect()->back()->with('success', 'Lançamentos cadastrados!');
    }



    /**
     * Display the specified resource.
     */
    public function show(Lancamentos $lancamentos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Lancamentos $lancamentos)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $lancamento = Lancamentos::find($id);

        if (!$lancamento) {
            return redirect()->back()->with('error', 'lançamento não encontrado!');
        }

        $lancamento->codigo = $request->input('codigo');
        $lancamento->moeda_id = $request->input('moeda_id');
        $lancamento->tipo_id = $request->input('tipo_id');
        $lancamento->valor = $request->input('valor');
        $lancamento->status_id = $request->input('status_id');
        $lancamento->valido = $request->input('valido');

        $lancamento->save();

        return redirect()->back()->with('success', 'Lançamento Atualizado com sucesso!');
    }

    public function updateStatus(Request $request)
    {
        $lancamento = Lancamentos::find($request->lancamento_id);

        $lancamento->status_id = $request->status_id;
        $lancamento->user_id = auth()->user()->id;
        $lancamento->save();

        return response()->json(['success' => 'Status atualizado com sucesso']);
    }

    public function updateStatus3(Request $request)
{
    $lancamentoIds = $request->lancamento_ids; // Array of lancamento ids
    $statusId = $request->status_id;

    try {
        foreach ($lancamentoIds as $lancamentoId) {
            $lancamento = Lancamentos::find($lancamentoId);

            if ($lancamento) {
                $lancamento->status_id = $statusId;
                $lancamento->user_id = auth()->user()->id; // Assuming you want to track who made the update
                $lancamento->save();
            }
        }

        return response()->json(['success' => 'Status atualizado com sucesso']);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
    }
}

public function updateStatus4(Request $request)
    {
        foreach ($request->lancamento_ids as $lancamento_id) {
            $lancamento = Lancamentos::find($lancamento_id);
            $lancamento->status_id = $request->status_id;
            $lancamento->user_id = auth()->user()->id;
            $lancamento->save();
        }

        return response()->json(['success' => 'Status atualizado com sucesso']);
    }

    public function updateStatus1(Request $request)
    {
        foreach ($request->lancamento_ids as $lancamento_id) {
            $lancamento = Lancamentos::find($lancamento_id);
            $lancamento->valido = $request->valido;
            $lancamento->user_id = auth()->user()->id;
            $lancamento->save();
        }

        return response()->json(['success' => 'Status atualizado com sucesso']);
    }

    public function exportarSelecionadosParaExcel(Request $request)
    {
        // Obtém os IDs dos lançamentos selecionados
        $lancamentosSelecionados = explode(',', $request->query('lancamentos'));

        // Remove IDs vazios ou não numéricos e mantém apenas IDs válidos
        $lancamentosIds = array_filter($lancamentosSelecionados, function ($id) {
            return is_numeric($id) && intval($id) > 0; // Verifica se é numérico e maior que zero
        });

        // Verifica se há IDs válidos para prosseguir
        if (empty($lancamentosIds)) {
            // Retorna uma resposta de erro ou faz outra ação adequada
            return redirect()->back()->with('error', 'Nenhum Lançamento selecionado.');
        }

        // Inicia o download do arquivo Excel com os dados dos lançamentos selecionados
        return Excel::download(new LancamentosExport($lancamentosIds), 'lancamentos_selecionados.xlsx');
    }

    public function controle(Request $request)
    {
        $status_id = $request->input('status_id');
        $tipo_id = $request->input('tipo_id');
        $user_id = $request->input('user_id');

        $query = Lancamentos::query();

        if ($status_id) {
            $query->where('status_id', $status_id);
        }
        if ($user_id) {
            $query->where('user_id', $user_id);
        }
        if ($tipo_id) {
            $query->where('tipo_id', $tipo_id);
        }

        $lancamentos = $query->get();
        $status = Status::all();
        $tipos = Tipo::all();
        $user = User::all();
        // Obter cotação BRL para USD
        $cotacaoBRLtoUSD = $this->getCotacaoBRLtoUSD();
        $cotacoes = Moedas::all()->pluck('cotacao', 'abreviacao');

        return view('lancamento.controle', compact('user', 'lancamentos', 'status', 'tipos', 'cotacaoBRLtoUSD', 'cotacoes'));
    }

    public function listaUser()
    {
        // Obtém o ID do usuário logado
        $userId = Auth::id();

        // Filtra os lançamentos pelo usuário logado e exclui aqueles com status_id igual a 2
        $lancamento = Lancamentos::where('user_id', $userId)->where('status_id', '!=', 1)->orderBy('id', 'desc')->get();

        return view('lancamento.listauser', compact('lancamento'));
    }

    public function listaLiberar()
    {
        $lancamento = Lancamentos::whereIn('status_id', [2, 4])->orderBy('id', 'desc')->get();

        return view('lancamento.liberar', compact('lancamento'));
    }

    public function showCart()
    {
        $lancamento = Lancamentos::where('user_id', Auth::id())->get();
        $valorTotal = $lancamento->sum('valor'); // Supondo que a coluna de valor seja 'valor'

        return view('carrinho', compact('lancamentos', 'valorTotal'));
    }

    public function getCotacaoBRLtoUSD()
    {
        $client = new Client();
        $response = $client->get('https://economia.awesomeapi.com.br/json/last/BRL-USD', ['verify' => false]);
        $data = json_decode($response->getBody(), true);
        return $data['BRLUSD']['bid']; // Retorna a taxa de câmbio de compra
    }


    public function showTable()
    {
        $cotacaoBRLtoUSD = $this->getCotacaoBRLtoUSD();
        $lancamentos = Lancamentos::all(); // ou o método que você usa para obter os lançamentos
        $cotacoes = Moedas::all()->pluck('cotacao', 'abreviacao'); // ou como você está obtendo as cotações das moedas

        return view('lancamento.controle', compact('lancamentos', 'cotacoes', 'cotacaoBRLtoUSD'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $lancamento = Lancamentos::findOrFail($id);

        $lancamento->delete();

        return redirect()->back()->with('success', 'Lançamento excluído com sucesso!');
    }

    public function reserva(Request $request)
    {
        $ids = $request->input('ids', []);
        DB::table('lancamentos')
            ->whereIn('id', $ids)
            ->update(['status_id' => 5]);

        return response()->json(['message' => 'Lançamentos reservados com sucesso.']);
    }

    public function updateStatus2(Request $request)
{
    foreach ($request->lancamento_ids as $lancamento_id) {
        $lancamento = Lancamentos::find($lancamento_id);
        $lancamento->status_id = $request->status_id;
        $lancamento->save();
    }

    return response()->json(['success' => 'Status atualizado com sucesso']);
}

}
