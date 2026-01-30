<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Caixa;
use App\Models\Entrada_caixa;
use App\Models\Estoque;
use App\Models\Sangria;
use App\Models\Sistema;
use App\Models\Transacoes;
use App\Models\Venda;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaixaControllerWitTooManyPublicMethods extends Controller
{
    public function __construct(
        private Caixa $caixaModel,
        private Transacoes $transacoesModel,
        private Sistema $sistemaModel,
        private Sangria $sangriaModel,
        private Entrada_caixa $entradaCaixaModel,
        private Venda $vendaModel,
        private Estoque $estoqueModel
    ) {
    }

    public function iniciarCaixaView(): Factory|Application|View
    {
        $caixa = $this->caixaModel->today();

        return view('admin.caixa.abrir', [
            'aberto' => $this->caixaModel->checkOpen(),
            'caixa' => $caixa
        ]);
    }

    public function iniciarCaixa(Request $request)
    {
        $caixa = $this->caixaModel;

        if (!$caixa->checkOpen() && $caixa == null) {
            try {
                $caixa = $this->caixaModel;
                $caixa->data = date('Y-m-d');
                $caixa->inicial = str_replace(['.', ','], ['', '.'], $request->valor);
                $caixa->valor = str_replace(['.', ','], ['', '.'], $request->valor);
                $this->sistemaModel->setVal('caixa_aberto', true);
                $caixa->save();
                return response()->json([
                    'success' => 'true',
                    'message' => 'Caixa foi aberto'
                ]);
            } catch (QueryException $e) {
                return response()->json([
                    'success' => 'false',
                    'message' => $e->errorInfo[2]
                ]);
            }
        }

        return view('admin.caixa.abrir', ['aberto' => $caixa->checkOpen()]);
    }

    public function fecharCaixaView()
    {

        $transacoes = $this->transacoesModeltoday();
        $sangria = $this->sangriaModel->today();
        $entradas = $this->entradaCaixaModel->today();
        foreach ($sangria as $sang) {
            $sang->valor = number_format($sang->valor, 2, ',', '.');
        }
        foreach ($entradas as $entrada) {
            $entrada->valor = number_format($entrada->valor, 2, ',', '.');
        }
        $detalhes = '';

        foreach ($transacoes as $value) {
            $transacoesId = $value->id;
            $venda = $this->vendaModel->where('transacao', '=', $transacoesId)->get();
            $value->pagamento = str_replace(
                ['DI', 'CR', 'DE'],
                ['Dinheiro', 'Cartão de Crédito', 'Débito'],
                $value->pagamento
            );

            $value->desconto = $value->desconto . '%';
            $value->total = number_format($value->total, 2, ',', '.');
            foreach ($venda as $val) {
                $codigo = $val->codigo_estoque;
                $estoque = $this->estoqueModel->where('codigo', '=', $codigo)->first();
                $detalhes = $estoque->nome . ' | ' . $detalhes;
            }
            $value->detalhes = $detalhes;
            $detalhes = '';
        }

        $caixaValor = $this->caixaModel->today() ?: (object)[
            'valor' => '0,00',
            'inicial' => '0,00',
            'totalCredito' => '0,00',
            'totalDebito' => '0,00',
            'totalC' => '0,00'
        ];

        if ($caixaValor !== (object)[/* ... */]) {  // Se não for o objeto padrão
            $caixaValor->valor = number_format($caixaValor->valor, 2, ',', '.');
            $caixaValor->inicial = number_format($caixaValor->inicial, 2, ',', '.');
            $caixaValor->totalCredito = number_format($this->transacoesModel->totalCreditoDay(), 2, ',', '.');
            $caixaValor->totalDebito = number_format($this->transacoesModel->totalDebitoDay(), 2, ',', '.');
            $caixaValor->totalC = number_format(
                $this->transacoesModel->totalDebitoDay() + $this->transacoesModel->totalCreditoDay(),
                2,
                ',',
                '.'
            );
        }

        return view(
            'admin.caixa.fechar',
            [
                'aberto' => $this->caixaModel->checkOpen(),
                'caixaValor' => $caixaValor,
                'transacoes' => $transacoes,
                'sangria' => $sangria,
                'entrada' => $entradas
            ]
        );
    }

    public function sangriaView()
    {
        return view('admin.caixa.sangria', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    public function sangriaPost(Request $request)
    {
        if ($this->caixaModel->checkOpen()) {
            $sangria = new Sangria();
            $sangria->data = $this->data();
            $sangria->descricao = $request->descricao;
            $sangria->valor = str_replace(['.', ','], ['', '.'], $request->valor);
            try {
                if ($sangria->save()) {
                    $this->caixaModel->getOff($sangria->valor);
                    return response()->json([
                        'success' => 'true',
                        'message' => 'Retirado com sucesso, o saldo do caixa é de R$' . number_format(
                            $this->caixaModel->today()->valor,
                            2,
                            ',',
                            '.'
                        )
                    ]);
                }
            } catch (QueryException $e) {
                return $e->errorInfo[2];
            }
        }

        return false;
    }

    protected function data()
    {
        return date('Y-m-d');
    }

    public function addCaixaView()
    {
        return view('admin.caixa.adicionar', ['aberto' => $this->caixaModel->checkOpen()]);
    }

    public function generateJ()
    {
        return "J";
    }

    public function generateH()
    {
        return "H";
    }

    public function generateI()
    {
        return "I";
    }

    public function generateK()
    {
        return "K";
    }

    public function generateL()
    {
        return "L";
    }

    public function generateP()
    {
        return "P";
    }

    protected function generateQ()
    {
        return "Q";
    }

    protected function generateR()
    {
        return "R";
    }

    protected function generateS()
    {
        return "S";
    }

    protected function generateT()
    {
        return "T";
    }

    protected function generateF()
    {
        return "F";
    }

    protected function generateG()
    {
        return "G";
    }

    protected function generateM()
    {
        return "M";
    }

    protected function generateN()
    {
        return "N";
    }

    protected function generateO()
    {
        return "O";
    }
}
