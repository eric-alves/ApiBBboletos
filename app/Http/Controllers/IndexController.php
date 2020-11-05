<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IndexController extends Controller
{
    // Importar classe "BBBoletoWebService" do arquivo no caminho "./lib/bb.php"
    //require_once(__DIR__ . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'bb.php');

    // Se for uma requisição na Web, retorna no formato texto plano com suporte à codificação de caracteres UTF-8

public function preparaDados(){
    // Exemplo de preenchimento e uso abaixo. Por favor, não deixe de ler a especificação
    // e definir a chamada conforme a sua necessidade.
    $convenio = '2625444';
    $numerodacarteira = '17';
    $variacaodacarteira = '19';
    $numerodoboleto = '155149';
    $datadaemissao = '30.10.2020';					// Segundo a especificação, deve ser no formato DD.MM.AAAA
    $datadovencimento = '05.11.2020';				// Segundo a especificação, deve ser no formato DD.MM.AAAA
    $valor = '150.35';								// No formato inglês (sem separador de milhar)
    $tipodedocumentodocliente = 2;					// 1 para CPF e 2 para CNPJ
    $numerodedocumentodocliente = '12345678901';	// CPF ou CNPJ, sem pontos ou traços
    $nomedocliente = 'Boleto de Teste';
    $enderecodocliente = 'Boleto de Teste';
    $bairrodocliente = 'Boleto de Teste';
    $municipiodocliente = 'Boleto de Teste';
    $sigladoestadodocliente = 'MG';
    $cepdocliente = '36212000';						// Sem pontos ou traços
    $telefonedocliente = '';

    $numeroInscricaoPagador = '00000000000191';

    // Cria objeto de BBBoletoWebService para consumo de serviço
    $bb = new RegistrarBoletoController(
        'eyJpZCI6IjgwNDNiNTMtZjQ5Mi00YyIsImNvZGlnb1B1YmxpY2Fkb3IiOjEwOSwiY29kaWdvU29mdHdhcmUiOjEsInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxfQ', 
        'eyJpZCI6IjBjZDFlMGQtN2UyNC00MGQyLWI0YSIsImNvZGlnb1B1YmxpY2Fkb3IiOjEwOSwiY29kaWdvU29mdHdhcmUiOjEsInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxLCJzZXF1ZW5jaWFsQ3JlZGVuY2lhbCI6MX0');

    // O diretório de cache pode ser alterado pelo método "trocarCaminhoDaPastaDeCache"
    // $bb->trocarCaminhoDaPastaDeCache('./cache'); // exemplo

    // Parâmetros que serão passados para o Banco do Brasil
    $parametros = array(
        'numeroConvenio' => $convenio,
        'numeroCarteira' => $numerodacarteira,
        'numeroVariacaoCarteira' => $variacaodacarteira,
        'codigoModalidadeTitulo' => 1,
        'dataEmissaoTitulo' => $datadaemissao,
        'dataVencimentoTitulo' => $datadovencimento,
        'valorOriginalTitulo' => $valor,
        //'codigoTipoDesconto' => 0,
        //'codigoTipoJuroMora' => 0,
        //'codigoTipoMulta' => 0,
        'codigoAceiteTitulo' => 'N',
        'codigoTipoTitulo' => 17,
        'textoDescricaoTipoTitulo' => 'Recibo',
        'indicadorPermissaoRecebimentoParcial' => 'N',
        'textoNumeroTituloBeneficiario' => '1',
        'textoNumeroTituloCliente' => '000' . $convenio . sprintf('%010d', $numerodoboleto),
        'textoMensagemBloquetoOcorrencia' => 'Pagamento disponível até a data de vencimento',
        'codigoTipoInscricaoPagador' => $tipodedocumentodocliente,
        'numeroInscricaoPagador' => $numerodedocumentodocliente,
        'nomePagador' => $nomedocliente,
        'textoEnderecoPagador' => $enderecodocliente,
        'numeroCepPagador' => $cepdocliente,
        'nomeMunicipioPagador' => $municipiodocliente,
        'nomeBairroPagador' => $bairrodocliente,
        'siglaUfPagador' => $sigladoestadodocliente,
        'textoNumeroTelefonePagador' => $telefonedocliente,
        'codigoChaveUsuario' => 1,
        'codigoTipoCanalSolicitacao' => 5,

        'numeroInscricaoPagador' => $numeroInscricaoPagador
    );

    // Passa para o ambiente de testes. Por padrão, o construtor usa o ambiente de produção.
    // Para retornar para o ambiente de produção a qualquer momento, basta chamar o método
    // alterarParaAmbienteDeProducao() (ex.: $bb->alterarParaAmbienteDeProducao();)
    $bb->alterarParaAmbienteDeTestes();
    // $bb->alterarParaAmbienteDeProducao();

    // Exemplo de chamada passando os parâmetros com a token.
    // Retorna um array com a resposta do Banco do Brasil, se ocorreu tudo bem. Caso contrário, retorna "false".
    // A descrição do erro pode ser obtida pelo método "obterErro()".
    $resultado = $bb->registrarBoleto($parametros);

    // As linhas abaixo apenas testam o resultado

    echo "\n";

    $token = $bb->obterToken(false);
    if ($token) {
        echo "Token recebida/usada:\n\n" . $token->token . "\n\n\n";
        echo "Token obtida em cache:\n\n" . ($token->cache ? 'Sim' : 'Não') . "\n\n\n";
    } else
        echo "Falha ao receber/usar a token.\n\n\n";

    echo "Resultado:\n\n" . ($resultado ? 'Boleto registrado no Banco do Brasil com sucesso' : 'Erro. O boleto não foi registrado no Banco do Brasil.') . "\n\n\n";

    if ($resultado) {
        echo "Parse do resultado:\n\n";
        print_r($resultado);
    } else
        echo "Descrição do erro:\n\n" . $bb->obterErro() . "\n";

    echo "\n\n";

    flush();
}
}
